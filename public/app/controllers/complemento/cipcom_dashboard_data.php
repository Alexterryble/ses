<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function bindUsed(PDOStatement $st, array $params): void {
  $sql = $st->queryString;
  foreach ($params as $k => $v) {
    if (strpos($sql, $k) === false) continue;
    $type = ($k === ':asesor_id') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $st->bindValue($k, $v, $type);
  }
}

try {
  require_once __DIR__ . '/../../db/conexion.php';

  $db = (isset($pdo) && $pdo instanceof PDO) ? $pdo
      : ((isset($conn) && $conn instanceof PDO) ? $conn : null);

  if (!$db) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Sin conexión PDO']);
    exit;
  }

  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

  $asesorId =
      $_SESSION['asesor']['id_asesor'] ?? null
   ?: $_SESSION['asesor']['id'] ?? null
   ?: $_SESSION['asesor_id'] ?? null
   ?: $_SESSION['user_id'] ?? null
   ?: $_SESSION['usuario_id'] ?? null
   ?: $_SESSION['id_asesor'] ?? null;

  if ($asesorId === null) {
    http_response_code(401);
    echo json_encode([
      'ok'=>false,
      'error'=>'No autenticado: falta asesor_id en sesión'
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $asesorId = (int)$asesorId;

  $q = trim((string)($_GET['q'] ?? ''));
  $page = max(1, (int)($_GET['page'] ?? 1));
  $pageSize = min(50, max(5, (int)($_GET['page_size'] ?? 15)));
  $offset = ($page - 1) * $pageSize;

  $monthsBack = (int)($_GET['months'] ?? 6);
  if ($monthsBack < 1) $monthsBack = 6;
  if ($monthsBack > 24) $monthsBack = 24;

  $where = "asesor_id = :asesor_id";
  $params = [':asesor_id' => $asesorId];

  if ($q !== '') {
    $like = "%{$q}%";
    $where .= " AND (nombre_completo LIKE :q1 OR rfc LIKE :q2 OR beneficiario LIKE :q3)";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
  }

  $currentYear = (int)date('Y');
  $prevYear = $currentYear - 1;

  // TOTAL FILAS
  $st = $db->prepare("SELECT COUNT(*) FROM cipcom WHERE {$where}");
  bindUsed($st, $params);
  $st->execute();
  $totalRows = (int)$st->fetchColumn();

  // KPIs
  $sqlStats = "
    SELECT
      COUNT(*) AS total_registros,
      COUNT(DISTINCT rfc) AS total_usuarios,
      COALESCE(SUM(CASE WHEN YEAR(created_at)=:y  THEN ingreso_capital ELSE 0 END),0) AS ingreso_anio_actual,
      COALESCE(SUM(CASE WHEN YEAR(created_at)=:py THEN ingreso_capital ELSE 0 END),0) AS ingreso_anio_anterior,
      COALESCE(SUM(CASE WHEN YEAR(created_at)=:y  THEN resumen_total   ELSE 0 END),0) AS recuperacion_anio_actual
    FROM cipcom
    WHERE {$where}
  ";
  $st = $db->prepare($sqlStats);
  bindUsed($st, $params);
  $st->bindValue(':y',  $currentYear, PDO::PARAM_INT);
  $st->bindValue(':py', $prevYear, PDO::PARAM_INT);
  $st->execute();
  $stats = $st->fetch(PDO::FETCH_ASSOC) ?: [];

  // SUMA INGRESOS POR AÑO
  $sqlYears = "
    SELECT
      YEAR(created_at) AS anio,
      COALESCE(SUM(ingreso_capital),0) AS ingreso_total,
      COUNT(DISTINCT rfc) AS usuarios,
      COUNT(*) AS registros
    FROM cipcom
    WHERE {$where}
    GROUP BY YEAR(created_at)
    ORDER BY anio DESC
    LIMIT 8
  ";
  $st = $db->prepare($sqlYears);
  bindUsed($st, $params);
  $st->execute();
  $years = $st->fetchAll(PDO::FETCH_ASSOC);

  // CHART
  $dtStart = new DateTime(date('Y-m-01'));
  $dtStart->modify('-' . ($monthsBack - 1) . ' months');
  $startDate = $dtStart->format('Y-m-d');

  $sqlChart = "
    SELECT
      DATE_FORMAT(created_at, '%Y-%m') AS ym,
      COUNT(DISTINCT rfc) AS clientes
    FROM cipcom
    WHERE {$where}
      AND created_at >= :start_date
    GROUP BY ym
    ORDER BY ym ASC
  ";
  $st = $db->prepare($sqlChart);
  bindUsed($st, $params);
  $st->bindValue(':start_date', $startDate, PDO::PARAM_STR);
  $st->execute();
  $chartRows = $st->fetchAll(PDO::FETCH_ASSOC);

  $map = [];
  foreach ($chartRows as $r) {
    $map[(string)$r['ym']] = (int)$r['clientes'];
  }

  $labels = [];
  $values = [];
  $dt = new DateTime(date('Y-m-01'));
  $dt->modify('-' . ($monthsBack - 1) . ' months');

  for ($i = 0; $i < $monthsBack; $i++) {
    $ym = $dt->format('Y-m');
    $labels[] = $ym;
    $values[] = $map[$ym] ?? 0;
    $dt->modify('+1 month');
  }

  // LISTA paginada
  $sqlRows = "
    SELECT
      id,
      asesor_id,
      nombre_completo,
      rfc,
      beneficiario,
      ingreso_capital,
      pension_base,
      rendimiento_rate,
      resumen_total,
      created_at,
      updated_at,
      COALESCE(estatus, 'activo') AS estatus,
      cancelado_at,
      cancelado_por,
      motivo_cancelacion
    FROM cipcom
    WHERE {$where}
    ORDER BY created_at DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $db->prepare($sqlRows);
  bindUsed($st, $params);
  $st->bindValue(':lim', $pageSize, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // RECIENTES
  $sqlRecent = "
    SELECT
      id,
      nombre_completo,
      rfc,
      created_at,
      COALESCE(estatus, 'activo') AS estatus
    FROM cipcom
    WHERE {$where}
    ORDER BY created_at DESC
    LIMIT 5
  ";
  $st = $db->prepare($sqlRecent);
  bindUsed($st, $params);
  $st->execute();
  $recent = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'version' => 'cipcom_dashboard_data_v4',
    'year_now' => $currentYear,
    'year_prev' => $prevYear,
    'page' => $page,
    'page_size' => $pageSize,
    'total_rows' => $totalRows,
    'stats' => $stats,
    'years' => $years,
    'chart' => [
      'labels' => $labels,
      'values' => $values
    ],
    'recent' => $recent,
    'rows' => $rows
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok'=>false,
    'error'=>$e->getMessage()
  ], JSON_UNESCAPED_UNICODE);
}