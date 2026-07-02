<?php
// public/app/controllers/obtener_datos/get_nss.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
  require_once __DIR__ . '/../../db/conexion.php';
  if (!isset($pdo) || !($pdo instanceof PDO)) throw new RuntimeException('PDO no disponible');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Permite buscar por solicitud_id o por cliente_id (cualquiera de los dos)
  $sid = isset($_GET['solicitud_id']) ? (int)$_GET['solicitud_id'] : 0;
  $cid = isset($_GET['cliente_id'])   ? (int)$_GET['cliente_id']   : 0;
  if ($sid <= 0 && $cid <= 0) throw new InvalidArgumentException('Proporciona solicitud_id o cliente_id');

  // Construye WHERE según lo que te pasen
  $where = [];
  $args  = [];
  if ($cid > 0) { $where[] = 'r.cliente_id = :cid';     $args[':cid'] = $cid; }
  if ($sid > 0) { $where[] = 'r.solicitud_id = :sid';   $args[':sid'] = $sid; }
  $whereSql = implode(' OR ', $where);

  // Toma el NSS más reciente (por updated_at)
  $sql = "
    SELECT r.id, r.solicitud_id, r.cliente_id, r.nss,
           r.umas_cotizar, r.salario, r.periodos_cotizar,
           r.fecha_alta, r.fecha_baja, r.updated_at
    FROM retroactivo r
    WHERE $whereSql
    ORDER BY r.updated_at DESC
    LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute($args);
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(['ok'=>true, 'data'=>null], JSON_UNESCAPED_UNICODE);
    exit;
  }

  echo json_encode([
    'ok'   => true,
    'data' => [
      'id'               => (int)$row['id'],
      'solicitud_id'     => (int)$row['solicitud_id'],
      'cliente_id'       => (int)$row['cliente_id'],
      'nss'              => (string)$row['nss'],
      'umas_cotizar'     => (float)$row['umas_cotizar'],
      'salario'          => (float)$row['salario'],
      'periodos_cotizar' => (int)$row['periodos_cotizar'],
      'fecha_alta'       => (string)($row['fecha_alta'] ?? ''),
      'fecha_baja'       => (string)($row['fecha_baja'] ?? ''),
      'updated_at'       => (string)$row['updated_at'],
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
