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

/**
 * Bindea SOLO los params que realmente existan en el SQL (evita HY093).
 */
function bindUsed(PDOStatement $st, array $params): void {
  $sql = $st->queryString;
  foreach ($params as $k => $v) {
    if (strpos($sql, $k) === false) continue;
    $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $st->bindValue($k, $v, $type);
  }
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

  // ====== INPUTS ======
  $q = trim((string)($_GET['q'] ?? ''));

  $page = max(1, (int)($_GET['page'] ?? 1));
  $pageSize = min(100, max(5, (int)($_GET['page_size'] ?? 25)));
  $offset = ($page - 1) * $pageSize;

  $monthsBack = (int)($_GET['months'] ?? 6);
  if ($monthsBack < 1) $monthsBack = 6;
  if ($monthsBack > 24) $monthsBack = 24;

  // ✅ TODOS LOS REGISTROS
  $where = "1=1";
  $params = [];

  if ($q !== '') {
    $like = "%{$q}%";
    $where .= " AND (
      c.nombre_completo LIKE :q1 OR
      c.rfc LIKE :q2 OR
      c.beneficiario LIKE :q3 OR
      a.nombre LIKE :q4
    )";
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
    $params[':q4'] = $like;
  }

  $currentYear = (int)date('Y');
  $prevYear = $currentYear - 1;

  // ====== TOTAL FILAS ======
  $st = $db->prepare("
    SELECT COUNT(*)
    FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
  ");
  bindUsed($st, $params);
  $st->execute();
  $totalRows = (int)$st->fetchColumn();

  // ====== KPIs ======
  $sqlStats = "
    SELECT
      COUNT(*) AS total_registros,
      COUNT(DISTINCT c.rfc) AS total_usuarios,
      COALESCE(SUM(CASE WHEN YEAR(c.created_at)=:y  THEN c.ingreso_capital ELSE 0 END),0) AS ingreso_anio_actual,
      COALESCE(SUM(CASE WHEN YEAR(c.created_at)=:py THEN c.ingreso_capital ELSE 0 END),0) AS ingreso_anio_anterior,
      COALESCE(SUM(CASE WHEN YEAR(c.created_at)=:y  THEN c.resumen_rendimiento ELSE 0 END),0) AS recuperacion_anio_actual
    FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
  ";
  $st = $db->prepare($sqlStats);
  bindUsed($st, $params);
  $st->bindValue(':y',  $currentYear, PDO::PARAM_INT);
  $st->bindValue(':py', $prevYear, PDO::PARAM_INT);
  $st->execute();
  $stats = $st->fetch(PDO::FETCH_ASSOC) ?: [];

  // ====== SUMA INGRESOS POR AÑO ======
  $sqlYears = "
    SELECT
      YEAR(c.created_at) AS anio,
      COALESCE(SUM(c.ingreso_capital),0) AS ingreso_total,
      COUNT(DISTINCT c.rfc) AS usuarios,
      COUNT(*) AS registros
    FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
    GROUP BY YEAR(c.created_at)
    ORDER BY anio DESC
    LIMIT 8
  ";
  $st = $db->prepare($sqlYears);
  bindUsed($st, $params);
  $st->execute();
  $years = $st->fetchAll(PDO::FETCH_ASSOC);

  // ====== CHART (clientes por mes) ======
  $dtStart = new DateTime(date('Y-m-01'));
  $dtStart->modify('-' . ($monthsBack - 1) . ' months');
  $startDate = $dtStart->format('Y-m-d');

  $sqlChart = "
    SELECT
      DATE_FORMAT(c.created_at, '%Y-%m') AS ym,
      COUNT(DISTINCT c.rfc) AS clientes
    FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
      AND c.created_at >= :start_date
    GROUP BY ym
    ORDER BY ym ASC
  ";
  $st = $db->prepare($sqlChart);
  bindUsed($st, $params);
  $st->bindValue(':start_date', $startDate, PDO::PARAM_STR);
  $st->execute();
  $chartRows = $st->fetchAll(PDO::FETCH_ASSOC);

  $map = [];
  foreach ($chartRows as $r) $map[(string)$r['ym']] = (int)$r['clientes'];

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

  // ====== LISTA PAGINADA (YA TRAE ASESOR) ======
$sqlRows = "
  SELECT
    c.id,
    c.asesor_id,
    TRIM(a.nombre) AS asesor_nombre,
    c.nombre_completo,
    c.rfc,
    c.beneficiario,
    c.firma_contrato,
    c.ingreso_capital,
    c.aportacion_mensual,
    c.pension_base,
    c.rendimiento_rate,
    c.resumen_rendimiento,
    c.created_at,
    c.updated_at,

    /* ✅ Campos para detectar cancelados */
    c.estatus,
    c.cancelado_at,
    c.cancelado_por,
    c.motivo_cancelacion
  FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT :lim OFFSET :off
  ";
  $st = $db->prepare($sqlRows);
  bindUsed($st, $params);
  $st->bindValue(':lim', $pageSize, PDO::PARAM_INT);
  $st->bindValue(':off', $offset, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // ====== RECIENTES (YA TRAE ASESOR) ======
$sqlRecent = "
  SELECT
    c.id,
    TRIM(a.nombre) AS asesor_nombre,
    c.nombre_completo,
    c.rfc,
    c.created_at,
    c.estatus,
    c.cancelado_at,
    c.cancelado_por,
    c.motivo_cancelacion
  FROM cipcom c
    LEFT JOIN asesores a ON a.id_asesor = c.asesor_id
    WHERE {$where}
    ORDER BY c.created_at DESC, c.id DESC
    LIMIT 5
  ";
  $st = $db->prepare($sqlRecent);
  bindUsed($st, $params);
  $st->execute();
  $recent = $st->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode([
    'ok' => true,
    'version' => 'cipcom_all_v2_with_asesor',
    'page' => $page,
    'page_size' => $pageSize,
    'total_rows' => $totalRows,
    'stats' => $stats,
    'years' => $years,
    'chart' => ['labels'=>$labels,'values'=>$values],
    'recent' => $recent,
    'rows' => $rows
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
