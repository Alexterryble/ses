<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

function out(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

/* -------- Paths / Autoload -------- */
$here      = __DIR__;
$publicDir = dirname($here, 3);
$rootDir   = dirname($here, 4);

$autoloadPaths = [
  $rootDir . '/vendor/autoload.php',
  $publicDir . '/vendor/autoload.php',
];

$ok = false;
foreach ($autoloadPaths as $p) {
  if (file_exists($p)) { require_once $p; $ok = true; break; }
}
if (!$ok) out(500, ['success'=>false,'message'=>'No se encontró vendor/autoload.php']);

/* -------- DB (PDO $pdo) -------- */
require_once __DIR__ . '/../../db/conexion.php';
if (!isset($pdo)) out(500, ['success'=>false,'message'=>'No existe $pdo en conexion.php']);

/* -------- Cargar .env -------- */
$dotenv = null;
if (file_exists($rootDir . '/.env')) {
  $dotenv = Dotenv::createImmutable($rootDir);
} elseif (file_exists($publicDir . '/config/.env')) {
  $dotenv = Dotenv::createImmutable($publicDir . '/config');
}
if ($dotenv) $dotenv->load();

/* -------- Input -------- */
$inversionId = (int)($_GET['inversion_id'] ?? $_GET['solicitud_id'] ?? 0);
if ($inversionId <= 0) out(400, ['success'=>false,'message'=>'Falta inversion_id']);

/* -------- Detectar nombre de FK en documentos_inv -------- */
try {
  $cols = $pdo->query("SHOW COLUMNS FROM documentos_inv")->fetchAll(PDO::FETCH_ASSOC);
  $names = array_map(fn($c) => $c['Field'], $cols);

  // prioridad: inversion_id, luego solicitud_id
  if (in_array('inversion_id', $names, true)) $fk = 'inversion_id';
  elseif (in_array('solicitud_id', $names, true)) $fk = 'solicitud_id';
  else out(500, ['success'=>false,'message'=>'documentos_inv no tiene inversion_id ni solicitud_id']);
} catch (Throwable $t) {
  out(500, ['success'=>false,'message'=>'No se pudo leer columnas de documentos_inv: '.$t->getMessage()]);
}

/* -------- AWS -------- */
$region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['AWS_REGION'] ?? 'us-east-2';
$bucket = $_ENV['S3_BUCKET'] ?? $_ENV['AWS_BUCKET'] ?? '';
$keyId  = $_ENV['AWS_ACCESS_KEY_ID'] ?? '';
$secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '';

$s3 = null;
if ($bucket && $keyId && $secret) {
  $s3 = new S3Client([
    'version' => 'latest',
    'region'  => $region,
    'credentials' => ['key'=>$keyId, 'secret'=>$secret],
  ]);
}

try {
  $sql = "
    SELECT
      id,
      $fk AS inversion_id,
      tipo_documento,
      filename,
      nombre_archivo,
      ruta_archivo,
      mime_type,
      tamano_bytes,
      version,
      fecha_subida,
      storage_key,
      storage_provider
    FROM documentos_inv
    WHERE $fk = ?
    ORDER BY fecha_subida DESC, version DESC
  ";

  $st = $pdo->prepare($sql);
  $st->execute([$inversionId]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  foreach ($rows as &$r) {
    $r['download_url'] = null;

    if ($s3 && !empty($r['storage_key'])) {
      $cmd = $s3->getCommand('GetObject', [
        'Bucket' => $bucket,
        'Key'    => $r['storage_key'],
      ]);
      $req = $s3->createPresignedRequest($cmd, '+15 minutes');
      $r['download_url'] = (string)$req->getUri();
    }
  }
  unset($r);

  out(200, ['success'=>true, 'documentos'=>$rows]);

} catch (AwsException $e) {
  out(500, ['success'=>false,'message'=>'AWS: '.$e->getAwsErrorMessage()]);
} catch (Throwable $t) {
  error_log('[listar_documentos_inver] '.$t->getMessage());
  out(500, ['success'=>false,'message'=>$t->getMessage()]);
}
