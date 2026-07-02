<?php
declare(strict_types=1);

/* =====================================================
   CONTRATOS DE INVERSIÓN | DASHBOARD CIP
   Diseño corregido con métricas, gráficas y documentos
===================================================== */

// ✅ sesión
require_once __DIR__ . '/app/controllers/auth/require_login.php';

// ✅ BD
require_once __DIR__ . '/app/db/conexion.php';

// ✅ BASE URL dinámica: funciona en /, /sempiternal/public/, /app/, etc.
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$BASE_URL = ($basePath === '' || $basePath === '.') ? '/' : $basePath . '/';

// ✅ Asesor ID
$asesorId = (int) (
  $_SESSION['asesor']['id_asesor']
  ?? $_SESSION['asesor']['id']
  ?? $_SESSION['asesor_id']
  ?? $_SESSION['user_id']
  ?? 0
);

if ($asesorId <= 0) {
  http_response_code(401);
  die('No autenticado (asesorId vacío)');
}

// ✅ Nombre asesor
$asesorNombreSidebar = '';
if (!empty($_SESSION['asesor']) && is_array($_SESSION['asesor'])) {
  $a = $_SESSION['asesor'];
  $asesorNombreSidebar = trim(
    (string)($a['nombre'] ?? '') . ' ' .
    (string)($a['apellido_paterno'] ?? '') . ' ' .
    (string)($a['apellido_materno'] ?? '')
  );
}

// ✅ Helpers
function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function fmtFecha(?string $sqlDate): string {
  if (!$sqlDate) return '—';
  $ts = strtotime($sqlDate);
  if (!$ts) return $sqlDate;
  return date('d/m/Y H:i', $ts);
}

function fmtFechaCorta(?string $sqlDate): string {
  if (!$sqlDate) return '—';
  $ts = strtotime($sqlDate);
  if (!$ts) return $sqlDate;
  return date('d/m/Y', $ts);
}

function fmtMoney($n): string {
  $v = (float)$n;
  return '$' . number_format($v, 2, '.', ',');
}

function estadoClass(string $estado): string {
  $estado = strtolower(trim($estado));

  return match ($estado) {
    'capturado' => 'estado-capturado',
    'firmado'   => 'estado-firmado',
    'cancelado' => 'estado-cancelado',
    'por vencer'=> 'estado-por-vencer',
    'completo'  => 'estado-completo',
    default     => 'estado-capturado',
  };
}

function faltanDias(?string $fechaDevolucion): ?int {
  if (!$fechaDevolucion) return null;

  $ts = strtotime($fechaDevolucion);
  if (!$ts) return null;

  $hoy = strtotime(date('Y-m-d'));
  return (int)floor(($ts - $hoy) / 86400);
}

function normalizaEstado(string $estado): string {
  $estado = strtolower(trim($estado));
  return $estado !== '' ? $estado : 'capturado';
}

/* =====================================================
   ✅ QUERY PRINCIPAL CORREGIDA
   Detecta columnas reales de la tabla inversion
===================================================== */

try {
  // ✅ Revisar columnas reales de la tabla inversion
  $colsStmt = $pdo->query("DESCRIBE inversion");
  $colsInfo = $colsStmt->fetchAll(PDO::FETCH_ASSOC);

  $cols = [];
  foreach ($colsInfo as $c) {
    $cols[] = $c['Field'];
  }

  // ✅ Validar columna ID
  if (!in_array('id', $cols, true)) {
    throw new Exception("No existe la columna id en la tabla inversion.");
  }

  // ✅ Detectar columna del asesor
  if (in_array('asesor_id', $cols, true)) {
    $asesorColumn = 'asesor_id';
  } elseif (in_array('id_asesor', $cols, true)) {
    $asesorColumn = 'id_asesor';
  } else {
    throw new Exception("No existe la columna asesor_id ni id_asesor en la tabla inversion.");
  }

  // ✅ Detectar columnas opcionales
  $folioSelect = in_array('folio', $cols, true)
    ? "folio"
    : "NULL AS folio";

  $nombreSelect = in_array('nombre', $cols, true)
    ? "nombre"
    : "NULL AS nombre";

  $apPaternoSelect = in_array('ap_paterno', $cols, true)
    ? "ap_paterno"
    : "NULL AS ap_paterno";

  $apMaternoSelect = in_array('ap_materno', $cols, true)
    ? "ap_materno"
    : "NULL AS ap_materno";

  $montoSelect = in_array('monto', $cols, true)
    ? "monto"
    : "0 AS monto";

  // ✅ Detectar columna de plazo
  if (in_array('plazo_anios', $cols, true)) {
    $plazoSelect = "plazo_anios";
  } elseif (in_array('plazo', $cols, true)) {
    $plazoSelect = "plazo AS plazo_anios";
  } else {
    $plazoSelect = "NULL AS plazo_anios";
  }

  // ✅ Detectar fecha de solicitud
  if (in_array('fecha_solicitud', $cols, true)) {
    $fechaSolicitudSelect = "fecha_solicitud";
  } elseif (in_array('created_at', $cols, true)) {
    $fechaSolicitudSelect = "created_at AS fecha_solicitud";
  } elseif (in_array('fecha_creacion', $cols, true)) {
    $fechaSolicitudSelect = "fecha_creacion AS fecha_solicitud";
  } else {
    $fechaSolicitudSelect = "NULL AS fecha_solicitud";
  }

  // ✅ Detectar fecha devolución
  if (in_array('fecha_devolucion', $cols, true)) {
    $fechaDevolucionSelect = "fecha_devolucion";
  } elseif (in_array('fecha_vencimiento', $cols, true)) {
    $fechaDevolucionSelect = "fecha_vencimiento AS fecha_devolucion";
  } else {
    $fechaDevolucionSelect = "NULL AS fecha_devolucion";
  }

  // ✅ Detectar estado
  $estadoSelect = in_array('estado', $cols, true)
    ? "estado"
    : "'capturado' AS estado";

  // ✅ Query final compatible
  $sql = "SELECT
            id,
            $folioSelect,
            $nombreSelect,
            $apPaternoSelect,
            $apMaternoSelect,
            $montoSelect,
            $plazoSelect,
            $fechaSolicitudSelect,
            $fechaDevolucionSelect,
            $estadoSelect
          FROM inversion
          WHERE $asesorColumn = :asesor_id
          ORDER BY id DESC";

  $st = $pdo->prepare($sql);
  $st->execute([':asesor_id' => $asesorId]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  echo '<pre style="background:#111;color:#fff;padding:15px;border-radius:10px;white-space:pre-wrap;">';
  echo 'Error SQL: ' . $e->getMessage();
  echo '</pre>';
  exit;
}

/* =====================================================
   MÉTRICAS PARA DASHBOARD
===================================================== */

$totalContratos = count($rows);
$montoTotal = 0.0;
$clientesUnicos = [];

$estadoCounts = [
  'capturado' => 0,
  'por vencer' => 0,
  'cancelado' => 0,
];

$plazoCounts = [];
$porVencerCount = 0;
$proximosVencer = [];

$monthLabels = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$monthlyAmounts = array_fill(0, 12, 0.0);
$monthlyCounts  = array_fill(0, 12, 0);
$currentYear = (int)date('Y');

foreach ($rows as $r) {
  $monto = (float)($r['monto'] ?? 0);
  $montoTotal += $monto;

  $cliente = trim(
    (string)($r['nombre'] ?? '') . ' ' .
    (string)($r['ap_paterno'] ?? '') . ' ' .
    (string)($r['ap_materno'] ?? '')
  );

  if ($cliente !== '') {
    $clientesUnicos[strtolower($cliente)] = true;
  }

$estado = normalizaEstado((string)($r['estado'] ?? 'capturado'));

// solo permitir estos 3 estados
if (!in_array($estado, ['capturado', 'cancelado', 'por vencer'], true)) {
  $estado = 'capturado';
}

$estadoCounts[$estado]++;

  $plazo = trim((string)($r['plazo_anios'] ?? ''));

  if ($plazo === '' || $plazo === '—') {
    $plazoKey = 'Sin plazo';
  } else {
    $plazoKey = $plazo . ' año(s)';
  }

  $plazoCounts[$plazoKey] = ($plazoCounts[$plazoKey] ?? 0) + 1;

  $fs = (string)($r['fecha_solicitud'] ?? '');
  $ts = $fs ? strtotime($fs) : false;

  if ($ts && (int)date('Y', $ts) === $currentYear) {
    $idx = (int)date('n', $ts) - 1;
    $monthlyAmounts[$idx] += $monto;
    $monthlyCounts[$idx]++;
  }

  $dias = null;

  if ($estado !== 'cancelado') {
    $dias = faltanDias($r['fecha_devolucion'] ?? null);
  }

  if ($dias !== null && $dias >= 0 && $dias <= 30) {
    $porVencerCount++;

    $tmp = $r;
    $tmp['_dias'] = $dias;
    $proximosVencer[] = $tmp;
  }
}

usort($proximosVencer, function ($a, $b) {
  return ((int)$a['_dias']) <=> ((int)$b['_dias']);
});

$proximosVencer = array_slice($proximosVencer, 0, 5);
$clientesActivos = count($clientesUnicos);
$recentRows = array_slice($rows, 0, 5);

// ✅ Datos Chart.js estados
$chartEstadosLabels = [];
$chartEstadosValues = [];

foreach ($estadoCounts as $estado => $count) {
  if ($count > 0) {
    $chartEstadosLabels[] = ucfirst($estado);
    $chartEstadosValues[] = $count;
  }
}

// ✅ Datos Chart.js plazos
$chartPlazosLabels = array_keys($plazoCounts);
$chartPlazosValues = array_values($plazoCounts);
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Dashboard de Inversión | CIP Financial México</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Chart.js para gráficas -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --bg:#020617;
  --bg-2:#061426;
  --panel:rgba(8, 22, 43, .86);
  --panel-2:rgba(9, 28, 54, .72);
  --panel-3:rgba(15, 42, 75, .64);
  --text:#f8fafc;
  --muted:#94a3b8;
  --muted-2:#cbd5e1;
  --line:rgba(59,130,246,.22);
  --line-2:rgba(148,163,184,.22);
  --blue:#1d8cff;
  --blue-2:#0f5bd8;
  --cyan:#38bdf8;
  --green:#22c55e;
  --yellow:#f59e0b;
  --red:#ef4444;
  --purple:#8b5cf6;
  --shadow:0 22px 60px rgba(0,0,0,.32);
  --radius:18px;
  --sidebar:278px;
}

:root[data-theme="light"]{
  --bg:#f4f7fb;
  --bg-2:#ffffff;
  --panel:rgba(255,255,255,.94);
  --panel-2:rgba(255,255,255,.88);
  --panel-3:rgba(241,245,249,.92);
  --text:#0f172a;
  --muted:#64748b;
  --muted-2:#334155;
  --line:rgba(37,99,235,.18);
  --line-2:rgba(148,163,184,.34);
  --shadow:0 16px 38px rgba(15,23,42,.10);
}

*{ box-sizing:border-box; margin:0; padding:0; }

body{
  min-height:100vh;
  font-family:Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  background:
    radial-gradient(circle at 20% 0%, rgba(29,140,255,.20), transparent 32%),
    radial-gradient(circle at 85% 12%, rgba(56,189,248,.10), transparent 26%),
    linear-gradient(135deg, var(--bg), var(--bg-2));
  color:var(--text);
}

a{ color:inherit; }
button,input,select{ font:inherit; }

.app{
  width:100%;
  min-height:100vh;
  display:grid;
  grid-template-columns:var(--sidebar) minmax(0,1fr);
}

/* ================== SIDEBAR ================== */
.sidebar{
  position:sticky;
  top:0;
  height:100vh;
  padding:24px 18px;
  border-right:1px solid var(--line);
  background:
    radial-gradient(circle at top left, rgba(29,140,255,.20), transparent 38%),
    linear-gradient(180deg, rgba(2,6,23,.96), rgba(2,14,31,.92));
  box-shadow:12px 0 45px rgba(0,0,0,.18);
  display:flex;
  flex-direction:column;
  gap:20px;
  z-index:50;
}

:root[data-theme="light"] .sidebar{
  background:rgba(255,255,255,.92);
}

.brand{
  display:flex;
  align-items:flex-end;
  gap:10px;
  padding:0 2px 22px;
  border-bottom:1px solid var(--line-2);
}

.brand__logo{
  font-size:2.25rem;
  line-height:.9;
  font-weight:900;
  letter-spacing:-.08em;
}

.brand__name{
  color:#60a5fa;
  font-size:.78rem;
  padding-bottom:5px;
}

.advisor{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px;
  border:1px solid var(--line);
  border-radius:18px;
  background:rgba(255,255,255,.045);
}

.avatar{
  width:46px;
  height:46px;
  border-radius:50%;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg, rgba(29,140,255,.90), rgba(56,189,248,.42));
  box-shadow:0 12px 32px rgba(29,140,255,.22);
  font-size:1.45rem;
}

.advisor small{
  color:var(--muted);
  display:block;
  font-size:.72rem;
}
.advisor strong{
  display:block;
  font-size:.88rem;
  margin-top:1px;
  max-width:155px;
  overflow:hidden;
  white-space:nowrap;
  text-overflow:ellipsis;
}

.nav{
  display:flex;
  flex-direction:column;
  gap:8px;
}

.nav-title{
  color:var(--muted);
  font-size:.72rem;
  letter-spacing:.08em;
  text-transform:uppercase;
  padding:10px 10px 2px;
}

.nav-link{
  width:100%;
  border:1px solid transparent;
  border-radius:14px;
  padding:12px 13px;
  display:flex;
  align-items:center;
  gap:12px;
  color:var(--muted-2);
  text-decoration:none;
  background:transparent;
  cursor:pointer;
  transition:.18s ease;
}

.nav-link:hover{
  transform:translateX(2px);
  color:var(--text);
  background:rgba(255,255,255,.055);
  border-color:var(--line);
}

.nav-link.active{
  color:#fff;
  background:linear-gradient(135deg, var(--blue), var(--blue-2));
  border-color:rgba(96,165,250,.60);
  box-shadow:0 14px 30px rgba(29,140,255,.26);
}

.nav-link.danger{
  color:#fecaca;
}
.nav-link.danger:hover{
  background:rgba(239,68,68,.10);
  border-color:rgba(239,68,68,.32);
}

.nav-link .ico{
  width:22px;
  text-align:center;
}

.sidebar-bottom{
  margin-top:auto;
  display:flex;
  flex-direction:column;
  gap:8px;
  padding-top:16px;
  border-top:1px solid var(--line-2);
}

/* ================== MAIN ================== */
.main{
  min-width:0;
  padding:22px;
}

.topbar{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:16px;
  margin-bottom:18px;
}

.topbar-left{
  display:flex;
  align-items:center;
  gap:16px;
}

.menu-btn{
  display:none;
  width:42px;
  height:42px;
  border-radius:14px;
  border:1px solid var(--line);
  background:var(--panel);
  color:var(--text);
  cursor:pointer;
}

.page-heading h1{
  font-size:1.85rem;
  letter-spacing:-.03em;
}
.page-heading p{
  color:var(--muted);
  margin-top:4px;
  font-size:.92rem;
}

.topbar-actions{
  display:flex;
  align-items:center;
  gap:12px;
}

.date-pill{
  display:flex;
  align-items:center;
  gap:8px;
  color:var(--muted-2);
  border:1px solid var(--line);
  background:var(--panel);
  border-radius:14px;
  padding:10px 12px;
  white-space:nowrap;
}

.btn-primary{
  border:0;
  cursor:pointer;
  border-radius:14px;
  padding:11px 16px;
  color:#fff;
  font-weight:800;
  text-decoration:none;
  display:inline-flex;
  align-items:center;
  gap:8px;
  background:linear-gradient(135deg, var(--blue), var(--blue-2));
  box-shadow:0 14px 30px rgba(29,140,255,.22);
}

.btn-primary:hover{ filter:brightness(1.08); }

/* ================== DASHBOARD GRID ================== */
.dashboard{
  display:grid;
  grid-template-columns:minmax(0,1fr) 330px;
  gap:16px;
}

.left-stack,
.right-stack{
  min-width:0;
  display:flex;
  flex-direction:column;
  gap:16px;
}

.kpi-grid{
  display:grid;
  grid-template-columns:repeat(4, minmax(0,1fr));
  gap:12px;
}

.card,
.kpi,
.chart-card,
.side-card{
  border:1px solid var(--line);
  background:
    linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.025)),
    var(--panel);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
}

.kpi{
  padding:18px;
  min-height:126px;
  overflow:hidden;
  position:relative;
}

.kpi::after{
  content:"";
  position:absolute;
  right:-36px;
  bottom:-50px;
  width:140px;
  height:140px;
  border-radius:50%;
  background:rgba(29,140,255,.10);
}

.kpi-top{
  display:flex;
  align-items:center;
  gap:14px;
  position:relative;
  z-index:1;
}

.kpi-icon{
  width:52px;
  height:52px;
  border-radius:18px;
  display:grid;
  place-items:center;
  font-size:1.5rem;
  background:rgba(29,140,255,.18);
  color:#93c5fd;
}

.kpi-icon.green{ background:rgba(34,197,94,.16); color:#86efac; }
.kpi-icon.purple{ background:rgba(139,92,246,.17); color:#c4b5fd; }
.kpi-icon.yellow{ background:rgba(245,158,11,.17); color:#fcd34d; }

.kpi-label{
  color:var(--muted-2);
  font-size:.86rem;
}

.kpi-value{
  margin-top:4px;
  font-weight:900;
  font-size:1.65rem;
  letter-spacing:-.04em;
}

.kpi-foot{
  color:#22c55e;
  margin-top:12px;
  font-size:.82rem;
  font-weight:750;
  position:relative;
  z-index:1;
}

.kpi-foot.warn{ color:#f59e0b; }

.chart-card{
  padding:16px;
  min-width:0;
}

.chart-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  margin-bottom:12px;
}

.chart-head h2,
.side-card h2{
  font-size:1.05rem;
  letter-spacing:-.02em;
}

.chart-head p{
  color:var(--muted);
  font-size:.82rem;
}

.chart-wrap{
  height:280px;
  position:relative;
}

.chart-wrap.sm{
  height:210px;
}

.sub-grid{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:16px;
}

.side-card{
  padding:16px;
}

.side-card-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:14px;
}

.side-card-header a{
  color:#60a5fa;
  text-decoration:none;
  font-size:.82rem;
}

.donut-box{
  height:210px;
  position:relative;
}

.legend-list{
  display:flex;
  flex-direction:column;
  gap:9px;
  margin-top:10px;
}

.legend-row,
.due-row,
.activity-row{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  padding:10px 0;
  border-bottom:1px solid var(--line-2);
}

.legend-name,
.due-name,
.activity-title{
  font-weight:800;
  font-size:.84rem;
}

.legend-meta,
.due-meta,
.activity-meta{
  color:var(--muted);
  font-size:.75rem;
  margin-top:2px;
}

.dot{
  width:10px;
  height:10px;
  border-radius:50%;
  background:var(--blue);
  flex:0 0 10px;
}

.legend-left,
.due-left,
.activity-left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}

.due-icon,
.activity-icon{
  width:34px;
  height:34px;
  border-radius:12px;
  display:grid;
  place-items:center;
  background:rgba(29,140,255,.16);
  color:#93c5fd;
  flex:0 0 34px;
}

.days{
  border-radius:999px;
  padding:6px 9px;
  background:rgba(245,158,11,.16);
  color:#fcd34d;
  border:1px solid rgba(245,158,11,.34);
  font-size:.74rem;
  font-weight:900;
  white-space:nowrap;
}

/* ================== TABLA ================== */
.table-card{
  border:1px solid var(--line);
  background:var(--panel);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  overflow:hidden;
}

.table-top{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  padding:16px;
  border-bottom:1px solid var(--line);
}

.table-top h2{
  font-size:1.05rem;
}

.filters{
  display:flex;
  align-items:center;
  gap:10px;
  flex-wrap:wrap;
}

.filter-input,
.filter-select{
  min-height:42px;
  border-radius:14px;
  border:1px solid var(--line);
  background:var(--panel-2);
  color:var(--text);
  padding:0 13px;
  outline:none;
}

.filter-input{
  width:min(330px, 100%);
}

.table-scroll{
  width:100%;
  overflow:auto;
}

table{
  width:100%;
  border-collapse:collapse;
  min-width:980px;
}

thead th{
  text-align:left;
  font-size:.78rem;
  color:var(--muted-2);
  font-weight:850;
  padding:12px 16px;
  background:rgba(15,42,75,.46);
  border-bottom:1px solid var(--line);
  white-space:nowrap;
}

tbody td{
  padding:12px 16px;
  border-bottom:1px solid var(--line-2);
  color:var(--muted-2);
  font-size:.86rem;
}

tbody tr:hover{
  background:rgba(29,140,255,.06);
}

.col-folio{
  color:#38bdf8;
  font-weight:900;
  white-space:nowrap;
}

.col-nombre{
  color:var(--text);
  font-weight:750;
}

.col-monto{
  color:var(--text);
  font-weight:850;
  text-align:right;
  white-space:nowrap;
}

.col-plazo{
  text-align:center;
  white-space:nowrap;
}

.col-fecha{
  white-space:nowrap;
}

.estado-chip{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  min-width:88px;
  border-radius:999px;
  padding:6px 10px;
  font-size:.72rem;
  font-weight:900;
  text-transform:uppercase;
  border:1px solid transparent;
}

.estado-capturado{
  color:#93c5fd;
  border-color:rgba(59,130,246,.40);
  background:rgba(59,130,246,.12);
}
.estado-firmado{
  color:#86efac;
  border-color:rgba(34,197,94,.42);
  background:rgba(34,197,94,.12);
}
.estado-completo{
  color:#67e8f9;
  border-color:rgba(6,182,212,.42);
  background:rgba(6,182,212,.12);
}
.estado-por-vencer{
  color:#fcd34d;
  border-color:rgba(245,158,11,.42);
  background:rgba(245,158,11,.13);
}
.estado-cancelado{
  color:#fca5a5;
  border-color:rgba(239,68,68,.42);
  background:rgba(239,68,68,.13);
}

.estado-extra{
  color:var(--muted);
  font-size:.72rem;
  margin-top:4px;
}

.actions-cell{
  text-align:right;
  white-space:nowrap;
}

.btn-sm{
  border-radius:999px;
  padding:7px 10px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.04);
  color:var(--muted-2);
  text-decoration:none;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;
  font-weight:750;
  font-size:.78rem;
  margin-left:6px;
}

.btn-sm:hover{
  color:var(--text);
  border-color:rgba(96,165,250,.55);
  background:rgba(29,140,255,.10);
}

.btn-delete:hover{
  border-color:rgba(239,68,68,.55);
  background:rgba(239,68,68,.10);
  color:#fecaca;
}

.btn-sm.is-disabled{
  opacity:.55;
  cursor:not-allowed;
  pointer-events:none;
}

.empty-state{
  text-align:center;
  color:var(--muted);
  padding:28px;
}
.empty-state span{
  display:block;
  margin-top:5px;
}

/* ================== MODAL DOCUMENTOS ================== */
.modal-docs{
  position:fixed;
  inset:0;
  display:none;
  z-index:9999;
}

.modal-docs.show{
  display:block;
}

.modal-docs__backdrop{
  position:absolute;
  inset:0;
  background:rgba(2,6,23,.78);
  backdrop-filter:blur(8px);
}

.modal-docs__panel{
  position:relative;
  width:min(1040px, calc(100vw - 28px));
  max-height:calc(100vh - 28px);
  margin:14px auto;
  border-radius:24px;
  border:1px solid var(--line);
  background:
    radial-gradient(circle at top left, rgba(29,140,255,.18), transparent 36%),
    var(--panel);
  box-shadow:0 30px 90px rgba(0,0,0,.44);
  overflow:hidden;
  display:flex;
  flex-direction:column;
}

.modal-docs__head{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:12px;
  padding:18px;
  border-bottom:1px solid var(--line);
}

.modal-docs__title{
  font-size:1.15rem;
  font-weight:950;
}

.modal-docs__sub{
  color:var(--muted);
  margin-top:4px;
  font-size:.88rem;
}

.modal-docs__close{
  border:1px solid var(--line);
  background:rgba(255,255,255,.06);
  color:var(--text);
  border-radius:14px;
  padding:9px 12px;
  cursor:pointer;
}

.modal-docs__content{
  padding:18px;
  overflow:auto;
}

.docs-grid{
  display:grid;
  grid-template-columns:repeat(3, minmax(0, 1fr));
  gap:14px;
}

.doc-card{
  border:1px solid var(--line);
  border-radius:18px;
  padding:14px;
  background:rgba(255,255,255,.045);
  display:flex;
  flex-direction:column;
  gap:12px;
}

.doc-card__top{
  display:flex;
  align-items:center;
  gap:11px;
}

.doc-card__icon{
  width:42px;
  height:42px;
  border-radius:15px;
  display:grid;
  place-items:center;
  background:linear-gradient(135deg, rgba(29,140,255,.82), rgba(56,189,248,.30));
  font-size:1.25rem;
  flex:0 0 42px;
}

.doc-card__name{
  font-weight:900;
  font-size:.92rem;
}

.doc-card__hint{
  color:var(--muted);
  font-size:.76rem;
  margin-top:3px;
}

.doc-card__upload{
  min-height:54px;
  border-radius:16px;
  border:1px dashed rgba(96,165,250,.45);
  background:rgba(29,140,255,.07);
  display:grid;
  place-items:center;
  cursor:pointer;
  color:#bfdbfe;
  font-weight:850;
}

.doc-card__upload input{
  display:none;
}

.doc-card__footer{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
}

.doc-card__file{
  color:var(--muted);
  font-size:.78rem;
  overflow:hidden;
  text-overflow:ellipsis;
  white-space:nowrap;
  max-width:68%;
}

.doc-card__file a{
  display:inline-flex;
  align-items:center;
  padding:5px 9px;
  border-radius:999px;
  background:rgba(29,140,255,.22);
  border:1px solid rgba(96,165,250,.35);
  color:#bfdbfe;
  text-decoration:none;
  font-weight:850;
  margin-left:3px;
}

.doc-card__btn{
  border:1px solid var(--line);
  background:rgba(255,255,255,.05);
  color:var(--text);
  border-radius:999px;
  padding:7px 11px;
  cursor:pointer;
  font-weight:850;
  font-size:.78rem;
}

.doc-card__btn:hover{
  background:rgba(29,140,255,.13);
}

.st-ok{ color:#86efac; }
.st-err{ color:#fca5a5; }
.st-up{ color:#93c5fd; }
.st-info{ color:var(--muted); }

.swal2-container{
  z-index:99999 !important;
}

/* ================== RESPONSIVE ================== */
.backdrop{
  display:none;
}

@media (max-width: 1320px){
  .dashboard{
    grid-template-columns:1fr;
  }
  .right-stack{
    display:grid;
    grid-template-columns:1fr 1fr;
  }
}

@media (max-width: 1120px){
  .app{
    grid-template-columns:1fr;
  }

  .menu-btn{
    display:grid;
    place-items:center;
  }

  .sidebar{
    position:fixed;
    left:0;
    top:0;
    transform:translateX(-105%);
    transition:.2s ease;
    width:min(var(--sidebar), calc(100vw - 42px));
  }

  .sidebar.show{
    transform:translateX(0);
  }

  .backdrop{
    position:fixed;
    inset:0;
    background:rgba(2,6,23,.72);
    z-index:40;
  }

  .backdrop.show{
    display:block;
  }

  .kpi-grid{
    grid-template-columns:repeat(2, minmax(0,1fr));
  }
}

@media (max-width: 760px){
  .main{
    padding:14px;
  }

  .topbar{
    align-items:flex-start;
    flex-direction:column;
  }

  .topbar-actions{
    width:100%;
    flex-wrap:wrap;
  }

  .date-pill,
  .btn-primary{
    flex:1 1 auto;
    justify-content:center;
  }

  .kpi-grid,
  .sub-grid,
  .right-stack,
  .docs-grid{
    grid-template-columns:1fr;
  }

  .chart-wrap{
    height:240px;
  }

  .table-top{
    align-items:flex-start;
    flex-direction:column;
  }

  .filters,
  .filter-input,
  .filter-select{
    width:100%;
  }
}

@media (max-width: 520px){
  .page-heading h1{
    font-size:1.45rem;
  }

  .kpi-value{
    font-size:1.35rem;
  }

  .modal-docs__panel{
    width:calc(100vw - 16px);
    margin:8px auto;
    max-height:calc(100vh - 16px);
  }
}
</style>
</head>

<body>

<div class="app">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand__logo">CIP</div>
      <div class="brand__name">Financial México</div>
    </div>

    <div class="advisor">
      <div class="avatar">👤</div>
      <div>
        <small>Asesor</small>
        <strong><?= e($asesorNombreSidebar ?: 'Asesor CIP') ?></strong>
        <small>Asesor financiero</small>
      </div>
    </div>

    <nav class="nav">
      <button class="nav-link active" type="button" onclick="goDashboard()">
        <span class="ico">▦</span><span>Dashboard</span>
      </button>

      <a class="nav-link" href="<?= e($BASE_URL) ?>home.php">
        <span class="ico">⬅️</span><span>Regresar al panel</span>
      </a>

      <a class="nav-link" href="<?= e($BASE_URL) ?>invercion.php">
        <span class="ico">＋</span><span>Nuevo contrato</span>
      </a>

      <a class="nav-link" href="#tablaContratos">
        <span class="ico">📄</span><span>Contratos de inversión</span>
      </a>

      <button class="nav-link" type="button" onclick="document.getElementById('filtro-busqueda')?.focus()">
        <span class="ico">🔎</span><span>Buscar contrato</span>
      </button>

      <button class="nav-link" id="btnTema2" type="button" onclick="toggleTheme()">
        <span class="ico">🌙</span><span>Modo oscuro</span>
      </button>
    </nav>

    <div class="sidebar-bottom">
      <button class="nav-link danger" type="button" onclick="cerrarSesion()">
        <span class="ico">⎋</span><span>Cerrar sesión</span>
      </button>
    </div>
  </aside>

  <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>

  <!-- MAIN -->
  <main class="main">
    <header class="topbar">
      <div class="topbar-left">
        <button class="menu-btn" type="button" onclick="toggleSidebar(true)">☰</button>
        <div class="page-heading">
          <h1>Dashboard</h1>
          <p>Resumen general de tu actividad y contratos de inversión.</p>
        </div>
      </div>

      <div class="topbar-actions">
        <div class="date-pill">📅 <?= e(date('d/m/Y')) ?></div>
        <a class="btn-primary" href="<?= e($BASE_URL) ?>invercion.php">＋ Nuevo contrato</a>
      </div>
    </header>

    <section class="dashboard">

      <div class="left-stack">

        <!-- KPIs -->
        <div class="kpi-grid">
          <article class="kpi">
            <div class="kpi-top">
              <div class="kpi-icon">📄</div>
              <div>
                <div class="kpi-label">Contratos totales</div>
                <div class="kpi-value"><?= (int)$totalContratos ?></div>
              </div>
            </div>
            <div class="kpi-foot">↑ Control general actualizado</div>
          </article>

          <article class="kpi">
            <div class="kpi-top">
              <div class="kpi-icon green">$</div>
              <div>
                <div class="kpi-label">Monto total invertido</div>
                <div class="kpi-value"><?= e(fmtMoney($montoTotal)) ?></div>
              </div>
            </div>
            <div class="kpi-foot">↑ Suma de contratos registrados</div>
          </article>

          <article class="kpi">
            <div class="kpi-top">
              <div class="kpi-icon purple">👥</div>
              <div>
                <div class="kpi-label">Clientes activos</div>
                <div class="kpi-value"><?= (int)$clientesActivos ?></div>
              </div>
            </div>
            <div class="kpi-foot">↑ Clientes únicos</div>
          </article>

          <article class="kpi">
            <div class="kpi-top">
              <div class="kpi-icon yellow">🗓️</div>
              <div>
                <div class="kpi-label">Contratos por vencer</div>
                <div class="kpi-value"><?= (int)$porVencerCount ?></div>
              </div>
            </div>
            <div class="kpi-foot warn">Próximos 30 días</div>
          </article>
        </div>

        <!-- Gráfica principal -->
        <article class="chart-card">
          <div class="chart-head">
            <div>
              <h2>Evolución de inversiones</h2>
              <p>Monto registrado por mes en <?= (int)$currentYear ?>.</p>
            </div>
          </div>
          <div class="chart-wrap">
            <canvas id="chartEvolucion"></canvas>
          </div>
        </article>

        <!-- Gráficas secundarias -->
        <div class="sub-grid">
          <article class="chart-card">
            <div class="chart-head">
              <div>
                <h2>Inversiones por plazo</h2>
                <p>Distribución de contratos por duración.</p>
              </div>
            </div>
            <div class="chart-wrap sm">
              <canvas id="chartPlazos"></canvas>
            </div>
          </article>

          <article class="chart-card">
            <div class="chart-head">
              <div>
                <h2>Contratos mensuales</h2>
                <p>Número de contratos por mes.</p>
              </div>
            </div>
            <div class="chart-wrap sm">
              <canvas id="chartMensuales"></canvas>
            </div>
          </article>
        </div>

        <!-- Tabla -->
        <section class="table-card" id="tablaContratos">
          <div class="table-top">
            <div>
              <h2>Contratos recientes</h2>
              <p style="color:var(--muted);font-size:.84rem;margin-top:4px;">
                Filtra por cliente, folio, monto o estado.
              </p>
            </div>

            <div class="filters">
              <input
                class="filter-input"
                type="text"
                id="filtro-busqueda"
                placeholder="Buscar por folio, cliente, monto..."
                oninput="filtrarTabla()">

              <select class="filter-select" id="filtro-estado" onchange="filtrarTabla()">
                <option value="">Todos los estados</option>
                <option value="capturado">Capturado</option>
                <option value="cancelado">Cancelado</option>
                <option value="por vencer">Por vencer</option>
              </select>
            </div>
          </div>

          <div class="table-scroll">
            <table id="tabla-inversiones">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Cliente</th>
                  <th style="text-align:right;">Monto</th>
                  <th style="text-align:center;">Plazo</th>
                  <th>Fecha solicitud</th>
                  <th>Fecha devolución</th>
                  <th>Estado</th>
                  <th style="text-align:right;">Acciones</th>
                </tr>
              </thead>

              <tbody>
                <?php if (!$rows): ?>
                  <tr>
                    <td colspan="8">
                      <div class="empty-state">
                        No hay contratos registrados.
                        <span>Cuando crees uno, aparecerá aquí.</span>
                      </div>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows as $r): ?>
                    <?php
                      $id = (int)$r['id'];
                      $folio = (string)($r['folio'] ?? '');
                      $cliente = trim(
                        (string)($r['nombre'] ?? '') . ' ' .
                        (string)($r['ap_paterno'] ?? '') . ' ' .
                        (string)($r['ap_materno'] ?? '')
                      );
                      $estado = (string)($r['estado'] ?? 'capturado');
                      $cls = estadoClass($estado);

                      $dias = null;
                      if (strtolower(trim($estado)) !== 'cancelado') {
                        $dias = faltanDias($r['fecha_devolucion'] ?? null);
                      }
                    ?>
                    <tr data-inversion-id="<?= $id ?>">
                      <td class="col-folio" data-label="Folio">
                        <?= e($folio ?: ('CIP-INV-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT))) ?>
                      </td>

                      <td class="col-nombre" data-label="Cliente">
                        <?= e($cliente ?: '—') ?>
                      </td>

                      <td class="col-monto" data-label="Monto">
                        <?= e(fmtMoney($r['monto'] ?? 0)) ?>
                      </td>

                      <td class="col-plazo" data-label="Plazo">
                        <?= e((string)($r['plazo_anios'] ?? '—')) ?> año(s)
                      </td>

                      <td class="col-fecha" data-label="Fecha solicitud">
                        <?= e(fmtFecha($r['fecha_solicitud'] ?? null)) ?>
                      </td>

                      <td class="col-fecha" data-label="Fecha devolución">
                        <?= e(fmtFechaCorta($r['fecha_devolucion'] ?? null)) ?>
                      </td>

                      <td class="col-estado" data-label="Estado">
                        <span class="estado-chip <?= e($cls) ?> badge-estado">
                          <?= e($estado) ?>
                        </span>

                        <?php if ($dias !== null): ?>
                          <div class="estado-extra">
                            <?= ($dias >= 0) ? ('Faltan ' . $dias . ' día(s)') : ('Vencido hace ' . abs($dias) . ' día(s)') ?>
                          </div>
                        <?php endif; ?>
                      </td>

                      <td class="actions-cell" data-label="Acciones">
                        <button type="button"
                                class="btn-sm"
                                onclick="openDocsModal(<?= $id ?>, '<?= e($folio) ?>', '<?= e($cliente) ?>')">
                          📁 Docs
                        </button>

                        <a class="btn-sm" href="<?= e($BASE_URL) ?>invercion.php?id=<?= $id ?>">
                          👁️ Ver
                        </a>

                        <?php if (strtolower(trim($estado)) === 'cancelado'): ?>
                          <span class="estado-chip estado-cancelado" style="margin-left:.5rem;">Cancelado</span>
                        <?php else: ?>
                          <a href="#" class="btn-sm btn-delete"
                             onclick="cancelarInversion(<?= $id ?>, '<?= e($folio) ?>'); return false;">
                            ✕ Eliminar
                          </a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </div>

      <!-- RIGHT COLUMN -->
      <aside class="right-stack">

        <article class="side-card">
          <div class="side-card-header">
            <h2>Distribución por estado</h2>
            <a href="#tablaContratos">Ver todos</a>
          </div>

          <div class="donut-box">
            <canvas id="chartEstados"></canvas>
          </div>

          <div class="legend-list">
            <?php
              $dotColors = ['#1d8cff','#22c55e','#f59e0b','#8b5cf6','#ef4444','#06b6d4'];
              $i = 0;
            ?>
            <?php foreach ($estadoCounts as $estado => $count): ?>
              <?php if ($count <= 0) continue; ?>
              <?php
                $pct = $totalContratos > 0 ? round(($count / $totalContratos) * 100, 1) : 0;
                $color = $dotColors[$i % count($dotColors)];
                $i++;
              ?>
              <div class="legend-row">
                <div class="legend-left">
                  <span class="dot" style="background:<?= e($color) ?>"></span>
                  <div>
                    <div class="legend-name"><?= e(ucfirst($estado)) ?></div>
                    <div class="legend-meta"><?= (int)$pct ?>% del total</div>
                  </div>
                </div>
                <strong><?= (int)$count ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </article>

        <article class="side-card">
          <div class="side-card-header">
            <h2>Próximos a vencer</h2>
            <a href="#tablaContratos">Ver todos</a>
          </div>

          <?php if (!$proximosVencer): ?>
            <div class="empty-state" style="padding:18px 4px;">No hay contratos por vencer en los próximos 30 días.</div>
          <?php else: ?>
            <?php foreach ($proximosVencer as $r): ?>
              <?php
                $id = (int)$r['id'];
                $folio = (string)($r['folio'] ?? ('CIP-INV-' . $id));
                $cliente = trim(
                  (string)($r['nombre'] ?? '') . ' ' .
                  (string)($r['ap_paterno'] ?? '') . ' ' .
                  (string)($r['ap_materno'] ?? '')
                );
              ?>
              <div class="due-row">
                <div class="due-left">
                  <div class="due-icon">👤</div>
                  <div>
                    <div class="due-name"><?= e($cliente ?: '—') ?></div>
                    <div class="due-meta"><?= e($folio) ?> · <?= e(fmtFechaCorta($r['fecha_devolucion'] ?? null)) ?></div>
                  </div>
                </div>
                <span class="days"><?= (int)$r['_dias'] ?> días</span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>

        <article class="side-card">
          <div class="side-card-header">
            <h2>Actividad reciente</h2>
            <a href="#tablaContratos">Ver tabla</a>
          </div>

          <?php if (!$recentRows): ?>
            <div class="empty-state" style="padding:18px 4px;">Sin actividad reciente.</div>
          <?php else: ?>
            <?php foreach ($recentRows as $r): ?>
              <?php
                $folio = (string)($r['folio'] ?? '');
                $cliente = trim(
                  (string)($r['nombre'] ?? '') . ' ' .
                  (string)($r['ap_paterno'] ?? '') . ' ' .
                  (string)($r['ap_materno'] ?? '')
                );
              ?>
              <div class="activity-row">
                <div class="activity-left">
                  <div class="activity-icon">＋</div>
                  <div>
                    <div class="activity-title">Contrato registrado</div>
                    <div class="activity-meta">
                      <?= e($folio ?: 'Sin folio') ?> · <?= e($cliente ?: '—') ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </article>

      </aside>

    </section>
  </main>
</div>

<!-- ================== MODAL DOCUMENTOS ================== -->
<div class="modal-docs" id="modalDocs" aria-hidden="true">
  <div class="modal-docs__backdrop" onclick="closeDocsModal()"></div>

  <div class="modal-docs__panel" role="dialog" aria-modal="true" aria-labelledby="docsTitle">
    <div class="modal-docs__head">
      <div>
        <div class="modal-docs__title" id="docsTitle">Documentos del contrato</div>
        <div class="modal-docs__sub" id="docsMeta">—</div>
      </div>

      <button class="modal-docs__close" type="button" onclick="closeDocsModal()">✕</button>
    </div>

    <div class="modal-docs__content">
      <div class="docs-grid">

        <div class="doc-card" data-doc="ine">
          <div class="doc-card__top">
            <div class="doc-card__icon">🪪</div>
            <div>
              <div class="doc-card__name">INE (Identificación oficial)</div>
              <div class="doc-card__hint">PDF (INE / Pasaporte)</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="INE" onchange="previewDocName(this, 'doc_ine_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_ine_name" class="doc-card__file" data-status="INE">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="INE">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="domicilio">
          <div class="doc-card__top">
            <div class="doc-card__icon">🏠</div>
            <div>
              <div class="doc-card__name">Comprobante de domicilio</div>
              <div class="doc-card__hint">PDF (agua, luz, teléfono, etc.)</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="COMPROBANTE_DOMICILIO" onchange="previewDocName(this, 'doc_dom_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_dom_name" class="doc-card__file" data-status="COMPROBANTE_DOMICILIO">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="COMPROBANTE_DOMICILIO">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="rfc">
          <div class="doc-card__top">
            <div class="doc-card__icon">🧾</div>
            <div>
              <div class="doc-card__name">Constancia de Situación Fiscal (RFC)</div>
              <div class="doc-card__hint">PDF del SAT</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="RFC" onchange="previewDocName(this, 'doc_rfc_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_rfc_name" class="doc-card__file" data-status="RFC">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="RFC">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="edo_6m">
          <div class="doc-card__top">
            <div class="doc-card__icon">📊</div>
            <div>
              <div class="doc-card__name">Estado de cuenta (últimos 6 meses)</div>
              <div class="doc-card__hint">PDF bancario</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="E_CUENTA_6M" onchange="previewDocName(this, 'doc_edo6_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_edo6_name" class="doc-card__file" data-status="E_CUENTA_6M">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="E_CUENTA_6M">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="edo_actual">
          <div class="doc-card__top">
            <div class="doc-card__icon">📄</div>
            <div>
              <div class="doc-card__name">Estado de cuenta (actual)</div>
              <div class="doc-card__hint">PDF del mes actual</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="E_CUENTA_ACTUAL" onchange="previewDocName(this, 'doc_edoact_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_edoact_name" class="doc-card__file" data-status="E_CUENTA_ACTUAL">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="E_CUENTA_ACTUAL">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="evidencia_transferencia">
          <div class="doc-card__top">
            <div class="doc-card__icon">💸</div>
            <div>
              <div class="doc-card__name">Evidencia de transferencia</div>
              <div class="doc-card__hint">PDF del comprobante SPEI / captura</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="EVIDENCIA_TRANSFERENCIA" onchange="previewDocName(this, 'doc_spei_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_spei_name" class="doc-card__file" data-status="EVIDENCIA_TRANSFERENCIA">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="EVIDENCIA_TRANSFERENCIA">Guardar</button>
          </div>
        </div>

        <div class="doc-card" data-doc="contrato_firmado">
          <div class="doc-card__top">
            <div class="doc-card__icon">📑</div>
            <div>
              <div class="doc-card__name">Contrato firmado</div>
              <div class="doc-card__hint">PDF del contrato con firmas</div>
            </div>
          </div>
          <label class="doc-card__upload">
            <input type="file" accept="application/pdf" data-file-input="CONTRATO_FIRMADO" onchange="previewDocName(this, 'doc_contrato_firmado_name')">
            <span>Subir PDF</span>
          </label>
          <div class="doc-card__footer">
            <span id="doc_contrato_firmado_name" class="doc-card__file" data-status="CONTRATO_FIRMADO">Selecciona un PDF.</span>
            <button type="button" class="doc-card__btn" data-upload-btn="CONTRATO_FIRMADO">Guardar</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
const BASE_URL = <?= json_encode($BASE_URL, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
const UPLOAD_URL = BASE_URL + "app/controllers/documentos/subir_documento_inver.php";
const LIST_URL = BASE_URL + "app/controllers/documentos/listar_documentos_inver.php";

const CHART_DATA = {
  months: <?= json_encode($monthLabels, JSON_UNESCAPED_UNICODE) ?>,
  monthlyAmounts: <?= json_encode(array_map(fn($v) => round($v, 2), $monthlyAmounts), JSON_UNESCAPED_UNICODE) ?>,
  monthlyCounts: <?= json_encode($monthlyCounts, JSON_UNESCAPED_UNICODE) ?>,
  estadosLabels: <?= json_encode($chartEstadosLabels, JSON_UNESCAPED_UNICODE) ?>,
  estadosValues: <?= json_encode($chartEstadosValues, JSON_UNESCAPED_UNICODE) ?>,
  plazosLabels: <?= json_encode($chartPlazosLabels, JSON_UNESCAPED_UNICODE) ?>,
  plazosValues: <?= json_encode($chartPlazosValues, JSON_UNESCAPED_UNICODE) ?>
};

const DOC_TYPES = [
  "INE",
  "COMPROBANTE_DOMICILIO",
  "RFC",
  "E_CUENTA_6M",
  "E_CUENTA_ACTUAL",
  "EVIDENCIA_TRANSFERENCIA",
  "CONTRATO_FIRMADO"
];

const THEME_KEY = 'cip_theme';
const _lastOkStatus = {};

function moneyShort(value){
  const n = Number(value || 0);
  if (n >= 1000000) return '$' + (n / 1000000).toFixed(1) + 'M';
  if (n >= 1000) return '$' + (n / 1000).toFixed(0) + 'K';
  return '$' + n.toFixed(0);
}

function cssVar(name){
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function toggleSidebar(open){
  const sb = document.getElementById('sidebar');
  const bd = document.getElementById('backdrop');
  if(!sb || !bd) return;

  if(open){
    sb.classList.add('show');
    bd.classList.add('show');
  }else{
    sb.classList.remove('show');
    bd.classList.remove('show');
  }
}

function goDashboard(){
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function applyTheme(theme){
  if (theme !== 'light' && theme !== 'dark') theme = 'dark';
  document.documentElement.setAttribute('data-theme', theme);

  try { localStorage.setItem(THEME_KEY, theme); } catch(e){}

  const btn2 = document.getElementById('btnTema2');
  if (btn2){
    const txt = btn2.querySelector('span:last-child');
    const ico = btn2.querySelector('.ico');

    if (txt) txt.textContent = (theme === 'dark') ? 'Modo claro' : 'Modo oscuro';
    if (ico) ico.textContent = (theme === 'dark') ? '☀️' : '🌙';
  }

  setTimeout(renderCharts, 80);
}

function toggleTheme(){
  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

function filtrarTabla() {
  const q = (document.getElementById('filtro-busqueda')?.value || '').toLowerCase();
  const estado = (document.getElementById('filtro-estado')?.value || '').toLowerCase();

  const tbody = document.querySelector('#tabla-inversiones tbody');
  if (!tbody) return;

  Array.from(tbody.rows).forEach(row => {
    const textoRow = row.innerText.toLowerCase();
    const estadoTexto = (row.querySelector('.badge-estado')?.textContent || '').toLowerCase();

    const matchTexto = !q || textoRow.includes(q);
    const matchEstado = !estado || estadoTexto.includes(estado);

    row.style.display = (matchTexto && matchEstado) ? '' : 'none';
  });
}

async function cerrarSesion(){
  try{
    await fetch(BASE_URL + 'app/controllers/auth/logout.php', {
      method:'POST',
      credentials:'include',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body:''
    });
  }catch(_){}

  window.location.href = BASE_URL + 'login.html';
}

/* =========================
   CHARTS
========================= */
let charts = {};

function destroyCharts(){
  Object.values(charts).forEach(ch => {
    try { ch.destroy(); } catch(e){}
  });
  charts = {};
}

function renderCharts(){
  if (typeof Chart === 'undefined') return;

  destroyCharts();

  const text = cssVar('--muted-2') || '#cbd5e1';
  const grid = 'rgba(148,163,184,.12)';
  const blue = cssVar('--blue') || '#1d8cff';

  Chart.defaults.color = text;
  Chart.defaults.font.family = 'Inter, system-ui, sans-serif';

  const evolution = document.getElementById('chartEvolucion');
  if (evolution) {
    const ctx = evolution.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(29,140,255,.46)');
    gradient.addColorStop(1, 'rgba(29,140,255,.02)');

    charts.evolucion = new Chart(evolution, {
      type: 'line',
      data: {
        labels: CHART_DATA.months,
        datasets: [{
          label: 'Monto invertido',
          data: CHART_DATA.monthlyAmounts,
          borderColor: blue,
          backgroundColor: gradient,
          fill: true,
          tension: .42,
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: blue
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display:false },
          tooltip: {
            callbacks: {
              label: ctx => ' ' + moneyShort(ctx.parsed.y)
            }
          }
        },
        scales: {
          x: { grid: { color: grid } },
          y: {
            grid: { color: grid },
            ticks: { callback: value => moneyShort(value) }
          }
        }
      }
    });
  }

  const estados = document.getElementById('chartEstados');
  if (estados) {
    charts.estados = new Chart(estados, {
      type: 'doughnut',
      data: {
        labels: CHART_DATA.estadosLabels,
        datasets: [{
          data: CHART_DATA.estadosValues,
          backgroundColor: ['#1d8cff','#22c55e','#f59e0b','#8b5cf6','#ef4444','#06b6d4'],
          borderWidth: 0,
          hoverOffset: 7
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '66%',
        plugins: {
          legend: { display:false }
        }
      }
    });
  }

  const plazos = document.getElementById('chartPlazos');
  if (plazos) {
    charts.plazos = new Chart(plazos, {
      type: 'doughnut',
      data: {
        labels: CHART_DATA.plazosLabels,
        datasets: [{
          data: CHART_DATA.plazosValues,
          backgroundColor: ['#1d8cff','#22c55e','#f59e0b','#8b5cf6','#06b6d4','#ef4444'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '58%',
        plugins: { legend: { position:'right', labels:{ boxWidth:10, boxHeight:10 } } }
      }
    });
  }

  const mensuales = document.getElementById('chartMensuales');
  if (mensuales) {
    charts.mensuales = new Chart(mensuales, {
      type: 'bar',
      data: {
        labels: CHART_DATA.months,
        datasets: [{
          label: 'Contratos',
          data: CHART_DATA.monthlyCounts,
          backgroundColor: 'rgba(29,140,255,.72)',
          borderRadius: 8,
          maxBarThickness: 32
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display:false } },
        scales: {
          x: { grid: { display:false } },
          y: { grid: { color: grid }, ticks: { precision:0 } }
        }
      }
    });
  }
}

/* =========================
   DOCUMENTOS
========================= */
function getInversionIdFromModal(){
  const modal = document.getElementById('modalDocs');
  const id = parseInt(modal?.dataset?.inversionId || "0", 10);
  return id > 0 ? id : 0;
}

function setStatus(tipo, msg, mode="info") {
  const el = document.querySelector(`[data-status="${tipo}"]`);
  if (!el) return;

  el.innerHTML = msg;
  el.classList.remove("st-ok","st-err","st-info","st-up");

  if (mode === "ok") el.classList.add("st-ok");
  else if (mode === "err") el.classList.add("st-err");
  else if (mode === "up") el.classList.add("st-up");
  else el.classList.add("st-info");
}

function lockCard(tipo, locked) {
  const btn = document.querySelector(`[data-upload-btn="${tipo}"]`);
  const inp = document.querySelector(`[data-file-input="${tipo}"]`);
  if (btn) btn.disabled = !!locked;
  if (inp) inp.disabled = !!locked;
}

function humanSize(bytes) {
  const n = Number(bytes) || 0;
  if (n < 1024) return `${n} B`;
  if (n < 1024*1024) return `${(n/1024).toFixed(1)} KB`;
  return `${(n/(1024*1024)).toFixed(1)} MB`;
}

function validarPDF(file) {
  if (!file) return "Selecciona un archivo.";
  const name = (file.name || "").toLowerCase();
  if (!name.endsWith(".pdf")) return "Solo se permite PDF.";

  const max = 2.5 * 1024 * 1024;
  if (file.size > max) {
    const mb = (file.size / (1024 * 1024)).toFixed(2);

    Swal.fire({
      target: document.getElementById('modalDocs'),
      icon: "error",
      title: "Archivo demasiado grande",
      html: `Tu PDF pesa <b>${mb} MB</b>.<br>El máximo permitido es <b>2.5 MB</b>.`,
      confirmButtonText: "Entendido",
      allowOutsideClick: false
    });

    return "__too_big__";
  }

  return null;
}

function rememberOkStatus(tipo){
  const el = document.querySelector(`[data-status="${tipo}"]`);
  if (!el) return;

  const html = el.innerHTML || "";
  if (html.includes("Subido") || html.includes("Ver PDF")) {
    _lastOkStatus[tipo] = html;
  }
}

function restoreOkStatus(tipo){
  const el = document.querySelector(`[data-status="${tipo}"]`);
  if (!el) return;

  if (_lastOkStatus[tipo]) {
    el.innerHTML = _lastOkStatus[tipo];
    el.classList.remove("st-ok","st-err","st-info","st-up");
    el.classList.add("st-ok");
  } else {
    setStatus(tipo, "Selecciona un PDF.", "info");
  }
}

function resetDocsUI() {
  DOC_TYPES.forEach(t => { delete _lastOkStatus[t]; });

  DOC_TYPES.forEach(t => {
    const inp = document.querySelector(`[data-file-input="${t}"]`);
    if (inp) inp.value = "";
  });

  DOC_TYPES.forEach(t => setStatus(t, "Selecciona un PDF.", "info"));
}

function openDocsModal(id, folio, cliente){
  document.body.style.overflow = "hidden";

  const modal = document.getElementById('modalDocs');
  const meta  = document.getElementById('docsMeta');
  if(!modal) return;

  modal.dataset.inversionId = String(id || '');

  const f = folio ? folio : ('CIP-INV-' + String(id).padStart(4,'0'));
  const c = cliente ? cliente : '—';

  if(meta){
    meta.textContent = `Contrato: ${f} · Cliente: ${c}`;
  }

  resetDocsUI();

  modal.classList.add('show');
  modal.setAttribute('aria-hidden','false');
  document.addEventListener('keydown', escCloseDocs);

  cargarDocsModal();
}

function closeDocsModal(){
  document.body.style.overflow = "";

  const modal = document.getElementById('modalDocs');
  if(!modal) return;

  modal.classList.remove('show');
  modal.setAttribute('aria-hidden','true');
  document.removeEventListener('keydown', escCloseDocs);

  resetDocsUI();
  modal.dataset.inversionId = "";
}

function escCloseDocs(e){
  if(e.key === 'Escape') closeDocsModal();
}

function previewDocName(input, targetId){
  const el = document.getElementById(targetId);
  if(!el) return;

  const file = input?.files?.[0];
  el.textContent = file ? file.name : 'Sin archivo';
}

async function subirDocumento(tipo) {
  const inversionId = getInversionIdFromModal();

  if (!inversionId) {
    Swal.fire({
      target: document.getElementById('modalDocs'),
      icon: "warning",
      title: "Sin contrato",
      text: "Abre el modal desde un contrato para poder subir documentos."
    });
    return;
  }

  const input = document.querySelector(`[data-file-input="${tipo}"]`);
  const file = input?.files?.[0];

  if (!file) {
    Swal.fire({
      target: document.getElementById('modalDocs'),
      icon: "info",
      title: "Selecciona un PDF",
      text: "Primero elige un archivo antes de subir."
    });
    restoreOkStatus(tipo);
    return;
  }

  rememberOkStatus(tipo);

  const err = validarPDF(file);
  if (err) {
    if (input) input.value = "";
    restoreOkStatus(tipo);
    return;
  }

  lockCard(tipo, true);
  setStatus(tipo, `⬆️ Subiendo… (${humanSize(file.size)})`, "up");

  const fd = new FormData();
  fd.append("inversion_id", String(inversionId));
  fd.append("tipo_documento", tipo);
  fd.append("file", file);

  try {
    const resp = await fetch(UPLOAD_URL, {
      method: "POST",
      body: fd,
      credentials: "include"
    });

    const ct = resp.headers.get("Content-Type") || "";
    if (!ct.includes("application/json")) {
      if (input) input.value = "";
      Swal.fire({
        target: document.getElementById('modalDocs'),
        icon: "error",
        title: "Error del servidor",
        text: "El servidor devolvió una respuesta no válida."
      });
      restoreOkStatus(tipo);
      return;
    }

    const json = await resp.json();

    if (!resp.ok || !json.ok) {
      if (input) input.value = "";
      Swal.fire({
        target: document.getElementById('modalDocs'),
        icon: "error",
        title: "No se pudo subir",
        text: json.error || "Error al subir"
      });
      restoreOkStatus(tipo);
      return;
    }

    const link = json.download_url
      ? ` · <a href="${json.download_url}" target="_blank" rel="noopener">Ver PDF</a>`
      : "";

    setStatus(tipo, `✅ Subido v${json.version}${link}`, "ok");
    if (input) input.value = "";

    setTimeout(() => cargarDocsModal(), 250);

  } catch (e) {
    console.error(e);
    if (input) input.value = "";
    Swal.fire({
      target: document.getElementById('modalDocs'),
      icon: "error",
      title: "Error de red",
      text: "No se pudo conectar para subir el archivo."
    });
    restoreOkStatus(tipo);
  } finally {
    lockCard(tipo, false);
  }
}

async function cargarDocsModal() {
  const inversionId = getInversionIdFromModal();
  if (!inversionId) return;

  try {
    const resp = await fetch(`${LIST_URL}?inversion_id=${inversionId}`, {
      credentials: "include"
    });

    const ct = resp.headers.get("Content-Type") || "";
    if (!ct.includes("application/json")) return;

    const json = await resp.json();
    if (!json.ok && !json.success) return;

    const docs = json.documentos || json.data || [];
    const map = {};

    for (const d of docs) {
      const tipo = (d.tipo_documento || "").toUpperCase();
      if (!map[tipo]) map[tipo] = d;
    }

    DOC_TYPES.forEach(t => {
      const doc = map[t];

      if (!doc) {
        restoreOkStatus(t);
        return;
      }

      const url = doc.download_url || doc.url || "";
      const v = doc.version || "-";

      if (url) {
        setStatus(
          t,
          `✅ Subido v${v} · <a href="${url}" target="_blank" rel="noopener">Ver PDF</a>`,
          "ok"
        );
      } else {
        setStatus(t, `✅ Subido v${v}`, "ok");
      }

      rememberOkStatus(t);
    });

  } catch (e) {
    console.error("cargarDocsModal error:", e);
  }
}

async function cancelarInversion(id, folio) {
  if (!id) return;

  const fila = document.querySelector(`tr[data-inversion-id="${id}"]`);

  const result = await Swal.fire({
    icon: "warning",
    title: "Cancelar contrato",
    text: folio
      ? `¿Seguro que deseas eliminar/cancelar el contrato ${folio}?`
      : "¿Seguro que deseas eliminar/cancelar este contrato?",
    showCancelButton: true,
    confirmButtonText: "Sí, cancelar",
    cancelButtonText: "No",
    confirmButtonColor: "#ef4444"
  });

  if (!result.isConfirmed) return;

  try {
    const resp = await fetch(`${BASE_URL}app/controllers/invercion/eliminar_inversion.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      credentials: 'include',
      body: JSON.stringify({ id })
    });

    const contentType = resp.headers.get('Content-Type') || '';
    if (!contentType.includes('application/json')) {
      const text = await resp.text();
      console.error('Respuesta NO JSON en eliminar:', resp.status, text);
      Swal.fire("Error", "El servidor devolvió una respuesta no válida.", "error");
      return;
    }

    const json = await resp.json();

    if (!resp.ok || !json.ok) {
      Swal.fire({
        icon: "error",
        title: "No se pudo cancelar",
        text: json.error || json.message || `HTTP ${resp.status}`
      });
      return;
    }

    if (fila) {
      const badgeEstado = fila.querySelector('.col-estado .badge-estado');
      if (badgeEstado) {
        badgeEstado.textContent = 'cancelado';
        badgeEstado.classList.remove('estado-capturado','estado-firmado','estado-por-vencer','estado-completo');
        badgeEstado.classList.add('estado-cancelado');
      }

      const extra = fila.querySelector('.col-estado .estado-extra');
      if (extra) extra.remove();

      const btnDel = fila.querySelector('.btn-delete');
      if (btnDel) {
        btnDel.textContent = 'Cancelado';
        btnDel.classList.add('is-disabled');
        btnDel.onclick = null;
        btnDel.removeAttribute('href');
      }
    }

    Swal.fire("Listo", "El contrato fue cancelado correctamente.", "success");

  } catch (e) {
    console.error(e);
    Swal.fire("Error", "No se pudo conectar para cancelar la inversión.", "error");
  }
}

/* =========================
   INIT
========================= */
document.addEventListener("DOMContentLoaded", () => {
  let saved = 'dark';
  try { saved = localStorage.getItem(THEME_KEY) || 'dark'; } catch(e){}
  applyTheme(saved);

  document.querySelectorAll("[data-upload-btn]").forEach(btn => {
    btn.addEventListener("click", () => {
      const tipo = btn.getAttribute("data-upload-btn");
      subirDocumento(tipo);
    });
  });

  document.querySelectorAll("[data-file-input]").forEach(inp => {
    inp.addEventListener("change", () => {
      const tipo = inp.getAttribute("data-file-input");
      const file = inp.files?.[0];
      if (!file) return;

      rememberOkStatus(tipo);

      const err = validarPDF(file);
      if (err) {
        inp.value = "";
        restoreOkStatus(tipo);
        return;
      }

      const el = document.querySelector(`[data-status="${tipo}"]`);
      const yaTieneVer = el && (el.innerHTML.includes("Ver PDF") || el.innerHTML.includes("Subido"));
      if (!yaTieneVer) {
        setStatus(tipo, `📄 Listo: ${file.name} (${humanSize(file.size)})`, "info");
      }
    });
  });

  DOC_TYPES.forEach(t => restoreOkStatus(t));
});

window.openDocsModal = openDocsModal;
window.closeDocsModal = closeDocsModal;
window.subirDocumento = subirDocumento;
</script>

</body>
</html>
