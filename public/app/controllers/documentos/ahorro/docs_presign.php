<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db/conexion.php';
require_once __DIR__ . '/../../auth/require_login.php';
require_once __DIR__ . '/../../../../../vendor/autoload.php';

try {
  // ✅ Cargar .env desde /public/config (igual que tu test)
  $envDir = dirname(__DIR__, 4) . '/config'; // .../public/config
  if (is_dir($envDir) && file_exists($envDir . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable($envDir);
    $dotenv->load();
  }

  $env = function(string $key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return $default;
    return $v;
  };

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Falta id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ✅ Buscar doc
  $st = $pdo->prepare("
    SELECT id, solicitud_id, tipo_documento, nombre_archivo, storage_key, storage_provider
    FROM documentos_ahorro
    WHERE id = ?
    LIMIT 1
  ");
  $st->execute([$id]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'Documento no encontrado'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if (($row['storage_provider'] ?? 's3') !== 's3') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'storage_provider no soportado'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $bucket = (string)$env('S3_BUCKET', '');
  $region = (string)$env('AWS_DEFAULT_REGION', (string)$env('AWS_REGION', ''));
  $keyId  = (string)$env('AWS_ACCESS_KEY_ID', '');
  $secret = (string)$env('AWS_SECRET_ACCESS_KEY', '');

  if ($bucket === '') {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Falta S3_BUCKET en env'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  if ($region === '' || $keyId === '' || $secret === '') {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Faltan credenciales/region en env'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $storageKey = (string)($row['storage_key'] ?? '');
  if ($storageKey === '') {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'No hay storage_key para este documento'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ✅ Generar URL firmada
  $s3 = new Aws\S3\S3Client([
    'version'     => 'latest',
    'region'      => $region,
    'credentials' => ['key' => $keyId, 'secret' => $secret],
  ]);

  $cmd = $s3->getCommand('GetObject', [
    'Bucket' => $bucket,
    'Key'    => $storageKey,
    // para que descargue con nombre legible
    'ResponseContentDisposition' => 'inline; filename="' . ($row['nombre_archivo'] ?? 'documento.pdf') . '"',
    'ResponseContentType' => 'application/pdf',
  ]);

  $expires = '+10 minutes';
  $request = $s3->createPresignedRequest($cmd, $expires);
  $signedUrl = (string)$request->getUri();

  echo json_encode(['ok'=>true,'url'=>$signedUrl], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
