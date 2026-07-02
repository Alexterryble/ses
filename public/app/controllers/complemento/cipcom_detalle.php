<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

try {
  require_once __DIR__ . '/../../db/conexion.php';

  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo
      : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Sin conexión PDO'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

  // ✅ asesor actual (fallbacks)
  $asesorId =
      ($_SESSION['asesor']['id_asesor'] ?? null)
   ?: ($_SESSION['asesor']['id'] ?? null)
   ?: ($_SESSION['asesor_id'] ?? null)
   ?: ($_SESSION['user_id'] ?? null)
   ?: ($_SESSION['usuario_id'] ?? null)
   ?: ($_SESSION['id_asesor'] ?? null);

  if ($asesorId === null) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado: falta asesor_id en sesión'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $asesorId = (int)$asesorId;

  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'ID inválido'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ✅ Si es 7, ve TODO; si no, solo sus registros
  $sql = "
    SELECT
      id, asesor_id,
      nombre_completo, rfc, beneficiario,
      firma_contrato,
      ingreso_capital, aportacion_mensual, pension_base, rendimiento_rate,
      resumen_rendimiento, resumen_capital, resumen_total,
      tabla_aportaciones, tabla_imss,
      created_at, updated_at
    FROM cipcom
    WHERE id = :id
  ";

  if ($asesorId !== 7) {
    $sql .= " AND asesor_id = :asesor_id ";
  }

  $sql .= " LIMIT 1 ";

  $st = $db->prepare($sql);
  $st->bindValue(':id', $id, PDO::PARAM_INT);

  if ($asesorId !== 7) {
    $st->bindValue(':asesor_id', $asesorId, PDO::PARAM_INT);
  }

  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'No se encontró el registro (o no tienes permiso)'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode(['ok' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
