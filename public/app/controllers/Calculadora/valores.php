<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../db/conexion.php';

/* ============================================================
   RESPUESTA JSON
============================================================ */

function resp(bool $ok, $data = null, string $msg = ''): void {
  echo json_encode([
    'ok'   => $ok,
    'data' => $data,
    'msg'  => $msg
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   HELPERS GENERALES
============================================================ */

function table_exists(PDO $pdo, string $table): bool {
  $dbRow = $pdo->query('SELECT DATABASE() AS db')->fetch(PDO::FETCH_ASSOC);
  $db = $dbRow['db'] ?? null;

  if (!$db) {
    return false;
  }

  $st = $pdo->prepare("
    SELECT 1
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = :db
      AND TABLE_NAME = :t
    LIMIT 1
  ");

  $st->execute([
    ':db' => $db,
    ':t'  => $table
  ]);

  return (bool)$st->fetch();
}

/**
 * Normaliza porcentaje.
 *
 * Acepta:
 * - 0.101   => 10.1
 * - 10.075  => 10.075
 */
function normalize_pct($v): float {
  $x = (float)$v;

  if (!is_finite($x)) {
    return 0.0;
  }

  return ($x < 1.0) ? $x * 100.0 : $x;
}

/**
 * Normaliza recargo mensual a fracción.
 *
 * Acepta:
 * - 0.0147 => 0.0147
 * - 1.47   => 0.0147
 * - 2.07   => 0.0207
 */
function normalize_recargo_frac($v): float {
  $x = (float)$v;

  if (!is_finite($x) || $x <= 0) {
    return 0.0;
  }

  return ($x > 0.5) ? $x / 100.0 : $x;
}

/**
 * Tasa por fórmula para años sin registro en BD.
 * Devuelve porcentaje, ejemplo: 10.075
 */
function tasa_formula(int $year): float {
  if ($year <= 2022) {
    return 10.075;
  }

  $v = 10.075 + ($year - 2022) * 1.0905625;

  return round($v, 3);
}

/**
 * Detecta columna de INPC:
 * - inpc
 * - valor
 */
function inpc_column_name(PDO $pdo): string {
  static $cache = null;

  if ($cache !== null) {
    return $cache;
  }

  $dbRow = $pdo->query('SELECT DATABASE() AS db')->fetch(PDO::FETCH_ASSOC);
  $db = $dbRow['db'] ?? null;

  if (!$db) {
    throw new RuntimeException('No se pudo detectar la base de datos actual.');
  }

  foreach (['inpc', 'valor'] as $col) {
    $st = $pdo->prepare("
      SELECT 1
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = :db
        AND TABLE_NAME = 'inpc_mensual'
        AND COLUMN_NAME = :c
      LIMIT 1
    ");

    $st->execute([
      ':db' => $db,
      ':c'  => $col
    ]);

    if ($st->fetch()) {
      $cache = $col;
      return $cache;
    }
  }

  throw new RuntimeException("La tabla inpc_mensual no tiene columna 'inpc' ni 'valor'.");
}

/* ============================================================
   HELPERS DE CATÁLOGOS
============================================================ */

function get_uma_topado(PDO $pdo, int $year): ?array {
  if (!table_exists($pdo, 'uma_topado')) {
    return null;
  }

  $st = $pdo->prepare("
    SELECT year, uma, topado
    FROM uma_topado
    WHERE year = ?
    LIMIT 1
  ");

  $st->execute([$year]);

  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    return null;
  }

  return [
    'year'   => (int)$row['year'],
    'uma'    => (float)$row['uma'],
    'topado' => (float)$row['topado']
  ];
}

function get_imss_valores(PDO $pdo, int $year): ?array {
  if (!table_exists($pdo, 'imss_valores')) {
    return null;
  }

  $st = $pdo->prepare("
    SELECT 
      year,
      imss,
      retiro,
      cesantia,
      rcv,
      total
    FROM imss_valores
    WHERE year = ?
    LIMIT 1
  ");

  $st->execute([$year]);

  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    return null;
  }

  return [
    'year'     => (int)$row['year'],
    'imss'     => normalize_pct($row['imss']),
    'retiro'   => normalize_pct($row['retiro']),
    'cesantia' => normalize_pct($row['cesantia']),
    'rcv'      => normalize_pct($row['rcv']),
    'total'    => normalize_pct($row['total'])
  ];
}

/**
 * Prioridad de tasa anual:
 * 1) imss_valores.total
 * 2) porcentajes_anuales.porcentaje
 * 3) tasa_formula()
 */
function get_porcentaje_anual(PDO $pdo, int $year): array {
  $imssVals = get_imss_valores($pdo, $year);

  if ($imssVals !== null) {
    return [
      'year'         => $year,
      'porcentaje'   => $imssVals['total'],
      'source'       => 'imss_valores',
      'imss_valores' => $imssVals
    ];
  }

  if (table_exists($pdo, 'porcentajes_anuales')) {
    $st = $pdo->prepare("
      SELECT year, porcentaje
      FROM porcentajes_anuales
      WHERE year = ?
      LIMIT 1
    ");

    $st->execute([$year]);

    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row && $row['porcentaje'] !== null) {
      return [
        'year'         => (int)$row['year'],
        'porcentaje'   => normalize_pct($row['porcentaje']),
        'source'       => 'porcentajes_anuales',
        'imss_valores' => null
      ];
    }
  }

  return [
    'year'         => $year,
    'porcentaje'   => tasa_formula($year),
    'source'       => 'formula',
    'imss_valores' => null
  ];
}

function get_recargo_vigente(PDO $pdo, string $fecha): ?array {
  if (!table_exists($pdo, 'recargos_mensuales')) {
    return null;
  }

  $st = $pdo->prepare("
    SELECT tasa_mensual
    FROM recargos_mensuales
    WHERE :f BETWEEN effective_from AND COALESCE(effective_to, '9999-12-31')
    ORDER BY effective_from DESC
    LIMIT 1
  ");

  $st->execute([
    ':f' => $fecha
  ]);

  $row = $st->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    return null;
  }

  $raw  = (float)$row['tasa_mensual'];
  $frac = normalize_recargo_frac($raw);
  $pct  = round($frac * 100, 6);

  return [
    'tasa_raw'  => $raw,
    'tasa_frac' => $frac,
    'tasa_pct'  => $pct,
    'fecha'     => $fecha
  ];
}

/* ============================================================
   INPUTS
============================================================ */

$action = $_GET['action'] ?? 'catalogos';

$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

$fecha = $_GET['fecha'] ?? null; // YYYY-MM-DD

if (!$year && $fecha && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
  $year = (int)substr($fecha, 0, 4);
}

$ym     = $_GET['ym']   ?? null; // YYYY-MM
$fromYM = $_GET['from'] ?? null; // YYYY-MM
$toYM   = $_GET['to']   ?? null; // YYYY-MM

try {

  /* ============================================================
     INPC DE UN MES
     /valores.php?action=inpc&ym=2026-05
  ============================================================ */

  if ($action === 'inpc') {
    if (!$ym || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
      resp(false, null, 'Falta ym (YYYY-MM)');
    }

    if (!table_exists($pdo, 'inpc_mensual')) {
      resp(false, null, 'No existe la tabla inpc_mensual');
    }

    $month = $ym . '-01';

    $col = inpc_column_name($pdo);

    $st = $pdo->prepare("
      SELECT $col AS inpc
      FROM inpc_mensual
      WHERE mes = ?
      LIMIT 1
    ");

    $st->execute([$month]);

    $row = $st->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
      resp(false, null, 'Sin INPC para ' . $month);
    }

    resp(true, [
      'mes'  => $month,
      'ym'   => $ym,
      'inpc' => (float)$row['inpc']
    ]);
  }

  /* ============================================================
     INPC POR RANGO
     /valores.php?action=inpc_range&from=2024-12&to=2026-06
  ============================================================ */

  if ($action === 'inpc_range') {
    if (
      !$fromYM ||
      !$toYM ||
      !preg_match('/^\d{4}-\d{2}$/', $fromYM) ||
      !preg_match('/^\d{4}-\d{2}$/', $toYM)
    ) {
      resp(false, null, 'Faltan from/to (YYYY-MM)');
    }

    if (!table_exists($pdo, 'inpc_mensual')) {
      resp(false, null, 'No existe la tabla inpc_mensual');
    }

    if ($fromYM > $toYM) {
      [$fromYM, $toYM] = [$toYM, $fromYM];
    }

    $fromD = $fromYM . '-01';
    $toD   = $toYM   . '-01';

    $col = inpc_column_name($pdo);

    $st = $pdo->prepare("
      SELECT mes, $col AS inpc
      FROM inpc_mensual
      WHERE mes BETWEEN ? AND ?
      ORDER BY mes ASC
    ");

    $st->execute([$fromD, $toD]);

    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $items = [];
    $map = [];

    foreach ($rows as $r) {
      $mes = (string)$r['mes'];
      $ymRow = substr($mes, 0, 7);
      $inpc = (float)$r['inpc'];

      $items[] = [
        'mes'  => $mes,
        'ym'   => $ymRow,
        'inpc' => $inpc
      ];

      $map[$ymRow] = $inpc;
    }

    resp(true, [
      'from'  => $fromYM,
      'to'    => $toYM,
      'items' => $items,
      'map'   => $map
    ]);
  }

  /* ============================================================
     RECARGO VIGENTE POR FECHA
     /valores.php?action=recargo&fecha=2026-06-01
  ============================================================ */

  if ($action === 'recargo') {
    $f = $fecha ?: date('Y-m-d');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f)) {
      resp(false, null, 'Fecha inválida. Usa YYYY-MM-DD');
    }

    $recargo = get_recargo_vigente($pdo, $f);

    if ($recargo === null) {
      resp(false, null, 'Sin tasa de recargo vigente');
    }

    resp(true, $recargo);
  }

  /* ============================================================
     RECARGOS POR RANGO OPTIMIZADO
     /valores.php?action=recargos_range&from=2024-12&to=2026-06
  ============================================================ */

  if ($action === 'recargos_range') {
    if (
      !$fromYM ||
      !$toYM ||
      !preg_match('/^\d{4}-\d{2}$/', $fromYM) ||
      !preg_match('/^\d{4}-\d{2}$/', $toYM)
    ) {
      resp(false, null, 'Faltan from/to (YYYY-MM)');
    }

    if (!table_exists($pdo, 'recargos_mensuales')) {
      resp(false, null, 'No existe la tabla recargos_mensuales');
    }

    if ($fromYM > $toYM) {
      [$fromYM, $toYM] = [$toYM, $fromYM];
    }

    $fromD = $fromYM . '-01';
    $toD   = $toYM   . '-01';

    $st = $pdo->prepare("
      SELECT effective_from, effective_to, tasa_mensual
      FROM recargos_mensuales
      WHERE effective_from <= :toD
        AND COALESCE(effective_to, '9999-12-31') >= :fromD
      ORDER BY effective_from ASC
    ");

    $st->execute([
      ':fromD' => $fromD,
      ':toD'   => $toD
    ]);

    $rangos = $st->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    $items = [];

    $cursor = new DateTime($fromD);
    $end    = new DateTime($toD);

    while ($cursor <= $end) {
      $fechaMes = $cursor->format('Y-m-d');
      $ymRow    = $cursor->format('Y-m');

      $tasaRaw = 0.0;

      foreach ($rangos as $r) {
        $desde = (string)$r['effective_from'];
        $hasta = $r['effective_to'] ? (string)$r['effective_to'] : '9999-12-31';

        if ($fechaMes >= $desde && $fechaMes <= $hasta) {
          $tasaRaw = (float)$r['tasa_mensual'];
          break;
        }
      }

      $tasaFrac = normalize_recargo_frac($tasaRaw);
      $tasaPct  = round($tasaFrac * 100, 6);

      $map[$ymRow] = $tasaPct;

      $items[] = [
        'ym'         => $ymRow,
        'fecha'      => $fechaMes,
        'tasa_raw'   => $tasaRaw,
        'tasa_frac'  => $tasaFrac,
        'tasa_pct'   => $tasaPct
      ];

      $cursor->modify('+1 month');
    }

    resp(true, [
      'from'  => $fromYM,
      'to'    => $toYM,
      'items' => $items,
      'map'   => $map
    ]);
  }

  /* ============================================================
     VALIDACIÓN YEAR
  ============================================================ */

  if (in_array($action, ['uma', 'porcentaje', 'catalogos'], true)) {
    if (!$year) {
      resp(false, null, 'Falta year o fecha');
    }
  }

  /* ============================================================
     UMA
     /valores.php?action=uma&year=2026
  ============================================================ */

  if ($action === 'uma') {
    $uma = get_uma_topado($pdo, (int)$year);

    if ($uma === null) {
      resp(false, null, 'Sin UMA para ese año');
    }

    resp(true, $uma);
  }

  /* ============================================================
     PORCENTAJE ANUAL
     /valores.php?action=porcentaje&year=2026
  ============================================================ */

  if ($action === 'porcentaje') {
    $porcentaje = get_porcentaje_anual($pdo, (int)$year);

    resp(true, [
      'year'         => $porcentaje['year'],
      'porcentaje'   => $porcentaje['porcentaje'],
      'source'       => $porcentaje['source'],
      'imss_valores' => $porcentaje['imss_valores']
    ]);
  }

  /* ============================================================
     CATÁLOGOS
     /valores.php?action=catalogos&year=2026
     /valores.php?action=catalogos&year=2026&fecha=2026-06-01
  ============================================================ */

  if ($action === 'catalogos') {
    $yearInt = (int)$year;

    $uma = get_uma_topado($pdo, $yearInt);

    $porcentaje = get_porcentaje_anual($pdo, $yearInt);

    $fRecargo = $fecha ?: ($yearInt . '-12-31');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fRecargo)) {
      $fRecargo = $yearInt . '-12-31';
    }

    $recargo = get_recargo_vigente($pdo, $fRecargo);

    resp(true, [
      'year'   => $yearInt,

      'uma'    => $uma ? $uma['uma']    : null,
      'topado' => $uma ? $uma['topado'] : null,

      'porcentaje'  => $porcentaje['porcentaje'],
      'tasa_source' => $porcentaje['source'],

      'imss_valores' => $porcentaje['imss_valores'],

      'recargo' => $recargo
    ]);
  }

  resp(false, null, 'Acción no válida: ' . $action);

} catch (Throwable $e) {
  http_response_code(500);

  resp(false, null, 'Error: ' . $e->getMessage());
}