<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

/* ==========================
   Helpers
========================== */
function jsonOut(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}
function cleanName(string $name): string {
  $name = trim($name);
  $name = preg_replace('/[^\pL\pN\.\-\_\s]/u', '', $name) ?: 'archivo';
  $name = preg_replace('/\s+/', '_', $name);
  return $name;
}
function extFromName(string $name): string {
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  return $ext ?: '';
}

/* ==========================
   Diagnóstico SOLO en GET
========================== */
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'GET') {
  jsonOut(200, [
    'ok'                 => true,
    'upload_max_filesize'=> ini_get('upload_max_filesize'),
    'post_max_size'      => ini_get('post_max_size'),
    'memory_limit'       => ini_get('memory_limit'),
    'file_uploads'       => ini_get('file_uploads'),
  ]);
}
if ($method !== 'POST') {
  jsonOut(405, ['ok'=>false,'error'=>'Método no permitido']);
}

/* ==========================
   1) Autoload
========================== */
$here      = __DIR__;           // .../app/controllers/documentos
$publicDir = dirname($here, 3); // .../public (si aplica)
$rootDir   = dirname($here, 4); // .../

$autoloadPaths = [
  $rootDir   . '/vendor/autoload.php',
  $publicDir . '/vendor/autoload.php',
];

$autoloadOk = false;
foreach ($autoloadPaths as $p) {
  if (file_exists($p)) { require_once $p; $autoloadOk = true; break; }
}
if (!$autoloadOk) jsonOut(500, ['ok'=>false,'error'=>'No se encontró vendor/autoload.php']);

/* ==========================
   2) Session + Auth + DB
========================== */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$auth = $rootDir . '/public/app/controllers/auth/require_login.php';
if (file_exists($auth)) require_once $auth;

require_once __DIR__ . '/../../db/conexion.php';
if (!isset($pdo)) jsonOut(500, ['ok'=>false,'error'=>'No existe $pdo en conexion.php']);

$subidoPor = (string)($_SESSION['asesor']['id_asesor']
  ?? $_SESSION['asesor']['id']
  ?? $_SESSION['asesor_id']
  ?? $_SESSION['user_id']
  ?? ''
);

/* ==========================
   3) Cargar .env
========================== */
$dotenv = null;
if (file_exists($rootDir . '/.env')) {
  $dotenv = Dotenv::createImmutable($rootDir);
} elseif (file_exists($publicDir . '/config/.env')) {
  $dotenv = Dotenv::createImmutable($publicDir . '/config');
}
if ($dotenv) { $dotenv->load(); }

/* ==========================
   4) Inputs
========================== */
// ✅ acepta inversion_id (y por compatibilidad solicitud_id)
$inversionId = (int)($_POST['inversion_id'] ?? $_POST['solicitud_id'] ?? 0);
$tipo        = strtoupper(trim((string)($_POST['tipo_documento'] ?? '')));

if ($inversionId <= 0) jsonOut(400, ['ok'=>false,'error'=>'Falta inversion_id']);
if ($tipo === '')      jsonOut(400, ['ok'=>false,'error'=>'Falta tipo_documento']);

$tiposValidos = [
  'INE',
  'COMPROBANTE_DOMICILIO',
  'RFC',
  'E_CUENTA_6M',
  'E_CUENTA_ACTUAL',
  'EVIDENCIA_TRANSFERENCIA',
  'CONTRATO_FIRMADO'
];
if (!in_array($tipo, $tiposValidos, true)) {
  jsonOut(400, ['ok'=>false,'error'=>'tipo_documento inválido']);
}

if (!isset($_FILES['file'])) jsonOut(400, ['ok'=>false,'error'=>'Falta file']);

$f = $_FILES['file'];

/* ====== Mensaje claro para code 1,2,3... ====== */
if (!empty($f['error'])) {
  $map = [
    UPLOAD_ERR_INI_SIZE   => 'El archivo excede upload_max_filesize (php.ini/.user.ini)',
    UPLOAD_ERR_FORM_SIZE  => 'El archivo excede MAX_FILE_SIZE del formulario',
    UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente',
    UPLOAD_ERR_NO_FILE    => 'No se recibió archivo',
    UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal (upload_tmp_dir)',
    UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir en disco',
    UPLOAD_ERR_EXTENSION  => 'Una extensión detuvo la subida',
  ];
  $code = (int)$f['error'];
  $msg  = $map[$code] ?? ('Error de subida (code '.$code.')');
  jsonOut(400, ['ok'=>false,'error'=>$msg,'code'=>$code]);
}

$tmp  = (string)($f['tmp_name'] ?? '');
$name = (string)($f['name'] ?? 'archivo.pdf');
$size = (int)($f['size'] ?? 0);

if ($tmp === '' || !is_uploaded_file($tmp)) jsonOut(400, ['ok'=>false,'error'=>'Archivo temporal inválido']);
if ($size <= 0) jsonOut(400, ['ok'=>false,'error'=>'Archivo vacío']);

// ✅ Solo PDF por extensión
$ext = extFromName($name);
if ($ext !== 'pdf') jsonOut(400, ['ok'=>false,'error'=>'Solo se permiten PDF']);

// ✅ MIME real
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = (string)$finfo->file($tmp);
if (!in_array($mime, ['application/pdf','application/x-pdf'], true)) {
  jsonOut(400, ['ok'=>false,'error'=>'El archivo no parece PDF (mime: '.$mime.')']);
}

// ✅ Max 10MB (tu regla interna)
$max = (int)(2.5 * 1024 * 1024); // 2.5MB
if ($size > $max) jsonOut(400, ['ok'=>false,'error'=>'El PDF supera el máximo permitido de 2.5 MB']);



/* ==========================
   5) AWS Config
========================== */
$region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['AWS_REGION'] ?? 'us-east-2';
$bucket = $_ENV['S3_BUCKET'] ?? $_ENV['AWS_BUCKET'] ?? '';
$keyId  = $_ENV['AWS_ACCESS_KEY_ID'] ?? '';
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '';

if (!$bucket || !$keyId || !$secret) {
  jsonOut(500, ['ok'=>false,'error'=>'Faltan variables AWS en .env (bucket/key/secret)']);
}

/* ==========================
   6) Versión (documentos_inv)
========================== */
try {
  $stV = $pdo->prepare("
    SELECT COALESCE(MAX(version),0)
    FROM documentos_inv
    WHERE inversion_id = ? AND tipo_documento = ?
  ");
  $stV->execute([$inversionId, $tipo]);
  $version = ((int)$stV->fetchColumn()) + 1;
} catch (Throwable $t) {
  jsonOut(500, ['ok'=>false,'error'=>'Error consultando versión: '.$t->getMessage()]);
}

/* ==========================
   7) Subir a S3
========================== */
$s3 = new S3Client([
  'version' => 'latest',
  'region'  => $region,
  'credentials' => ['key'=>$keyId,'secret'=>$secret],
]);

$safeOriginal = cleanName(pathinfo($name, PATHINFO_FILENAME)) . '.pdf';

$key = sprintf(
  'inversiones/%d/%s/v%d_%s',
  $inversionId,
  $tipo,
  $version,
  $safeOriginal
);

try {
  $s3->putObject([
    'Bucket'      => $bucket,
    'Key'         => $key,
    'SourceFile'  => $tmp,
    'ContentType' => 'application/pdf',
  ]);
} catch (AwsException $e) {
  jsonOut(500, ['ok'=>false,'error'=>'AWS S3: '.$e->getAwsErrorMessage()]);
}

/* URL presigned */
$downloadUrl = '';
try {
  $cmd = $s3->getCommand('GetObject', ['Bucket'=>$bucket,'Key'=>$key]);
  $request = $s3->createPresignedRequest($cmd, '+30 minutes');
  $downloadUrl = (string)$request->getUri();
} catch (Throwable $e) {
  $downloadUrl = '';
}

/* ==========================
   8) BD: UPSERT documentos_inv
   Requiere UNIQUE(inversion_id, tipo_documento)
========================== */
try {
  $filename    = basename($key);
  $provider    = 's3';
  $rutaArchivo = $key;

  $sql = "
    INSERT INTO documentos_inv
      (inversion_id, tipo_documento, filename, nombre_archivo, ruta_archivo,
       mime_type, tamano_bytes, subido_por, fecha_subida, version, notas,
       storage_key, storage_provider)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
      filename         = VALUES(filename),
      nombre_archivo   = VALUES(nombre_archivo),
      ruta_archivo     = VALUES(ruta_archivo),
      mime_type        = VALUES(mime_type),
      tamano_bytes     = VALUES(tamano_bytes),
      subido_por       = VALUES(subido_por),
      fecha_subida     = NOW(),
      version          = VALUES(version),
      notas            = VALUES(notas),
      storage_key      = VALUES(storage_key),
      storage_provider = VALUES(storage_provider)
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    $inversionId,
    $tipo,
    $filename,
    $safeOriginal,
    $rutaArchivo,
    $mime,
    $size,
    $subidoPor,
    $version,
    null,
    $key,
    $provider
  ]);

  // Nota: si fue UPDATE, lastInsertId puede regresar 0, no pasa nada
  $id = (int)($pdo->lastInsertId() ?: 0);

} catch (Throwable $t) {
  jsonOut(500, ['ok'=>false,'error'=>'BD: '.$t->getMessage(), 'storage_key'=>$key]);
}

/* ==========================
   9) OK
========================== */
jsonOut(200, [
  'ok'            => true,
  'id'            => $id,
  'inversion_id'  => $inversionId,
  'tipo_documento'=> $tipo,
  'version'       => $version,
  'storage_key'   => $key,
  'download_url'  => $downloadUrl,
]);
