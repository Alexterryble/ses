<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0'); // evita que warnings rompan el JSON

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Dotenv\Dotenv;

/* -------- Resolución de rutas portable (local y Railway) -------- */
$here      = __DIR__;                 // .../public/app/controllers/documentos
$publicDir = dirname($here, 3);       // .../public
$rootDir   = dirname($here, 4);       // .../

// Autoload de composer: preferir raíz, luego public (por si tuvieras un vendor copiado allí)
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

// Conexión a BD (ajusta si tu conexion.php está en otra carpeta)
require_once $publicDir . '/app/db/conexion.php';

/* -------- Cargar .env --------
   - Si tu .env está en la raíz del proyecto -> $rootDir
   - Si está en public/config/.env       -> $publicDir . '/config'
*/
$dotenv = null;
if (file_exists($rootDir . '/.env')) {
  $dotenv = Dotenv::createImmutable($rootDir);
} elseif (file_exists($publicDir . '/config/.env')) {
  $dotenv = Dotenv::createImmutable($publicDir . '/config');
}
if ($dotenv) { $dotenv->load(); }

/* ---------------- Lógica ---------------- */
try {
  $solicitud_id = (int)($_GET['solicitud_id'] ?? 0);
  if ($solicitud_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Falta el ID de solicitud']);
    exit;
  }

  $sql = "SELECT
            tipo_documento,
            ruta_archivo   AS storage_key,
            nombre_archivo,
            mime_type,
            tamano_bytes,
            version,
            fecha_subida
          FROM documentos_solicitud
          WHERE solicitud_id = ?
          ORDER BY fecha_subida DESC, version DESC";
  $stmt = $conn->prepare($sql);
  $stmt->execute([$solicitud_id]);
  $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Presignar S3 si hay credenciales
  $region = $_ENV['AWS_DEFAULT_REGION'] ?? $_ENV['AWS_REGION'] ?? 'us-east-2';
  $bucket = $_ENV['S3_BUCKET'] ?? $_ENV['AWS_BUCKET'] ?? '';
  $keyId  = $_ENV['AWS_ACCESS_KEY_ID'] ?? '';
  $secret = $_ENV['AWS_SECRET_ACCESS_KEY'] ?? '';

  $addUrl = function(array $row) use ($bucket, $region, $keyId, $secret) {
    $row['download_url'] = null;
    if (!$bucket || !$keyId || !$secret) return $row;
    $key = $row['storage_key'] ?? '';
    if ($key === '') return $row;

    $s3 = new S3Client([
      'version' => 'latest',
      'region'  => $region,
      'credentials' => ['key' => $keyId, 'secret' => $secret],
    ]);
    $cmd = $s3->getCommand('GetObject', ['Bucket' => $bucket, 'Key' => $key]);
    $req = $s3->createPresignedRequest($cmd, '+10 minutes');
    $row['download_url'] = (string)$req->getUri();
    return $row;
  };

  $out = array_map($addUrl, $docs);

  echo json_encode(['success' => true, 'documentos' => $out], JSON_UNESCAPED_UNICODE);

} catch (AwsException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'AWS: ' . $e->getAwsErrorMessage()]);
} catch (Throwable $t) {
  http_response_code(500);
  error_log('[listar.php] ' . $t->getMessage());
  echo json_encode(['success' => false, 'message' => $t->getMessage()]);
}
