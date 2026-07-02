<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
  if (session_status() === PHP_SESSION_NONE) session_start();

  // ===== PDO robusto =====
  /** @var PDO|null $pdo */
  if (isset($pdo) && $pdo instanceof PDO) {
    $db = $pdo;
  } elseif (isset($conn) && $conn instanceof PDO) {
    $db = $conn;
  } else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Conexión PDO no disponible (revisa conexion.php)'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ====== ASESOR EN SESIÓN ======
  $asesor = $_SESSION['asesor'] ?? [];
  $asesorId = (int)(
    $asesor['id_asesor']
    ?? $asesor['id']
    ?? $asesor['asesor_id']
    ?? $_SESSION['asesor_id']
    ?? 0
  );

  if ($asesorId <= 0) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida (sin asesorId).'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ====== INPUTS ======
  $q      = trim((string)($_GET['q'] ?? ''));
  $estado = trim((string)($_GET['estado'] ?? ''));
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $per    = min(200, max(5, (int)($_GET['per'] ?? 25)));
  $off    = ($page - 1) * $per;

  $where = [];
  $args  = [];

  if ($estado !== '') {
    $where[] = "inv.estado = ?";
    $args[]  = $estado;
  }

  if ($q !== '') {
    $where[] = "(inv.folio LIKE ? OR inv.nombre LIKE ? OR inv.ap_paterno LIKE ? OR inv.rfc LIKE ?)";
    $like = "%{$q}%";
    array_push($args, $like, $like, $like, $like);
  }

  $sqlWhere = $where ? ("WHERE " . implode(" AND ", $where)) : "";

  // ====== LISTADO ======
  $sql = "
    SELECT
      inv.id,
      inv.asesor_id,
      inv.folio,
      inv.nombre, inv.ap_paterno, inv.ap_materno,
      inv.rfc,
      inv.monto,
      inv.plazo_anios,
      inv.fecha_solicitud,
      inv.fecha_devolucion,
      inv.estado,
      TRIM(a.nombre) AS asesor_nombre
    FROM inversion inv
    LEFT JOIN asesores a ON a.id_asesor = inv.asesor_id
    $sqlWhere
    ORDER BY inv.fecha_solicitud DESC, inv.id DESC
    LIMIT ?, ?
  ";

  $stmt = $db->prepare($sql);

  $pos = 1;
  foreach ($args as $v) {
    $stmt->bindValue($pos++, $v);
  }

  $stmt->bindValue($pos++, (int)$off, PDO::PARAM_INT);
  $stmt->bindValue($pos++, (int)$per, PDO::PARAM_INT);

  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ====== TOTAL ======
  $stmt2 = $db->prepare("SELECT COUNT(*) c FROM inversion inv $sqlWhere");
  $stmt2->execute($args);
  $total = (int)($stmt2->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

  echo json_encode([
    'ok' => true,
    'page' => $page,
    'per' => $per,
    'total' => $total,
    'rows' => $rows
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
