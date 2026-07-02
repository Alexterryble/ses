<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/require_login.php';
require_once __DIR__ . '/../../db/conexion.php';

try {
  // ====== ASESOR EN SESIÓN ======
  $asesor = $_SESSION['asesor'] ?? [];

  // En producción puede venir como id_asesor, o en local como id / asesor_id
  $asesorId = (int)($asesor['id_asesor'] ?? $asesor['id'] ?? $asesor['asesor_id'] ?? 0);
  $rol = (string)($asesor['rol'] ?? 'asesor');

  if ($asesorId <= 0) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'Sesión inválida (sin asesor_id).']);
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

  // ✅ FILTRO POR ASESOR (si NO es admin)
  if (strtolower($rol) !== 'admin') {
    $where[] = "ah.asesor_id = ?";
    $args[]  = $asesorId;
  }

  // ✅ Si no mandan estado, ocultar cancelados por defecto
if ($estado !== '') {
  $where[] = "LOWER(COALESCE(ah.estado, 'activo')) = ?";
  $args[]  = strtolower($estado);
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
      ah.rfc, ah.correo, ah.telefono,
      ah.monto_semanal, ah.porcentaje,
      ah.fecha_inicio_ahorro, ah.fecha_devolucion,
      ah.estado, ah.creado_en,

      a.nombre    AS asesor_nombre,
      a.email     AS asesor_correo,
      a.telefono  AS asesor_telefono,
      a.direccion AS asesor_direccion,
      a.rfc       AS asesor_rfc

    FROM ahorro ah
    LEFT JOIN asesores a ON a.id_asesor = ah.asesor_id
    $sqlWhere
    ORDER BY ah.creado_en DESC, ah.id DESC
    LIMIT ? OFFSET ?
  ";

  $stmt = $pdo->prepare($sql);

  $pos = 1;
  foreach ($args as $v) {
    $stmt->bindValue($pos++, $v);
  }

  $stmt->bindValue($pos++, (int)$per, PDO::PARAM_INT);
  $stmt->bindValue($pos++, (int)$off, PDO::PARAM_INT);

  $stmt->execute();
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ====== TOTAL ======
  $stmt2 = $pdo->prepare("SELECT COUNT(*) c FROM ahorro ah $sqlWhere");
  $stmt2->execute($args);
  $total = (int)($stmt2->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);

  echo json_encode([
    'ok' => true,
    'page' => $page,
    'per' => $per,
    'total' => $total,
    'rows' => $rows
  ]);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
}