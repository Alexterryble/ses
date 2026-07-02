<?php
// public/app/controllers/amortizacion/guardar_firma.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  require_once __DIR__ . '/../../db/conexion.php';
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Conexión PDO no disponible');
  }
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ===== Entrada =====
  $sid = (int)($_POST['solicitud_id'] ?? 0);
  if ($sid <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>'solicitud_id inválido']);
    exit;
  }

  // Flag opcional para borrar firma explícitamente
  $clearFirma = isset($_POST['clear_firma']) && $_POST['clear_firma'] === '1';

  // Firma (opcional)
  $raw       = (string)($_POST['firma_dataurl'] ?? '');
  $firmaMime = null;
  $firmaBlob = null;

  if ($clearFirma) {
    // Borrar firma -> forzamos NULL
    $firmaMime = null;
    $firmaBlob = null;
  } else if ($raw !== '') {
    // Acepta data:image/...;base64,XXXX
    if (preg_match('#^data:(image/[a-zA-Z0-9.+-]+);base64,(.+)$#s', $raw, $m)) {
      $firmaMime = $m[1];
      $decoded   = base64_decode($m[2], true);

      // Límite razonable (2 MB) y tamaño mínimo (>20 bytes)
      $maxBytes = 2 * 1024 * 1024;
      if ($decoded !== false && strlen($decoded) > 20 && strlen($decoded) <= $maxBytes) {
        $firmaBlob = $decoded;
      } else {
        // Firma inválida o demasiado grande: ignorar sin error
        $firmaMime = null;
        $firmaBlob = null;
      }
    } else {
      // No viene con formato válido dataurl: ignorar sin error
      $firmaMime = null;
      $firmaBlob = null;
    }
  }

  // ===== UPSERT =====
  // Requiere UNIQUE KEY en amortizacion(solicitud_id)
  //
  // Reglas:
  // - fecha_emision: la fija el servidor en el INSERT usando CURDATE()
  //                  y NO se vuelve a tocar (salvo que estuviera NULL).
  // - firma: solo se actualiza si mandas nueva (dataurl válida) o clear_firma=1.
  $sql = "
    INSERT INTO amortizacion (solicitud_id, fecha_emision, firma_mime, firma_blob)
    VALUES (:sid, CURDATE(), :mime, :blob)
    ON DUPLICATE KEY UPDATE
      -- No tocar fecha si ya existe (pero si hubiera NULL por registros viejos, la fijamos)
      fecha_emision = IFNULL(fecha_emision, VALUES(fecha_emision)),
      -- Actualizar firma únicamente si mandas algo nuevo o si la estás borrando
      firma_mime    = COALESCE(VALUES(firma_mime), firma_mime),
      firma_blob    = COALESCE(VALUES(firma_blob), firma_blob)
  ";

  $st = $pdo->prepare($sql);
  $st->bindValue(':sid', $sid, PDO::PARAM_INT);

  // Bind seguro para NULL/LOB
  if ($firmaMime === null) { $st->bindValue(':mime', null, PDO::PARAM_NULL); }
  else                     { $st->bindValue(':mime', $firmaMime, PDO::PARAM_STR); }

  if ($firmaBlob === null) { $st->bindValue(':blob', null, PDO::PARAM_NULL); }
  else                     { $st->bindValue(':blob', $firmaBlob, PDO::PARAM_LOB); }

  $st->execute();

  // Respuesta (estado actual)
  $st2 = $pdo->prepare("
    SELECT solicitud_id, fecha_emision, firma_mime, (firma_blob IS NOT NULL) AS tiene_firma
    FROM amortizacion
    WHERE solicitud_id = :sid
    LIMIT 1
  ");
  $st2->execute([':sid' => $sid]);
  $row = $st2->fetch(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'amortizacion' => [
      'solicitud_id'  => (int)($row['solicitud_id'] ?? $sid),
      'fecha_emision' => (string)($row['fecha_emision'] ?? ''), // fijada en el INSERT con CURDATE()
      'firma_mime'    => $row['firma_mime'] ?? null,
      'tiene_firma'   => (bool)($row['tiene_firma'] ?? false),
    ],
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
