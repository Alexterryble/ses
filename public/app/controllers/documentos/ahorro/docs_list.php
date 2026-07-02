<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../../db/conexion.php';
require_once __DIR__ . '/../../auth/require_login.php';

try {
  $solicitudId = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
  if ($solicitudId <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'Falta solicitud_id'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $latest = isset($_GET['latest']) ? (int)$_GET['latest'] : 1; // default: 1

  if ($latest === 1) {
    // ✅ solo el último (mayor versión) por tipo_documento
    $sql = "
      SELECT d.*
      FROM documentos_ahorro d
      INNER JOIN (
        SELECT tipo_documento, MAX(version) AS max_version
        FROM documentos_ahorro
        WHERE solicitud_id = ?
        GROUP BY tipo_documento
      ) x
        ON x.tipo_documento = d.tipo_documento
       AND x.max_version = d.version
      WHERE d.solicitud_id = ?
      ORDER BY d.tipo_documento ASC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([$solicitudId, $solicitudId]);
  } else {
    // histórico completo
    $st = $pdo->prepare("
      SELECT *
      FROM documentos_ahorro
      WHERE solicitud_id = ?
      ORDER BY tipo_documento ASC, version DESC, fecha_subida DESC
    ");
    $st->execute([$solicitudId]);
  }

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);
  echo json_encode(['ok'=>true,'rows'=>$rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
