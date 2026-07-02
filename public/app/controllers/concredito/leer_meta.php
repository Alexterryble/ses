<?php
// public/app/controllers/caratula/leer_meta.php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  require_once __DIR__ . '/../../db/conexion.php';

  if (!isset($pdo) || !($pdo instanceof PDO)) {
    throw new RuntimeException('Sin conexión PDO');
  }

  $sid = (int)($_GET['solicitud_id'] ?? 0);

  if ($sid <= 0) {
    throw new Exception('solicitud_id requerido');
  }

  $sql = "
    SELECT
      solicitud_id,
      zona_id,
      zona_label,
      aplicar_zona,
      incluir_seguro,
      fecha_base,
      fecha_limite_pago,
      firma_blob,
      monto_linea_credito,
      monto_total_pagar
    FROM caratula_meta
    WHERE solicitud_id = :sid
    LIMIT 1
  ";

  $st = $pdo->prepare($sql);
  $st->execute([':sid' => $sid]);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode([
      'ok' => true,
      'meta' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $meta = [
    'solicitud_id'        => (int)$row['solicitud_id'],
    'zona_id'             => $row['zona_id'] !== null ? (int)$row['zona_id'] : null,
    'zona_label'          => (string)($row['zona_label'] ?? ''),

    // ✅ NUEVOS CAMPOS
    'aplicar_zona'        => (int)($row['aplicar_zona'] ?? 0),
    'incluir_seguro'      => (int)($row['incluir_seguro'] ?? 0),

    'fecha_base'          => (string)($row['fecha_base'] ?? ''),
    'fecha_limite_pago'   => (string)($row['fecha_limite_pago'] ?? ''),
    'monto_linea_credito' => $row['monto_linea_credito'] !== null ? (float)$row['monto_linea_credito'] : null,
    'monto_total_pagar'   => $row['monto_total_pagar'] !== null ? (float)$row['monto_total_pagar'] : null,
    'firma_dataurl'       => null,
  ];

  if (!empty($row['firma_blob'])) {
    $meta['firma_dataurl'] = 'data:image/png;base64,' . base64_encode($row['firma_blob']);
  }

  echo json_encode([
    'ok' => true,
    'meta' => $meta
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);

  echo json_encode([
    'ok' => false,
    'message' => $e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}