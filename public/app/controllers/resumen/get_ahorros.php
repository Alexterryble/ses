<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
  if (session_status() === PHP_SESSION_NONE) session_start();

  // ===== PDO robusto (por si tu conexion.php usa $pdo o $conn) =====
  /** @var PDO|null $pdo */
  if (isset($pdo) && $pdo instanceof PDO) $db = $pdo;
  elseif (isset($conn) && $conn instanceof PDO) $db = $conn;
  else {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Conexión PDO no disponible (revisa conexion.php)'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // ====== ASESOR EN SESIÓN (fallback robusto) ======
  $asesor = $_SESSION['asesor'] ?? [];
  $asesorId = (int)(
    $asesor['id_asesor']
    ?? $asesor['id']
    ?? $asesor['asesor_id']
    ?? $_SESSION['asesor_id']
    ?? $_SESSION['user_id']
    ?? 0
  );
  $rol = strtolower((string)($asesor['rol'] ?? 'asesor'));

  if ($asesorId <= 0) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida (sin asesorId).'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  // ====== INPUTS ======
  $q      = trim((string)($_GET['q'] ?? ''));
  $estado = trim((string)($_GET['estado'] ?? ''));
  $page   = max(1, (int)($_GET['page'] ?? 1));
  $per    = min(100, max(5, (int)($_GET['per'] ?? 25)));
  $off    = ($page - 1) * $per;

  $where = [];
  $args  = [];

  // ✅ SOLO ID 7 o admin pueden ver TODOS
  $puedeVerTodos = ($asesorId === 7) || ($rol === 'admin');

  if (!$puedeVerTodos) {
    $where[] = "ah.asesor_id = ?";
    $args[]  = $asesorId;
  }

  if ($estado !== '') {
    $where[] = "ah.estado = ?";
    $args[]  = $estado;
  }

  if ($q !== '') {
    $where[] = "(ah.folio LIKE ? OR ah.nombre LIKE ? OR ah.ap_paterno LIKE ? OR ah.rfc LIKE ?)";
    $like = "%$q%";
    array_push($args, $like, $like, $like, $like);
  }

  $sqlWhere = $where ? ("WHERE " . implode(" AND ", $where)) : "";

  // ====== LISTADO ======
  $sql = "
    SELECT
      ah.id, ah.folio, ah.nombre, ah.ap_paterno, ah.ap_materno,
      ah.rfc, ah.monto_semanal, ah.porcentaje,
      ah.fecha_inicio_ahorro, ah.fecha_devolucion,
      ah.estado, ah.creado_en,
      TRIM(a.nombre) AS asesor_nombre
    FROM ahorro ah
    LEFT JOIN asesores a ON a.id_asesor = ah.asesor_id
    $sqlWhere
    ORDER BY ah.creado_en DESC, ah.id DESC
    LIMIT ?, ?
  ";

  $stmt = $db->prepare($sql);

  $pos = 1;
  foreach ($args as $v) $stmt->bindValue($pos++, $v);

  $stmt->bindValue($pos++, (int)$off, PDO::PARAM_INT);
  $stmt->bindValue($pos++, (int)$per, PDO::PARAM_INT);

  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ====== TOTAL ======
  $stmt2 = $db->prepare("SELECT COUNT(*) c FROM ahorro ah $sqlWhere");
  $stmt2->execute($args);
  $total = (int)($stmt2->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

  echo json_encode([
    'ok' => true,
    'page' => $page,
    'per' => $per,
    'total' => $total,
    'rows' => $rows,
    'debug' => [
      'asesorId' => $asesorId,
      'rol' => $rol,
      'puedeVerTodos' => $puedeVerTodos
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
