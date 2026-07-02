<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

try {
  /* =========================
     DB + Auth + Composer
  ========================= */
  require_once __DIR__ . '/../../../db/conexion.php';
  require_once __DIR__ . '/../../auth/require_login.php';
  require_once __DIR__ . '/../../../../../vendor/autoload.php';

  /* =========================
     ✅ Cargar .env (public/config/.env)
     Controller: public/app/controllers/documentos/ahorro/docs_upload.php
  ========================= */
  $projectRoot = dirname(__DIR__, 4);      // .../public
  $envDir      = $projectRoot . '/config'; // .../public/config
  $envFile     = $envDir . '/.env';

  if (is_dir($envDir) && file_exists($envFile)) {
    $dotenv = Dotenv\Dotenv::createImmutable($envDir);
    $dotenv->load();
  }

  /* =========================
     Helpers
  ========================= */
  $env = function(string $key, $default = null) {
    $v = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
    if ($v === false || $v === null || $v === '') return $default;
    return $v;
  };

  $fail = function(string $msg, int $code = 400) {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
  };

  if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $fail('Método no permitido', 405);
  }

  /* =========================
     Inputs
  ========================= */
  $solicitudId   = isset($_POST['solicitud_id']) ? (int)$_POST['solicitud_id'] : 0;
  $tipoDocumento = strtoupper(trim((string)($_POST['tipo_documento'] ?? '')));

  if ($solicitudId <= 0) $fail('Falta solicitud_id');
  if ($tipoDocumento === '') $fail('Falta tipo_documento');

  $permitidos = ['INE','DOMICILIO','RFC','ESTADO_CUENTA','CONTRATO'];
  if (!in_array($tipoDocumento, $permitidos, true)) {
    $fail('tipo_documento no válido');
  }

  /* =========================
     Archivo
  ========================= */
  if (!isset($_FILES['archivo'])) $fail('Falta archivo');

  $f = $_FILES['archivo'];
  if (!isset($f['error']) || (int)$f['error'] !== UPLOAD_ERR_OK) {
    $fail('Error al subir archivo (código ' . (int)($f['error'] ?? -1) . ')');
  }

  $tmp  = (string)($f['tmp_name'] ?? '');
  $name = (string)($f['name'] ?? '');
  $size = (int)($f['size'] ?? 0);

  if ($tmp === '' || !is_uploaded_file($tmp)) $fail('Archivo temporal inválido');

  // ✅ Límite 2.5MB
  $maxBytes = (int) floor(2.5 * 1024 * 1024);
  if ($size > $maxBytes) $fail('El PDF excede 2.5 MB', 413);

  // ✅ MIME real
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = (string)($finfo->file($tmp) ?: '');
  if ($mime !== 'application/pdf') {
    $fail('Solo se permite PDF (mime detectado: ' . ($mime ?: 'desconocido') . ')');
  }

  /* =========================
     ✅ ENV AWS/S3
  ========================= */
  $bucket = (string)$env('S3_BUCKET', '');
  $region = (string)$env('AWS_DEFAULT_REGION', (string)$env('AWS_REGION', ''));
  $keyId  = (string)$env('AWS_ACCESS_KEY_ID', '');
  $secret = (string)$env('AWS_SECRET_ACCESS_KEY', '');

  if ($bucket === '') $fail('Falta S3_BUCKET en env');
  if ($region === '') $fail('Falta AWS_DEFAULT_REGION (o AWS_REGION) en env');
  if ($keyId === '' || $secret === '') $fail('Faltan credenciales AWS');

  $prefix = (string)$env('AWS_S3_PREFIX', 'Ahorros/');
  if ($prefix !== '' && substr($prefix, -1) !== '/') $prefix .= '/';

  if (!isset($pdo)) $fail('No se encontró conexión $pdo');

  /* =========================
     Key S3 (sin nextVersion)
  ========================= */
  $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $name) ?: 'documento.pdf';
  if (!str_ends_with(strtolower($safeOriginal), '.pdf')) $safeOriginal .= '.pdf';

  $timestamp = date('Ymd_His');
  $s3Key = $prefix . $solicitudId . '/' . $tipoDocumento . "/{$timestamp}_{$safeOriginal}";

  /* =========================
     Subir a S3
  ========================= */
  $s3 = new Aws\S3\S3Client([
    'version'     => 'latest',
    'region'      => $region,
    'credentials' => ['key' => $keyId, 'secret' => $secret],
  ]);

  $s3->putObject([
    'Bucket'      => $bucket,
    'Key'         => $s3Key,
    'SourceFile'  => $tmp,
    'ContentType' => 'application/pdf',
  ]);

  /* =========================
     Guardar DB (UNIQUE: solicitud_id + tipo_documento)
  ========================= */
  $subidoPor   = (string)($_SESSION['user_name'] ?? ($_SESSION['asesor']['nombre'] ?? 'sistema'));
  $rutaArchivo = 's3://' . $bucket . '/' . $s3Key;

  $sql = "
  INSERT INTO documentos_ahorro
    (solicitud_id, tipo_documento, filename, nombre_archivo,
     ruta_archivo, mime_type, tamano_bytes, subido_por,
     version, storage_key, storage_provider)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
  ON DUPLICATE KEY UPDATE
    filename = VALUES(filename),
    nombre_archivo = VALUES(nombre_archivo),
    ruta_archivo = VALUES(ruta_archivo),
    mime_type = VALUES(mime_type),
    tamano_bytes = VALUES(tamano_bytes),
    subido_por = VALUES(subido_por),
    version = version + 1,
    storage_key = VALUES(storage_key),
    storage_provider = VALUES(storage_provider),
    fecha_subida = CURRENT_TIMESTAMP
  ";

  $ins = $pdo->prepare($sql);
  $ins->execute([
    $solicitudId,
    $tipoDocumento,
    $safeOriginal,
    $safeOriginal,
    $rutaArchivo,
    'application/pdf',
    $size,
    $subidoPor,
    $s3Key,
    's3'
  ]);

  // ✅ obtener el registro actual (id + version real) aunque haya sido UPDATE
  $st = $pdo->prepare("SELECT id, version FROM documentos_ahorro WHERE solicitud_id=? AND tipo_documento=? LIMIT 1");
  $st->execute([$solicitudId, $tipoDocumento]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  $docId   = (int)($row['id'] ?? 0);
  $version = (int)($row['version'] ?? 1);

  echo json_encode([
    'ok'      => true,
    'id'      => $docId,
    'version' => $version,
    'bucket'  => $bucket,
    'key'     => $s3Key,
    'ruta'    => $rutaArchivo
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'Error interno: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
