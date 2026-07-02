<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

/* -------- Rutas -------- */
$here      = __DIR__;
$publicDir = dirname($here, 3);
$rootDir   = dirname($here, 4);

// Autoload
$autoloadPaths = [
  $rootDir   . '/vendor/autoload.php',
  $publicDir . '/vendor/autoload.php',
];
$autoloadOk = false;
foreach ($autoloadPaths as $p) {
  if (file_exists($p)) { require_once $p; $autoloadOk = true; break; }
}
if (!$autoloadOk) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'No se encontró vendor/autoload.php']);
  exit;
}

// DB
require_once $publicDir . '/app/db/conexion.php';

// .env
$dotenv = null;
if (file_exists($rootDir . '/.env')) {
  $dotenv = Dotenv::createImmutable($rootDir);
} elseif (file_exists($publicDir . '/config/.env')) {
  $dotenv = Dotenv::createImmutable($publicDir . '/config');
}
if ($dotenv) { $dotenv->load(); }

/* -------- Validaciones -------- */
$solicitud_id   = (int)($_POST['solicitud_id'] ?? 0);
$tipo_documento = $_POST['tipo_documento'] ?? null;

if ($solicitud_id <= 0 || !$tipo_documento || empty($_FILES['archivo'])) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Faltan datos (solicitud_id, tipo_documento, archivo)']);
  exit;
}

$f = $_FILES['archivo'];
if ($f['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
  exit;
}
$mime = mime_content_type($f['tmp_name']);
if ($mime !== 'application/pdf') {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'Solo se permiten PDFs']);
  exit;
}

/* -------- S3 -------- */
$region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['AWS_REGION'] ?? 'us-east-2';
$bucket = $_ENV['S3_BUCKET'] ?? $_ENV['AWS_BUCKET'] ?? '';
$keyId  = $_ENV['AWS_ACCESS_KEY_ID'] ?? '';
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '';

if (!$bucket || !$keyId || !$secret) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Faltan credenciales S3/AWS en .env']);
  exit;
}

$s3 = new S3Client([
  'version' => 'latest',
  'region'  => $region,
  'credentials' => ['key' => $keyId, 'secret' => $secret],
]);

$origName   = basename($f['name']);
$ext        = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$finalName  = $tipo_documento . '_' . date('Ymd_His') . '.' . $ext;
$storageKey = 'solicitudes/' . $solicitud_id . '/' . $finalName;

try {
  // subir a S3
  $s3->putObject([
    'Bucket'      => $bucket,
    'Key'         => $storageKey,
    'Body'        => fopen($f['tmp_name'], 'rb'),
    'ContentType' => 'application/pdf',
    'ACL'         => 'private',
  ]);

  // URL firmada 10 min
  $cmd = $s3->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $storageKey]);
  $req = $s3->createPresignedRequest($cmd, '+10 minutes');
  $presignedUrl = (string)$req->getUri();

  // Guardar en BD
// UPSERT: inserta si no existe, actualiza si ya existe (y sube la versión)
$stmt = $conn->prepare("
  INSERT INTO documentos_solicitud
    (solicitud_id, tipo_documento, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, subido_por, fecha_subida, version, notas)
  VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 1, NULL)
  ON DUPLICATE KEY UPDATE
    nombre_archivo = VALUES(nombre_archivo),
    ruta_archivo   = VALUES(ruta_archivo),
    mime_type      = VALUES(mime_type),
    tamano_bytes   = VALUES(tamano_bytes),
    subido_por     = VALUES(subido_por),
    fecha_subida   = NOW(),
    version        = version + 1,
    notas          = NULL
");

$ok = $stmt->execute([
  $solicitud_id,
  $tipo_documento,
  $origName,
  $storageKey,          // guardas la KEY de S3
  $mime,
  (int)$f['size'],
  'usuario'             // o el usuario en sesión
]);

if (!$ok) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'No se pudo registrar/actualizar el documento en BD']);
  exit;
}


  echo json_encode([
    'success' => true,
    'message' => 'Archivo subido correctamente',
    'doc' => [
      'solicitud_id'   => $solicitud_id,
      'tipo_documento' => $tipo_documento,
      'storage_key'    => $storageKey,
      'download_url'   => $presignedUrl
    ]
  ]);

} catch (AwsException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'AWS: ' . $e->getAwsErrorMessage()]);
} catch (Throwable $t) {
  http_response_code(500);
  error_log('[upload.php] ' . $t->getMessage());
  echo json_encode(['success' => false, 'message' => $t->getMessage()]);
}
