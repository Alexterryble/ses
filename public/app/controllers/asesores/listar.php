<?php
declare(strict_types=1);

ini_set('display_errors','0');
ini_set('log_errors','1');
error_reporting(E_ALL);
set_error_handler(function($s,$m,$f,$l){ throw new ErrorException($m,0,$s,$f,$l); });

header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
  // Sesión (soporta / y /public)
  $uri        = $_SERVER['REQUEST_URI'] ?? '';
  $cookiePath = (strpos($uri, '/public/') !== false) ? '/public' : '/';
  session_set_cookie_params(['path'=>$cookiePath,'httponly'=>true,'samesite'=>'Lax']);
  session_start();

  if (empty($_SESSION['asesor'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'error'=>'No autenticado']);
    ob_end_flush(); exit;
  }

  require_once __DIR__ . '/../../db/conexion.php';
  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo
     : ((isset($conn) && $conn instanceof PDO) ? $conn : null);
  if (!$db) throw new Exception('Sin conexión PDO');
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // Parámetros
  $page    = max(1, (int)($_GET['page'] ?? 1));
  $perPage = (int)($_GET['per_page'] ?? 10);
  if ($perPage < 5 || $perPage > 100) $perPage = 10;

  $q        = trim((string)($_GET['q'] ?? ''));
  $activoIn = $_GET['activo'] ?? null; // '1', '0' ó null
  $activo   = null;
  if ($activoIn === '1' || $activoIn === '0') $activo = (int)$activoIn;

  // WHERE dinámico (usa SOLO columnas reales)
  $where  = [];
  $params = [];

  if ($q !== '') {
    $where[] = "(a.nombre LIKE :q
              OR a.usuario LIKE :q
              OR a.email LIKE :q
              OR a.rfc LIKE :q
              OR a.telefono LIKE :q)";
    $params[':q'] = "%{$q}%";
  }
  if ($activo !== null) {
    $where[] = "a.activo = :activo";
    $params[':activo'] = $activo;
  }
  $whereSql = $where ? 'WHERE '.implode(' AND ',$where) : '';

  // Total
  $sqlTotal = "SELECT COUNT(*) FROM asesores a {$whereSql}";
  $st = $db->prepare($sqlTotal);
  $st->execute($params);
  $total = (int)$st->fetchColumn();

  // Datos
  $offset = ($page - 1) * $perPage;
  $sql = "SELECT
            a.id_asesor               AS id,     -- alias de cortesía
            a.id_asesor,                        -- pk real
            a.nombre,
            a.usuario,
            a.email,
            a.rfc,
            a.telefono,
            a.clabe,
            a.cuenta,
            a.rol,
            a.direccion,
            a.activo
          FROM asesores a
          {$whereSql}
          ORDER BY a.id_asesor DESC
          LIMIT :off, :pp";

  $st = $db->prepare($sql);
  foreach ($params as $k=>$v) $st->bindValue($k,$v);
  $st->bindValue(':off',$offset,PDO::PARAM_INT);
  $st->bindValue(':pp', $perPage,PDO::PARAM_INT);
  $st->execute();

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Derivado opcional
  foreach ($rows as &$r) {
    $r['nombre_completo'] = trim($r['nombre'] ?? '');
    unset($r['password']); // por seguridad, si existiera
  }

  echo json_encode([
    'ok'       => true,
    'data'     => $rows,
    'page'     => $page,
    'per_page' => $perPage,
    'total'    => $total,
    'pages'    => (int)ceil($total / $perPage),
  ]);
  ob_end_flush(); exit;

} catch (Throwable $e) {
  if (ob_get_length()) ob_clean();
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  exit;
}
