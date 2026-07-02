<?php
declare(strict_types=1);

/* =========================================================
   CENTRO DE INSTRUMENTOS CIP
   Vista gerencial: Solicitud / Ahorro / Inversión / Complemento
   Acceso: asesor ID 7
========================================================= */

/* =========================================================
   BASE URL dinámica
   Local XAMPP: /sempiternal/public
   Railway: raíz del dominio
========================================================= */
$host = $_SERVER['HTTP_HOST'] ?? '';

$isLocal = str_contains($host, 'localhost')
  || str_contains($host, '127.0.0.1');

$BASE_URL = $isLocal ? '/sempiternal/public' : '';

/* =========================================================
   Resolver carpeta /public
========================================================= */
$publicDir = null;

if ($isLocal) {
  $publicDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/sempiternal/public');
} else {
  $publicDir = realpath($_SERVER['DOCUMENT_ROOT']);
}

if (!$publicDir) {
  $try = __DIR__;

  for ($i = 0; $i < 10; $i++) {
    if (is_dir($try . '/app') && is_dir($try . '/app/db')) {
      $publicDir = realpath($try);
      break;
    }

    $try = dirname($try);
  }
}

if (!$publicDir) {
  http_response_code(500);
  exit('No pude resolver la carpeta /public. Revisa DOCUMENT_ROOT.');
}

/* =========================================================
   Sesión obligatoria
========================================================= */
$loginPath = $publicDir . '/app/controllers/auth/require_login.php';

if (!file_exists($loginPath)) {
  http_response_code(500);
  exit("No se encontró require_login.php en: {$loginPath}");
}

require_once $loginPath;

/* =========================================================
   Conexión BD
========================================================= */
$conexionPath = $publicDir . '/app/db/conexion.php';

if (!file_exists($conexionPath)) {
  http_response_code(500);
  exit("No se encontró conexion.php en: {$conexionPath}");
}

require_once $conexionPath;

/* =========================================================
   Resolver conexión PDO
========================================================= */
if (isset($pdo) && $pdo instanceof PDO) {
  $db = $pdo;
} elseif (isset($conn) && $conn instanceof PDO) {
  $db = $conn;
} else {
  http_response_code(500);
  exit('Conexión PDO no disponible. Revisa conexion.php');
}

$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* =========================================================
   Asesor ID en sesión
========================================================= */
$asesorId = (int) (
  $_SESSION['asesor']['id_asesor']
  ?? $_SESSION['asesor']['id']
  ?? $_SESSION['asesor_id']
  ?? $_SESSION['user_id']
  ?? 0
);

/* =========================================================
   Bloqueo: solo asesor ID 7
========================================================= */
if ($asesorId !== 7) {
  http_response_code(403);
  ?>
  <!doctype html>
  <html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso restringido</title>
    <style>
      body{
        margin:0;
        min-height:100vh;
        display:grid;
        place-items:center;
        background:#020617;
        color:#f9fafb;
        font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      }

      .card{
        max-width:560px;
        padding:24px;
        border-radius:18px;
        border:1px solid rgba(148,163,184,.35);
        background:rgba(15,23,42,.82);
        box-shadow:0 20px 45px rgba(0,0,0,.35);
      }

      h1{
        margin:0 0 10px;
        font-size:22px;
      }

      p{
        margin:0 0 10px;
        opacity:.9;
        line-height:1.45;
      }

      a{
        color:#7dd3fc;
        font-weight:800;
        text-decoration:none;
      }

      a:hover{
        text-decoration:underline;
      }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Acceso restringido</h1>
      <p>Este módulo es exclusivo para el asesor <b>ID 7</b>.</p>
      <p>
        <a href="<?= htmlspecialchars($BASE_URL . '/home.php', ENT_QUOTES, 'UTF-8') ?>">
          ← Volver al panel
        </a>
      </p>
    </div>
  </body>
  </html>
  <?php
  exit;
}

/* =========================================================
   Helpers generales
========================================================= */
function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function fmtMoney($n): string {
  return '$' . number_format((float)$n, 2, '.', ',');
}

function fmtFecha(?string $date): string {
  if (!$date) return '—';

  $ts = strtotime($date);

  return $ts ? date('d/m/Y', $ts) : $date;
}

/* =========================================================
   Helpers BD seguros
========================================================= */
function tableExists(PDO $db, string $table): bool {
  try {
    $st = $db->prepare("SHOW TABLES LIKE ?");
    $st->execute([$table]);

    return (bool)$st->fetch(PDO::FETCH_NUM);
  } catch (Throwable $e) {
    return false;
  }
}

function tableColumns(PDO $db, string $table): array {
  try {
    $st = $db->query("SHOW COLUMNS FROM `$table`");
    $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];

    return array_map(
      static fn($r) => (string)($r['Field'] ?? ''),
      $rows
    );
  } catch (Throwable $e) {
    return [];
  }
}

function firstExistingColumn(array $columns, array $candidates): ?string {
  foreach ($candidates as $candidate) {
    if (in_array($candidate, $columns, true)) {
      return $candidate;
    }
  }

  return null;
}

function buildConcatName(string $alias, array $columns, array $candidates): ?string {
  $parts = [];

  foreach ($candidates as $candidate) {
    if (in_array($candidate, $columns, true)) {
      $parts[] = "$alias.`$candidate`";
    }
  }

  if (!$parts) {
    return null;
  }

  return "NULLIF(TRIM(CONCAT_WS(' ', " . implode(', ', $parts) . ")), '')";
}

/* =========================================================
   Nombre para sidebar
========================================================= */
$asesorNombreSidebar = 'Asesor';

if (!empty($_SESSION['asesor']) && is_array($_SESSION['asesor'])) {
  $a = $_SESSION['asesor'];

  $asesorNombreSidebar = trim(
    (string)($a['nombre'] ?? '') . ' ' .
    (string)($a['apellido_paterno'] ?? '') . ' ' .
    (string)($a['apellido_materno'] ?? '')
  );

  if ($asesorNombreSidebar === '') {
    $asesorNombreSidebar = 'Asesor';
  }
}

/* =========================================================
   Tipo inicial
========================================================= */
$TIPOS = ['todos', 'solicitud', 'ahorro', 'inversion', 'complemento'];

$tipo = strtolower(trim((string)($_GET['tipo'] ?? 'todos')));

if (!in_array($tipo, $TIPOS, true)) {
  $tipo = 'todos';
}

/* =========================================================
   Solicitudes iniciales desde BD
   Con asesor real + folio pendiente + cancelados correctos
========================================================= */
/* =========================================================
   Solicitudes iniciales desde BD
   Con asesor real desde asesores.nombre
========================================================= */
$solicitudes = [];

try {
  $sql = "
    SELECT
      s.id,
      s.asesor_id,
      NULLIF(TRIM(s.folio), '') AS folio_real,

      COALESCE(
        NULLIF(TRIM(s.folio), ''),
        CONCAT('Sin folio #', s.id)
      ) AS folio_mostrar,

      COALESCE(s.monto, 0) AS monto,
      s.plazo,
      s.fecha_registro AS fecha_solicitud,

      COALESCE(
        NULLIF(TRIM(a.nombre), ''),
        NULLIF(TRIM(a.usuario), ''),
        CONCAT('Asesor ', s.asesor_id)
      ) AS asesor_nombre,

      COALESCE(
        NULLIF(TRIM(s.estado_validacion), ''),
        ''
      ) AS estado_validacion

    FROM solicitudes s
    LEFT JOIN asesores a
      ON a.id_asesor = s.asesor_id

    ORDER BY s.id DESC
    LIMIT 500
  ";

  $st = $db->prepare($sql);
  $st->execute();

  $solicitudes = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  error_log('Error cargando solicitudes en resumen.php: ' . $e->getMessage());
  $solicitudes = [];
}

/* =========================================================
   Adaptar solicitudes para el dashboard
========================================================= */
/* =========================================================
   Adaptar solicitudes para el dashboard
   Reglas:
   - Cancelado / rechazado => Cancelado
   - Sin folio => Pendiente
   - Con folio y no cancelado => Capturado
   - Validado / aprobado / completo / firmado => Capturado
========================================================= */
$serverSolicitudRows = array_map(static function(array $r) use ($BASE_URL): array {
  $id = (int)($r['id'] ?? 0);

  $folioReal = trim((string)($r['folio_real'] ?? ''));
  $folioMostrar = trim((string)($r['folio_mostrar'] ?? ''));

  if ($folioMostrar === '') {
    $folioMostrar = $folioReal !== '' ? $folioReal : 'Sin folio #' . $id;
  }

  $estadoRaw = trim((string)($r['estado_validacion'] ?? ''));
  $estadoNorm = mb_strtolower($estadoRaw, 'UTF-8');

  /*
    Orden correcto:
    1. Si está cancelado/rechazado, siempre Cancelado.
    2. Si no tiene folio, Pendiente.
    3. Todo lo demás con folio, Capturado.
  */
  if (
    str_contains($estadoNorm, 'cancel') ||
    str_contains($estadoNorm, 'rechaz')
  ) {
    $estadoFinal = 'Cancelado';

  } elseif ($folioReal === '') {
    $estadoFinal = 'Pendiente';

  } else {
    $estadoFinal = 'Capturado';
  }

  return [
    'id' => $id,
    'tipo' => 'solicitud',
    'folio' => $folioMostrar,
    'cliente' => 'Solicitud ' . $folioMostrar,
    'asesor' => (string)($r['asesor_nombre'] ?? 'Sin asesor'),
    'monto' => (float)($r['monto'] ?? 0),
    'fecha' => (string)($r['fecha_solicitud'] ?? ''),
    'estado' => $estadoFinal,
    'url' => $BASE_URL . '/contrato/contrato.php?id=' . $id,
  ];
}, $solicitudes);
?>

<!doctype html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8">
  <title>Centro de Instrumentos CIP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
:root{
  --bg:#06101f;
  --bg2:#071a34;
  --panel:#0b1a2e;
  --panel2:#0f223a;
  --panel3:#112844;
  --line:rgba(75,151,255,.28);
  --line2:rgba(148,163,184,.22);
  --text:#f8fafc;
  --muted:#9fb3ca;
  --blue:#268bff;
  --cyan:#22d3ee;
  --green:#22c55e;
  --yellow:#fbbf24;
  --red:#ef4444;
  --purple:#8b5cf6;
  --shadow:0 20px 60px rgba(0,0,0,.28);
  --radius:18px;
  --sidebar:270px;
}
:root[data-theme="light"]{
  --bg:#edf4fb;
  --bg2:#f8fafc;
  --panel:#ffffff;
  --panel2:#f8fafc;
  --panel3:#eff6ff;
  --line:rgba(37,99,235,.22);
  --line2:rgba(100,116,139,.18);
  --text:#0f172a;
  --muted:#475569;
  --shadow:0 12px 32px rgba(15,23,42,.12);
}
*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background:
    radial-gradient(1000px 420px at 20% -10%, rgba(38,139,255,.20), transparent 60%),
    radial-gradient(900px 520px at 92% 10%, rgba(34,211,238,.12), transparent 52%),
    linear-gradient(135deg,var(--bg),var(--bg2));
  color:var(--text);
  padding:0;
}
.app{display:grid;grid-template-columns:var(--sidebar) minmax(0,1fr);min-height:100vh;}
.sidebar{
  position:sticky;top:0;height:100vh;padding:22px 18px;border-right:1px solid var(--line);
  background:linear-gradient(180deg, rgba(3,10,22,.84), rgba(4,16,32,.88));
  box-shadow:inset -1px 0 0 rgba(255,255,255,.03);
  display:flex;flex-direction:column;gap:18px;
}
:root[data-theme="light"] .sidebar{background:#fff;}
.brand{display:flex;align-items:center;gap:10px;padding:6px 4px 18px;border-bottom:1px solid var(--line2);}
.brand-logo{font-size:30px;font-weight:950;letter-spacing:-.08em;color:#fff;line-height:1;text-shadow:0 8px 24px rgba(38,139,255,.45);}
:root[data-theme="light"] .brand-logo{color:#0f172a;text-shadow:none}
.brand-text{display:flex;flex-direction:column;line-height:1.05;color:#93c5fd;font-size:12px;font-weight:700;}
.user-card{display:flex;align-items:center;gap:12px;border:1px solid var(--line);background:rgba(255,255,255,.04);border-radius:16px;padding:12px;}
.avatar{width:42px;height:42px;border-radius:14px;display:grid;place-items:center;background:linear-gradient(135deg,var(--blue),#1646c7);font-weight:900;}
.user-card strong{display:block;font-size:13px}.user-card span{display:block;color:var(--muted);font-size:12px;margin-top:2px}
.nav{display:flex;flex-direction:column;gap:8px;margin-top:4px;}
.nav a,.nav button{border:1px solid transparent;background:transparent;color:var(--text);display:flex;align-items:center;gap:12px;width:100%;padding:11px 12px;border-radius:14px;text-decoration:none;cursor:pointer;font-weight:750;text-align:left;}
.nav a:hover,.nav button:hover{background:rgba(38,139,255,.10);border-color:var(--line)}
.nav .active{background:linear-gradient(135deg,#137cff,#0f5fe2);box-shadow:0 14px 35px rgba(38,139,255,.28);}
.nav .danger{margin-top:auto;color:#fecaca}.nav .danger:hover{background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.35)}
.sidebar-bottom{margin-top:auto;border-top:1px solid var(--line2);padding-top:16px;}
.main{padding:22px;min-width:0;}
.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:18px;}
.title h1{font-size:30px;letter-spacing:-.04em;line-height:1.05}.title p{color:var(--muted);font-size:14px;margin-top:6px}
.top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.pill{height:42px;padding:0 14px;border-radius:14px;border:1px solid var(--line);background:rgba(255,255,255,.04);display:inline-flex;align-items:center;gap:8px;color:var(--text);font-weight:750;}
.btn-primary{height:42px;padding:0 18px;border-radius:14px;border:none;background:linear-gradient(135deg,#1687ff,#0b61e8);color:white;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 14px 35px rgba(38,139,255,.25);cursor:pointer;}
.dashboard{display:grid;grid-template-columns:minmax(0,1fr) 320px;gap:16px;align-items:start;}
.left{display:flex;flex-direction:column;gap:16px;min-width:0}.right{display:flex;flex-direction:column;gap:16px;}
.kpis{display:grid;grid-template-columns:repeat(5,minmax(160px,1fr));gap:12px;}
.kpi{position:relative;overflow:hidden;min-height:104px;border:1px solid var(--line);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));border-radius:var(--radius);padding:16px;box-shadow:var(--shadow);}
.kpi:after{content:"";position:absolute;right:-38px;bottom:-50px;width:125px;height:125px;border-radius:50%;background:rgba(38,139,255,.13)}
.kpi .row{display:flex;align-items:center;gap:12px}.kpi-ico{width:46px;height:46px;border-radius:15px;display:grid;place-items:center;font-size:21px;background:rgba(38,139,255,.20);color:#93c5fd;border:1px solid rgba(38,139,255,.26)}
.kpi.green .kpi-ico{background:rgba(34,197,94,.17);color:#86efac}.kpi.cyan .kpi-ico{background:rgba(34,211,238,.16);color:#67e8f9}.kpi.yellow .kpi-ico{background:rgba(251,191,36,.16);color:#fde68a}.kpi.purple .kpi-ico{background:rgba(139,92,246,.18);color:#c4b5fd}
.kpi small{display:block;color:var(--muted);font-size:12px}.kpi strong{display:block;font-size:22px;margin-top:4px}.trend{margin-top:10px;font-size:12px;color:#22c55e;font-weight:900}.trend.down{color:#fb7185}
.filters{display:grid;grid-template-columns:minmax(240px,1.5fr) repeat(3,minmax(160px,.7fr)) auto;gap:10px;border:1px solid var(--line);background:rgba(255,255,255,.035);border-radius:var(--radius);padding:12px;}
.input,.select{width:100%;height:42px;border-radius:13px;border:1px solid var(--line2);background:rgba(2,6,23,.25);color:var(--text);padding:0 12px;outline:none;}
:root[data-theme="light"] .input,:root[data-theme="light"] .select{background:#fff}.input::placeholder{color:var(--muted)}
.btn-ghost{height:42px;border-radius:13px;border:1px solid var(--line);background:rgba(38,139,255,.08);color:var(--text);font-weight:850;padding:0 14px;cursor:pointer;}
.tabs{display:grid;grid-template-columns:repeat(5,1fr);gap:0;border:1px solid var(--line);background:rgba(255,255,255,.035);border-radius:16px;padding:5px;overflow:hidden;}
.tab{border:0;background:transparent;color:var(--muted);font-weight:900;border-radius:12px;padding:10px;cursor:pointer}.tab.active{background:linear-gradient(135deg,#1687ff,#0b61e8);color:#fff;}
.grid-charts{display:grid;grid-template-columns:1.1fr .9fr .9fr;gap:12px;}
.panel{border:1px solid var(--line);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));border-radius:var(--radius);box-shadow:var(--shadow);padding:14px;min-width:0;}
.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}.panel h2{font-size:15px}.panel p{color:var(--muted);font-size:12px;margin-top:2px}.panel-link{color:#60a5fa;font-size:12px;font-weight:900;text-decoration:none}
.chart-box{height:220px;position:relative}.chart-box.sm{height:190px}.chart-box.donut{height:220px;}
.table-panel{padding:0;overflow:hidden}.table-head{display:flex;align-items:center;justify-content:space-between;padding:14px;border-bottom:1px solid var(--line2)}.table-title h2{font-size:15px}.table-title p{color:var(--muted);font-size:12px;margin-top:2px}
.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse;font-size:13px}thead{background:rgba(255,255,255,.045)}th,td{padding:12px 14px;border-bottom:1px solid var(--line2);text-align:left;white-space:nowrap}th{font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:#cbd5e1}tbody tr:hover{background:rgba(38,139,255,.08)}
.tipo-badge,.estado{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:12px;font-weight:900;border:1px solid var(--line2)}.tipo-solicitud{color:#fbbf24;background:rgba(251,191,36,.12)}.tipo-ahorro{color:#67e8f9;background:rgba(34,211,238,.12)}.tipo-inversion{color:#60a5fa;background:rgba(96,165,250,.12)}.tipo-complemento{color:#c084fc;background:rgba(192,132,252,.12)}
.estado{color:#bfdbfe;background:rgba(59,130,246,.12)}.estado.cancelado,.estado.vencido{color:#fecaca;background:rgba(239,68,68,.12)}.estado.firmado,.estado.activo,.estado.colocado,.estado.completo{color:#bbf7d0;background:rgba(34,197,94,.12)}.estado.pendiente,.estado.por-vencer{color:#fde68a;background:rgba(251,191,36,.12)}
.actions{display:flex;justify-content:flex-end;gap:8px}.icon-btn,.btn-sm{border:1px solid var(--line2);background:rgba(255,255,255,.045);color:var(--text);height:32px;border-radius:10px;padding:0 10px;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;cursor:pointer;font-weight:850}.btn-sm:hover,.icon-btn:hover{background:rgba(38,139,255,.12);border-color:var(--line)}
.table-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 14px;color:var(--muted);font-size:12px}.pager{display:flex;gap:6px}.page-btn{min-width:34px;height:34px;border-radius:10px;border:1px solid var(--line2);background:rgba(255,255,255,.04);color:var(--text);cursor:pointer}.page-btn.active{background:#137cff;color:white;border-color:#137cff}
.side-card{border:1px solid var(--line);background:linear-gradient(180deg,rgba(255,255,255,.06),rgba(255,255,255,.025));border-radius:var(--radius);box-shadow:var(--shadow);padding:14px}.rank-item,.activity-item,.pending-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line2)}.rank-item:last-child,.activity-item:last-child,.pending-item:last-child{border-bottom:0}.rank-num{width:28px;height:28px;border-radius:10px;display:grid;place-items:center;background:rgba(38,139,255,.18);font-weight:900}.rank-info{min-width:0;flex:1}.rank-info strong,.activity-info strong{display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.rank-info span,.activity-info span{display:block;color:var(--muted);font-size:11px;margin-top:2px}.rank-money{font-size:12px;font-weight:950;color:#bfdbfe}.act-icon{width:34px;height:34px;border-radius:12px;display:grid;place-items:center;background:rgba(38,139,255,.18)}.act-icon.red{background:rgba(239,68,68,.14);color:#fecaca}.act-icon.green{background:rgba(34,197,94,.14);color:#bbf7d0}.pending-card{border-color:rgba(251,191,36,.42);background:linear-gradient(180deg,rgba(251,191,36,.10),rgba(255,255,255,.025));}.progress{height:8px;flex:1;background:rgba(148,163,184,.22);border-radius:999px;overflow:hidden}.progress span{display:block;height:100%;background:linear-gradient(90deg,#fbbf24,#fde68a)}
.mobile-toggle{display:none}.empty{padding:20px;text-align:center;color:var(--muted)}
@media(max-width:1280px){.dashboard{grid-template-columns:1fr}.right{display:grid;grid-template-columns:repeat(3,1fr)}.kpis{grid-template-columns:repeat(3,1fr)}.grid-charts{grid-template-columns:1fr 1fr}.grid-charts .panel:last-child{grid-column:1/-1}}
@media(max-width:900px){.app{grid-template-columns:1fr}.sidebar{position:fixed;z-index:80;left:12px;top:12px;height:calc(100vh - 24px);width:min(290px,calc(100vw - 24px));transform:translateX(-112%);transition:.18s ease}.sidebar.show{transform:translateX(0)}.mobile-toggle{display:inline-flex}.main{padding:16px}.topbar{align-items:center}.filters{grid-template-columns:1fr}.tabs{grid-template-columns:1fr 1fr}.kpis{grid-template-columns:1fr}.grid-charts{grid-template-columns:1fr}.right{grid-template-columns:1fr}.backdrop{display:none;position:fixed;inset:0;background:rgba(2,6,23,.72);z-index:70}.backdrop.show{display:block}}
  

/* =========================================================
   FIX LAYOUT FINAL
   Evita que el sidebar se encime con el contenido principal
   y permite que el dashboard use todo el ancho disponible.
========================================================= */
html, body{
  width:100%;
  min-height:100%;
  overflow-x:hidden;
}

.app{
  display:block !important;
  width:100% !important;
  min-height:100vh !important;
}

.sidebar{
  position:fixed !important;
  top:0 !important;
  left:0 !important;
  bottom:0 !important;
  width:var(--sidebar) !important;
  height:100vh !important;
  z-index:80 !important;
  overflow-y:auto !important;
}

.main{
  width:calc(100% - var(--sidebar)) !important;
  max-width:none !important;
  min-height:100vh !important;
  margin-left:var(--sidebar) !important;
  padding:26px 28px 32px !important;
}

.topbar,
.dashboard,
.left,
.right,
.table-panel,
.panel,
.filterbar{
  max-width:none !important;
}

.dashboard{
  grid-template-columns:minmax(0, 1fr) 330px !important;
  align-items:start !important;
}

.kpis{
  grid-template-columns:repeat(5, minmax(150px, 1fr)) !important;
}

.charts{
  grid-template-columns:minmax(0, 1.05fr) minmax(0, .95fr) minmax(0, .95fr) !important;
}

.table-wrap{
  width:100% !important;
  overflow:auto !important;
}

.table-wrap table{
  min-width:980px !important;
}

@media (max-width:1400px){
  .dashboard{
    grid-template-columns:1fr !important;
  }
  .right{
    display:grid !important;
    grid-template-columns:repeat(3, minmax(0,1fr)) !important;
  }
}

@media (max-width:1100px){
  .sidebar{
    transform:translateX(-105%) !important;
    transition:transform .18s ease !important;
  }
  .sidebar.show{
    transform:translateX(0) !important;
  }
  .main{
    margin-left:0 !important;
    width:100% !important;
    padding:18px 14px 24px !important;
  }
  .right{
    grid-template-columns:1fr !important;
  }
  .kpis{
    grid-template-columns:repeat(2, minmax(0,1fr)) !important;
  }
  .charts{
    grid-template-columns:1fr !important;
  }
}

@media (max-width:640px){
  .kpis{
    grid-template-columns:1fr !important;
  }
  .topbar{
    flex-direction:column !important;
    align-items:stretch !important;
  }
  .top-actions{
    justify-content:flex-start !important;
  }
  .filterbar{
    grid-template-columns:1fr !important;
  }
}

</style>
</head>
<body>
  <div class="app">
    <aside class="sidebar" id="sidebar">
      <div class="brand">
        <div class="brand-logo">CIP</div>
        <div class="brand-text"><span>Financial</span><span>México</span></div>
      </div>

      <div class="user-card">
        <div class="avatar"><?= e(strtoupper(mb_substr($asesorNombreSidebar, 0, 1, 'UTF-8'))) ?></div>
        <div><strong><?= e($asesorNombreSidebar) ?></strong><span>Director general · En línea</span></div>
      </div>

      <nav class="nav">
        <button class="active" type="button"><span>🏠</span><span>Dashboard</span></button>
        <a href="<?= e($BASE_URL) ?>/home.php"><span>⬅️</span><span>Regresar al panel</span></a>
        <button type="button" onclick="document.getElementById('filtro-busqueda')?.focus()"><span>🔎</span><span>Buscar instrumento</span></button>
        <button id="btnTema2" type="button" onclick="toggleTheme()"><span class="ico">🌙</span><span class="txt">Modo oscuro</span></button>
        <div class="sidebar-bottom">
          <button class="danger" type="button" onclick="cerrarSesion()"><span>⎋</span><span>Cerrar sesión</span></button>
        </div>
      </nav>
    </aside>

    <div class="backdrop" id="backdrop" onclick="toggleSidebar(false)"></div>

    <main class="main">
      <div class="topbar">
        <div class="title">
          <button class="pill mobile-toggle" type="button" onclick="toggleSidebar(true)">☰</button>
          <h1>Centro de Instrumentos CIP</h1>
          <p>Vista general de Solicitud, Ahorro, Inversión y Complemento.</p>
        </div>
        <div class="top-actions">
          <div class="pill">🔔 <span id="notifCount">0</span></div>
          <div class="pill">🗓️ <span id="todayLabel">—</span></div>
          <button class="btn-primary" type="button" onclick="exportarCSV()">⬇ Exportar reporte</button>
        </div>
      </div>

      <div class="dashboard">
        <section class="left">
          <div class="kpis">
            <article class="kpi"><div class="row"><div class="kpi-ico">▦</div><div><small>Total instrumentos</small><strong id="kpiTotal">0</strong></div></div><div class="trend">↑ Control global</div></article>
            <article class="kpi green"><div class="row"><div class="kpi-ico">$</div><div><small>Monto total colocado</small><strong id="kpiMonto">$0.00</strong></div></div><div class="trend">↑ Suma general</div></article>
            <article class="kpi purple"><div class="row"><div class="kpi-ico">👥</div><div><small>Asesores activos</small><strong id="kpiAsesores">0</strong></div></div><div class="trend">↑ Equipo operativo</div></article>
            <article class="kpi cyan"><div class="row"><div class="kpi-ico">👤</div><div><small>Clientes únicos</small><strong id="kpiClientes">0</strong></div></div><div class="trend">↑ Cartera</div></article>
            <article class="kpi yellow"><div class="row"><div class="kpi-ico">⏳</div><div><small>Pendientes</small><strong id="kpiPendientes">0</strong></div></div><div class="trend down">↓ Requieren atención</div></article>
          </div>

          <div class="filters">
            <input class="input" id="filtro-busqueda" type="text" placeholder="Buscar cliente, folio o asesor...">
            <select class="select" id="filtro-asesor"><option value="">Todos los asesores</option></select>
            <select class="select" id="filtro-tipo"><option value="todos">Todos los instrumentos</option><option value="solicitud">Solicitud</option><option value="ahorro">Ahorro</option><option value="inversion">Inversión</option><option value="complemento">Complemento</option></select>
            <select class="select" id="filtro-estado"><option value="">Todos los estados</option><option value="capturado">Capturado</option><option value="firmado">Firmado</option><option value="completo">Completo</option><option value="pendiente">Pendiente</option><option value="por vencer">Por vencer</option><option value="cancelado">Cancelado</option></select>
            <button class="btn-ghost" type="button" onclick="limpiarFiltros()">↻ Limpiar</button>
          </div>

          <div class="tabs" id="tipoTabs">
            <button class="tab" data-tipo="todos" type="button">Todos</button>
            <button class="tab" data-tipo="solicitud" type="button">Solicitud</button>
            <button class="tab" data-tipo="ahorro" type="button">Ahorro</button>
            <button class="tab" data-tipo="inversion" type="button">Inversión</button>
            <button class="tab" data-tipo="complemento" type="button">Complemento</button>
          </div>

          <div class="grid-charts">
            <article class="panel">
              <div class="panel-head"><div><h2>Registros por asesor</h2><p>Número de instrumentos registrados</p></div><a href="#tabla" class="panel-link">Ver detalles</a></div>
              <div class="chart-box"><canvas id="chartAsesores"></canvas></div>
            </article>
            <article class="panel">
              <div class="panel-head"><div><h2>Distribución por estado</h2><p>Participación de instrumentos</p></div><a href="#tabla" class="panel-link">Ver detalles</a></div>
              <div class="chart-box donut"><canvas id="chartEstados"></canvas></div>
            </article>
            <article class="panel">
              <div class="panel-head"><div><h2>Monto por instrumento</h2><p>Comparativo del monto colocado</p></div><a href="#tabla" class="panel-link">Ver detalles</a></div>
              <div class="chart-box sm"><canvas id="chartMontos"></canvas></div>
            </article>
          </div>

          <article class="panel table-panel" id="tabla">
            <div class="table-head">
              <div class="table-title"><h2>Detalle de instrumentos</h2><p><span id="resultCount">0</span> registros encontrados</p></div>
              <div><button class="btn-ghost" type="button" onclick="exportarCSV()">⬇ Descargar</button></div>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr><th>Tipo</th><th>Folio</th><th>Cliente</th><th>Asesor</th><th>Monto</th><th>Fecha</th><th>Estado</th><th style="text-align:right">Acciones</th></tr>
                </thead>
                <tbody id="tablaInstrumentos"><tr><td colspan="8" class="empty">Cargando información...</td></tr></tbody>
              </table>
            </div>
            <div class="table-foot"><div id="tableInfo">Mostrando 0 registros</div><div class="pager" id="pager"></div></div>
          </article>
        </section>

        <aside class="right">
          <section class="side-card"><div class="panel-head"><h2>Top asesores</h2><a href="#tabla" class="panel-link">Ver ranking</a></div><div id="rankingAsesores"></div></section>
          <section class="side-card"><div class="panel-head"><h2>Actividad reciente</h2><a href="#tabla" class="panel-link">Ver todas</a></div><div id="actividadReciente"></div></section>
          <section class="side-card pending-card"><div class="panel-head"><h2>Pendientes importantes</h2><a href="#tabla" class="panel-link">Ver todos</a></div><div id="pendientesImportantes"></div></section>
        </aside>
      </div>
    </main>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* =========================================================
   CONFIGURACIÓN GLOBAL
========================================================= */
const BASE_URL = <?= json_encode($BASE_URL, JSON_UNESCAPED_SLASHES) ?>;
const TIPO_INICIAL = <?= json_encode($tipo) ?>;
const SERVER_SOLICITUDES = <?= json_encode($serverSolicitudRows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const API_GET_AHORROS = `${BASE_URL}/app/controllers/resumen/get_ahorros.php`;
const API_GET_INVERSIONES = `${BASE_URL}/app/controllers/resumen/get_inversiones.php`;
const API_GET_COMPLEMENTO = `${BASE_URL}/app/controllers/resumen/get_complemento.php`;
const URL_LOGOUT = `${BASE_URL}/app/controllers/auth/logout.php`;

const THEME_KEY = 'cip_theme';

const state = {
  all: [],
  filtered: [],
  page: 1,
  per: 8,
  loaded: {
    solicitud: true,
    ahorro: false,
    inversion: false,
    complemento: false
  }
};

let chartAsesores = null;
let chartEstados = null;
let chartMontos = null;


/* =========================================================
   HELPERS
========================================================= */
function esc(s) {
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  }[m]));
}

function money(n) {
  const num = Number(n || 0);

  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2
  }).format(num);
}

function parseDate(v) {
  if (!v) return null;

  const d = new Date(String(v).replace(' ', 'T'));
  return isNaN(d.getTime()) ? null : d;
}

function fmtDate(v) {
  const d = parseDate(v);
  return d ? d.toLocaleDateString('es-MX') : (v || '—');
}

function norm(s) {
  return String(s ?? '')
    .trim()
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function esRegistroSumable(r) {
  const estado = norm(r.estado);
  const folio = norm(r.folio);

  // No sumar cancelados o rechazados
  if (
    estado.includes('cancel') ||
    estado.includes('rechaz')
  ) {
    return false;
  }

  // No sumar pendientes
  if (
    estado.includes('pendiente') ||
    estado.includes('por vencer') ||
    estado.includes('revision') ||
    estado.includes('proceso')
  ) {
    return false;
  }

  // No sumar solicitudes sin folio
  if (folio.includes('sin folio')) {
    return false;
  }

  return true;
}

function tipoLabel(t) {
  return ({
    solicitud: 'Solicitud',
    ahorro: 'Ahorro',
    inversion: 'Inversión',
    complemento: 'Complemento'
  }[t] || t);
}

function iconTipo(t) {
  return ({
    solicitud: '📄',
    ahorro: '💰',
    inversion: '📈',
    complemento: '🧩'
  }[t] || '▦');
}

function estadoClass(e) {
  const v = norm(e).replace(/\s+/g, '-');
  return `estado ${v}`;
}

function isComplementoCancelado(r) {
  const estatus = norm(r.estatus || r.estado || r.firma_contrato || '');
  const canceladoPor = String(r.cancelado_por || '').trim();
  const canceladoAt = String(r.cancelado_at || '').trim();
  const motivoCancelacion = String(r.motivo_cancelacion || '').trim();

  return (
    estatus.includes('cancel') ||
    canceladoPor !== '' ||
    canceladoAt !== '' ||
    motivoCancelacion !== ''
  );
}

function normalizarEstadoInstrumento(rawEstado, tipo = '', row = {}) {
  const tipoNorm = norm(tipo);
  let estado = norm(rawEstado);

  /*
    ✅ COMPLEMENTO:
    Tu tabla cipcom tiene:
    estatus, cancelado_at, cancelado_por, motivo_cancelacion.
    Entonces:
    - Si está cancelado -> Cancelado
    - Si no -> Activo
  */
  if (tipoNorm === 'complemento') {
    return isComplementoCancelado(row) ? 'Cancelado' : 'Activo';
  }

  if (!estado || estado === '—' || estado === '-') {
    return 'Pendiente';
  }

  if (estado.includes('cancel')) {
    return 'Cancelado';
  }

  if (
    estado.includes('firmado') ||
    estado.includes('validado') ||
    estado.includes('completo') ||
    estado.includes('aprobado') ||
    estado.includes('aceptado') ||
    estado.includes('listo')
  ) {
    return 'Listo';
  }

  if (
    estado.includes('activo') ||
    estado.includes('capturado') ||
    estado.includes('registrado')
  ) {
    return 'Capturado';
  }

  if (
    estado.includes('pendiente') ||
    estado.includes('revision') ||
    estado.includes('revisión') ||
    estado.includes('proceso') ||
    estado.includes('por vencer')
  ) {
    return 'Pendiente';
  }

  /*
    Evita que fechas o meses entren como estados en la dona:
    "Miércoles, 17 de diciembre", "Febrero 2026", etc.
  */
  const mesesDias = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
    'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'
  ];

  if (mesesDias.some(m => estado.includes(m)) || /\d{4}/.test(estado)) {
    return 'Pendiente';
  }

  return 'Pendiente';
}

function getEstadoGrafica(row) {
  return normalizarEstadoInstrumento(
    row.estado || row.estado_validacion || row.firma_contrato || row.status || row.estatus || '',
    row.tipo,
    row
  );
}


/* =========================================================
   NORMALIZADORES DE DATOS
========================================================= */
function normalizeAhorro(r) {
  const nombre = [r.nombre, r.ap_paterno, r.ap_materno]
    .filter(Boolean)
    .join(' ')
    .trim() || '—';

  const estado = normalizarEstadoInstrumento(r.estado || 'Activo', 'ahorro', r);

  return {
    id: r.id ?? r.id_ahorro ?? r.ahorro_id ?? '',
    tipo: 'ahorro',
    folio: r.folio || ('AH-' + (r.id || '')),
    cliente: nombre,
    asesor: r.asesor_nombre || '—',
    monto: Number(r.monto_semanal || r.monto || 0),
    fecha: r.creado_en || r.fecha_inicio_ahorro || '',
    estado,
    raw: r,
    url: `${BASE_URL}/ahorros.php?id=${encodeURIComponent(r.id ?? '')}`
  };
}

function normalizeInversion(r) {
  const nombre = [r.nombre, r.ap_paterno, r.ap_materno]
    .filter(Boolean)
    .join(' ')
    .trim() || '—';

  const estado = normalizarEstadoInstrumento(r.estado || 'Capturado', 'inversion', r);

  return {
    id: r.id ?? '',
    tipo: 'inversion',
    folio: r.folio || ('INV-' + (r.id || '')),
    cliente: nombre,
    asesor: r.asesor_nombre || '—',
    monto: Number(r.monto || 0),
    fecha: r.fecha_solicitud || r.creado_en || '',
    estado,
    raw: r,
    url: `${BASE_URL}/inversion_dashboard.php?id=${encodeURIComponent(r.id ?? '')}`
  };
}

function normalizeComplemento(r) {
  const cid = r.id ?? r.cipcom_id ?? r.user_id ?? '';

  const estado = isComplementoCancelado(r) ? 'Cancelado' : 'Activo';

  return {
    id: cid,
    tipo: 'complemento',
    folio: r.folio || r.user_id || ('COM-' + cid),
    cliente: (r.nombre_completo || '—').toString().trim() || '—',
    asesor: r.asesor_nombre || '—',
    monto: Number(r.ingreso_capital || r.monto || 0),
    fecha: r.created_at || '',
    estado,
    raw: r,
    url: `${BASE_URL}/app/controllers/complemento/complemento.html?cid=${encodeURIComponent(cid)}&mode=view`
  };
}

function normalizeSolicitud(r) {
  const estado = normalizarEstadoInstrumento(r.estado || 'Pendiente', 'solicitud', r);

  return {
    ...r,
    tipo: 'solicitud',
    estado,
    raw: r
  };
}


/* =========================================================
   FETCH
========================================================= */
async function fetchJson(url) {
  const res = await fetch(url, {
    credentials: 'same-origin',
    cache: 'no-store'
  });

  const ct = res.headers.get('content-type') || '';

  if (!res.ok) {
    throw new Error(`HTTP ${res.status} - ${url}`);
  }

  if (!ct.includes('application/json')) {
    throw new Error(`Respuesta no es JSON - ${url}`);
  }

  return await res.json();
}

async function cargarTipo(tipo) {
  if (state.loaded[tipo]) return;

  try {
    if (tipo === 'ahorro') {
      const data = await fetchJson(API_GET_AHORROS);
      const rows = Array.isArray(data.rows) ? data.rows : [];
      state.all.push(...rows.map(normalizeAhorro));
    }

    if (tipo === 'inversion') {
      const data = await fetchJson(API_GET_INVERSIONES);
      const rows = Array.isArray(data.rows) ? data.rows : [];
      state.all.push(...rows.map(normalizeInversion));
    }

    if (tipo === 'complemento') {
      const url = new URL(API_GET_COMPLEMENTO, location.origin);
      url.searchParams.set('page', '1');
      url.searchParams.set('page_size', '500');

      const data = await fetchJson(url.toString());
      const rows = Array.isArray(data.rows) ? data.rows : [];

      state.all.push(...rows.map(normalizeComplemento));
    }

    state.loaded[tipo] = true;

  } catch (e) {
    console.warn('No se pudo cargar ' + tipo, e);
    state.loaded[tipo] = true;
  }
}

async function cargarTodo() {
  state.all = SERVER_SOLICITUDES.map(normalizeSolicitud);

  await Promise.all([
    cargarTipo('ahorro'),
    cargarTipo('inversion'),
    cargarTipo('complemento')
  ]);

  llenarAsesores();
  aplicarFiltros();
}


/* =========================================================
   FILTROS
========================================================= */
function llenarAsesores() {
  const sel = document.getElementById('filtro-asesor');
  if (!sel) return;

  const actual = sel.value;

  const asesores = [...new Set(
    state.all
      .map(r => r.asesor)
      .filter(Boolean)
      .filter(a => a !== '—')
  )].sort((a, b) => a.localeCompare(b));

  sel.innerHTML = '<option value="">Todos los asesores</option>' +
    asesores.map(a => `<option value="${esc(a)}">${esc(a)}</option>`).join('');

  sel.value = actual;
}

function aplicarFiltros() {
  const q = norm(document.getElementById('filtro-busqueda')?.value || '');
  const tipo = document.getElementById('filtro-tipo')?.value || 'todos';
  const estado = norm(document.getElementById('filtro-estado')?.value || '');
  const asesor = document.getElementById('filtro-asesor')?.value || '';

  state.filtered = state.all.filter(r => {
    const texto = norm(`${r.tipo} ${r.folio} ${r.cliente} ${r.asesor} ${r.monto} ${r.estado}`);

    const okQ = !q || texto.includes(q);
    const okTipo = tipo === 'todos' || r.tipo === tipo;
    const okEstado = !estado || norm(r.estado).includes(estado);
    const okAsesor = !asesor || r.asesor === asesor;

    return okQ && okTipo && okEstado && okAsesor;
  });

  state.page = 1;
  renderAll();
}

function limpiarFiltros() {
  const q = document.getElementById('filtro-busqueda');
  const asesor = document.getElementById('filtro-asesor');
  const tipo = document.getElementById('filtro-tipo');
  const estado = document.getElementById('filtro-estado');

  if (q) q.value = '';
  if (asesor) asesor.value = '';
  if (tipo) tipo.value = 'todos';
  if (estado) estado.value = '';

  aplicarFiltros();
}


/* =========================================================
   RENDER GENERAL
========================================================= */
function renderAll() {
  renderKPIs();
  renderTabs();
  renderCharts();
  renderTabla();
  renderSidePanels();
}

function renderKPIs() {
  const rows = state.filtered;
const rowsSumables = rows.filter(esRegistroSumable);

const monto = rowsSumables.reduce((a, r) => a + Number(r.monto || 0), 0);
  const asesores = new Set(rows.map(r => r.asesor).filter(a => a && a !== '—'));
  const clientes = new Set(rows.map(r => norm(r.cliente)).filter(Boolean));

  const pendientes = rows.filter(r => {
    const e = norm(r.estado);
    return ['pendiente', 'por vencer', 'en proceso', 'revision', 'revisión'].some(x => e.includes(norm(x)));
  }).length;

  document.getElementById('kpiTotal').textContent = String(rows.length);
  document.getElementById('kpiMonto').textContent = money(monto);
  document.getElementById('kpiAsesores').textContent = String(asesores.size);
  document.getElementById('kpiClientes').textContent = String(clientes.size);
  document.getElementById('kpiPendientes').textContent = String(pendientes);
  document.getElementById('notifCount').textContent = String(pendientes);
}

function renderTabs() {
  const active = document.getElementById('filtro-tipo')?.value || 'todos';

  document.querySelectorAll('#tipoTabs .tab').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tipo === active);
  });
}

function groupBy(rows, keyFn, valFn = () => 1) {
  return rows.reduce((acc, r) => {
    const k = keyFn(r) || '—';
    acc[k] = (acc[k] || 0) + valFn(r);
    return acc;
  }, {});
}


/* =========================================================
   GRÁFICAS
========================================================= */
function chartColors() {
  const dark = document.documentElement.getAttribute('data-theme') !== 'light';

  return {
    text: dark ? '#cbd5e1' : '#334155',
    grid: dark ? 'rgba(148,163,184,.15)' : 'rgba(148,163,184,.28)'
  };
}

function renderCharts() {
  if (typeof Chart === 'undefined') return;

  const { text, grid } = chartColors();
  const rows = state.filtered;

  renderChartAsesores(rows, text, grid);
  renderChartEstados(rows, text);
  renderChartMontos(rows, text, grid);
}

function renderChartAsesores(rows, text, grid) {
  const canvas = document.getElementById('chartAsesores');
  if (!canvas) return;

  const porAsesor = Object.entries(groupBy(rows, r => r.asesor))
    .sort((a, b) => b[1] - a[1])
    .slice(0, 7);

  if (chartAsesores) chartAsesores.destroy();

  chartAsesores = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: porAsesor.map(x => x[0]),
      datasets: [{
        data: porAsesor.map(x => x[1]),
        backgroundColor: '#268bff',
        borderRadius: 8
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        x: {
          ticks: { color: text },
          grid: { display: false }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: text,
            precision: 0
          },
          grid: { color: grid }
        }
      }
    }
  });
}

function renderChartEstados(rows, text) {
  const canvas = document.getElementById('chartEstados');
  if (!canvas) return;

  const estados = {};

  rows.forEach(r => {
    const estado = getEstadoGrafica(r);
    estados[estado] = (estados[estado] || 0) + 1;
  });

  const labels = Object.keys(estados);
  const values = labels.map(k => estados[k]);

  const estadoColors = {
    'Activo': '#22c55e',
    'Capturado': '#268bff',
    'Listo': '#8b5cf6',
    'Pendiente': '#fbbf24',
    'Cancelado': '#ef4444'
  };

  const colors = labels.map(k => estadoColors[k] || '#94a3b8');

  if (chartEstados) chartEstados.destroy();

  chartEstados = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: labels.length ? labels : ['Sin datos'],
      datasets: [{
        data: values.length ? values : [1],
        backgroundColor: colors.length ? colors : ['rgba(148,163,184,.25)'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '66%',
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: text,
            usePointStyle: true,
            boxWidth: 9
          }
        },
        tooltip: {
          callbacks: {
            label: ctx => `${ctx.label}: ${ctx.raw}`
          }
        }
      }
    }
  });
}

function renderChartMontos(rows, text, grid) {
  const canvas = document.getElementById('chartMontos');
  if (!canvas) return;

  const rowsSumables = rows.filter(esRegistroSumable);

  const porMontoTipo = Object.entries(
    groupBy(rowsSumables, r => tipoLabel(r.tipo), r => Number(r.monto || 0))
  );

  if (chartMontos) chartMontos.destroy();

  chartMontos = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: porMontoTipo.map(x => x[0]),
      datasets: [{
        data: porMontoTipo.map(x => x[1]),
        backgroundColor: ['#fbbf24', '#22d3ee', '#268bff', '#8b5cf6'],
        borderRadius: 8,
        minBarLength: 10
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => money(ctx.raw)
          }
        }
      },
      scales: {
        x: {
          ticks: { color: text },
          grid: { display: false }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: text,
            callback: v => money(v)
          },
          grid: { color: grid }
        }
      }
    }
  });
}


/* =========================================================
   TABLA
========================================================= */
function renderTabla() {
  const tb = document.getElementById('tablaInstrumentos');
  if (!tb) return;

  const total = state.filtered.length;
  const pages = Math.max(1, Math.ceil(total / state.per));

  if (state.page > pages) state.page = pages;

  const start = (state.page - 1) * state.per;
  const rows = state.filtered.slice(start, start + state.per);

  document.getElementById('resultCount').textContent = String(total);
  document.getElementById('tableInfo').textContent =
    `Mostrando ${rows.length ? start + 1 : 0} a ${Math.min(start + state.per, total)} de ${total} registros`;

  if (!rows.length) {
    tb.innerHTML = '<tr><td colspan="8" class="empty">Sin resultados</td></tr>';
    renderPager(pages);
    return;
  }

  tb.innerHTML = rows.map(r => `
    <tr>
      <td>
        <span class="tipo-badge tipo-${esc(r.tipo)}">
          ${iconTipo(r.tipo)} ${tipoLabel(r.tipo)}
        </span>
      </td>

      <td style="color:#7dd3fc;font-weight:850">
        ${esc(r.folio || '—')}
      </td>

      <td>${esc(r.cliente || '—')}</td>
      <td>${esc(r.asesor || '—')}</td>
      <td>${money(r.monto)}</td>
      <td>${esc(fmtDate(r.fecha))}</td>

      <td>
        <span class="${estadoClass(r.estado)}">
          ${esc(r.estado || '—')}
        </span>
      </td>

      <td>
        <div class="actions">
          <a class="btn-sm" href="${esc(r.url || '#')}">Ver</a>

          <button
            class="icon-btn"
            type="button"
            onclick="verDetalle('${esc(r.tipo)}','${esc(r.folio)}')">
            ⋯
          </button>
        </div>
      </td>
    </tr>
  `).join('');

  renderPager(pages);
}

function renderPager(pages) {
  const p = document.getElementById('pager');
  if (!p) return;

  const items = [];

  items.push(`
    <button class="page-btn" ${state.page <= 1 ? 'disabled' : ''} onclick="goPage(${state.page - 1})">
      ‹
    </button>
  `);

  for (let i = 1; i <= Math.min(pages, 5); i++) {
    items.push(`
      <button class="page-btn ${i === state.page ? 'active' : ''}" onclick="goPage(${i})">
        ${i}
      </button>
    `);
  }

  if (pages > 5) {
    items.push(`
      <button class="page-btn" disabled>...</button>
      <button class="page-btn" onclick="goPage(${pages})">${pages}</button>
    `);
  }

  items.push(`
    <button class="page-btn" ${state.page >= pages ? 'disabled' : ''} onclick="goPage(${state.page + 1})">
      ›
    </button>
  `);

  p.innerHTML = items.join('');
}

window.goPage = function(n) {
  const pages = Math.max(1, Math.ceil(state.filtered.length / state.per));
  state.page = Math.min(Math.max(1, n), pages);
  renderTabla();
};


/* =========================================================
   PANELES DERECHOS
========================================================= */
function renderSidePanels() {
  renderRankingAsesores();
  renderActividadReciente();
  renderPendientesImportantes();
}

function renderRankingAsesores() {
  const el = document.getElementById('rankingAsesores');
  if (!el) return;

  const byAdvisor = Object.entries(
    groupBy(state.filtered, r => r.asesor, r => Number(r.monto || 0))
  )
    .sort((a, b) => b[1] - a[1])
    .slice(0, 5);

  if (!byAdvisor.length) {
    el.innerHTML = '<div class="empty">Sin ranking.</div>';
    return;
  }

  el.innerHTML = byAdvisor.map((x, i) => `
    <div class="rank-item">
      <div class="rank-num">${i + 1}</div>

      <div class="rank-info">
        <strong>${esc(x[0])}</strong>
        <span>${state.filtered.filter(r => r.asesor === x[0]).length} instrumentos</span>
      </div>

      <div class="rank-money">${money(x[1])}</div>
    </div>
  `).join('');
}

function renderActividadReciente() {
  const el = document.getElementById('actividadReciente');
  if (!el) return;

  if (!state.filtered.length) {
    el.innerHTML = '<div class="empty">Sin actividad.</div>';
    return;
  }

  el.innerHTML = state.filtered.slice(0, 5).map(r => {
    const cancelado = norm(r.estado).includes('cancel');

    return `
      <div class="activity-item">
        <div class="act-icon ${cancelado ? 'red' : 'green'}">
          ${cancelado ? '×' : '+'}
        </div>

        <div class="activity-info">
          <strong>${esc(tipoLabel(r.tipo))} registrado</strong>
          <span>${esc(r.folio)} · ${esc(r.asesor)}</span>
        </div>
      </div>
    `;
  }).join('');
}

function renderPendientesImportantes() {
  const el = document.getElementById('pendientesImportantes');
  if (!el) return;

  const pendientes = state.filtered
    .filter(r => {
      const e = norm(r.estado);
      return ['pendiente', 'por vencer', 'en proceso', 'revision', 'revisión'].some(x => e.includes(norm(x)));
    })
    .slice(0, 3);

  if (!pendientes.length) {
    el.innerHTML = '<div class="empty">No hay pendientes importantes.</div>';
    return;
  }

  el.innerHTML = pendientes.map((r, i) => `
    <div class="pending-item">
      <span>⚠️</span>

      <div class="rank-info">
        <strong>${esc(r.folio)}</strong>
        <span>${esc(r.cliente)} · ${esc(r.estado)}</span>
      </div>

      <div class="progress">
        <span style="width:${[80, 55, 35][i] || 40}%"></span>
      </div>
    </div>
  `).join('');
}


/* =========================================================
   ACCIONES
========================================================= */
/* =========================================================
   REPORTE EXCEL EJECUTIVO CON GRÁFICAS VISUALES
   1) Reemplaza tu función exportarCSV() por este bloque.
   2) Pega los helpers al final del JS, antes de verDetalle().
========================================================= */

/* =========================================================
   EXPORTADOR EXCEL EJECUTIVO CIP - IMPRIMIBLE
   Reemplaza desde:
   function exportarCSV() { ... }
   hasta antes de:
   function verDetalle(tipo, folio) { ... }

   Genera:
   1) Dashboard Ejecutivo listo para imprimir
   2) Resumenes
   3) Solicitudes
   4) Ahorro
   5) Inversion
   6) Complemento
   7) Detalle completo
========================================================= */

function exportarCSV() {
  exportarExcelEjecutivoCIP();
}

function exportarExcelEjecutivoCIP() {
  const rows = state.filtered || [];

  if (!rows.length) {
    alert('No hay datos para exportar.');
    return;
  }

  const fechaReporte = new Date().toLocaleString('es-MX');
  const fechaArchivo = new Date().toISOString().slice(0, 10);

  const filtroTipoRaw = document.getElementById('filtro-tipo')?.value || 'todos';
  const filtroTipo = filtroTipoRaw === 'todos' ? 'Todos' : tipoLabel(filtroTipoRaw);
  const filtroEstado = document.getElementById('filtro-estado')?.value || 'Todos';
  const filtroAsesor = document.getElementById('filtro-asesor')?.value || 'Todos';
  const filtroBusqueda = document.getElementById('filtro-busqueda')?.value || 'Sin búsqueda';

  const rowsSumables = rows.filter(esRegistroSumable);
  const rowsExcluidos = rows.filter(r => !esRegistroSumable(r));

  const totalInstrumentos = rows.length;
  const totalSumables = rowsSumables.length;
  const montoTotal = rowsSumables.reduce((sum, r) => sum + Number(r.monto || 0), 0);
  const montoExcluido = rowsExcluidos.reduce((sum, r) => sum + Number(r.monto || 0), 0);
  const asesoresActivos = new Set(rows.map(r => r.asesor).filter(a => a && a !== '—')).size;
  const clientesUnicos = new Set(rows.map(r => norm(r.cliente)).filter(Boolean)).size;
  const pendientes = rows.filter(r => esPendienteReporte(r.estado)).length;
  const cancelados = rows.filter(r => esCanceladoReporte(r.estado)).length;
  const capturados = rows.filter(r => esCapturadoReporte(r.estado)).length;
  const ticketPromedio = totalSumables ? montoTotal / totalSumables : 0;

  const resumenTipo = obtenerResumenGrupoReporte(rows, r => tipoLabel(r.tipo))
    .sort((a, b) => b.montoSumable - a.montoSumable || b.registros - a.registros);

  const resumenEstado = obtenerResumenGrupoReporte(rows, r => normalizarEstadoReporte(r.estado))
    .sort((a, b) => b.registros - a.registros || b.montoSumable - a.montoSumable);

  const resumenAsesor = obtenerResumenGrupoReporte(rows, r => r.asesor || 'Sin asesor')
    .sort((a, b) => b.montoSumable - a.montoSumable || b.registros - a.registros);

  const resumenMes = obtenerResumenGrupoReporte(rows, r => {
    const d = parseDate(r.fecha);
    return d ? `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}` : 'Sin fecha';
  }).sort((a, b) => String(a.grupo).localeCompare(String(b.grupo)));

  const detalle = rows.map((r, i) => ({
    num: i + 1,
    tipo: tipoLabel(r.tipo),
    folio: r.folio || '—',
    cliente: r.cliente || '—',
    asesor: r.asesor || '—',
    monto: Number(r.monto || 0),
    montoSumable: esRegistroSumable(r) ? Number(r.monto || 0) : 0,
    fecha: fmtDate(r.fecha),
    estado: r.estado || '—',
    suma: esRegistroSumable(r) ? 'Sí suma' : 'No suma',
    motivo: motivoExclusionReporte(r),
    url: r.url || ''
  }));

  const solicitudRows = detalle.filter(r => r.tipo === 'Solicitud');
  const ahorroRows = detalle.filter(r => r.tipo === 'Ahorro');
  const inversionRows = detalle.filter(r => r.tipo === 'Inversión');
  const complementoRows = detalle.filter(r => r.tipo === 'Complemento');

  const topAsesores = resumenAsesor.slice(0, 8);
  const topEstados = resumenEstado.slice(0, 6);
  const topTipos = resumenTipo.slice(0, 5);
  const topMeses = resumenMes.slice(-12);

  const maxAsesor = Math.max(...topAsesores.map(x => x.registros), 1);
  const maxEstado = Math.max(...topEstados.map(x => x.registros), 1);
  const maxTipoMonto = Math.max(...topTipos.map(x => x.montoSumable), 1);
  const maxMesMonto = Math.max(...topMeses.map(x => x.montoSumable), 1);

  const xmlHeader =
    '<' + '?xml version="1.0" encoding="UTF-8"?' + '>\n' +
    '<' + '?mso-application progid="Excel.Sheet"?' + '>\n';

  const workbook = xmlHeader + `
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
 xmlns:o="urn:schemas-microsoft-com:office:office"
 xmlns:x="urn:schemas-microsoft-com:office:excel"
 xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">
<Styles>
  <Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="10"/></Style>
  <Style ss:ID="Logo"><Alignment ss:Horizontal="Center" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="22" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B1A2E" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Title"><Alignment ss:Horizontal="Left" ss:Vertical="Center"/><Font ss:FontName="Segoe UI" ss:Size="20" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B1A2E" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Subtitle"><Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#BFDBFE"/><Interior ss:Color="#0B1A2E" ss:Pattern="Solid"/></Style>
  <Style ss:ID="GoldLine"><Interior ss:Color="#FBBF24" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BlueLine"><Interior ss:Color="#268BFF" ss:Pattern="Solid"/></Style>

  <Style ss:ID="FilterTitle"><Font ss:FontName="Segoe UI" ss:Size="10" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1D4ED8" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E3A8A"/></Borders></Style>
  <Style ss:ID="FilterValue"><Font ss:FontName="Segoe UI" ss:Size="10" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#CBD5E1"/></Borders></Style>

  <Style ss:ID="CardLabel"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#DBEAFE"/><Interior ss:Color="#0B1A2E" ss:Pattern="Solid"/><Borders><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/></Borders></Style>
  <Style ss:ID="CardBlue"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="17" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#123A68" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#2563EB"/></Borders></Style>
  <Style ss:ID="CardGreen"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="17" ss:Bold="1" ss:Color="#DCFCE7"/><Interior ss:Color="#064E3B" ss:Pattern="Solid"/><NumberFormat ss:Format="$#,##0.00"/></Style>
  <Style ss:ID="CardYellow"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="17" ss:Bold="1" ss:Color="#FEF3C7"/><Interior ss:Color="#78350F" ss:Pattern="Solid"/></Style>
  <Style ss:ID="CardRed"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="17" ss:Bold="1" ss:Color="#FEE2E2"/><Interior ss:Color="#7F1D1D" ss:Pattern="Solid"/><NumberFormat ss:Format="$#,##0.00"/></Style>

  <Style ss:ID="Section"><Font ss:FontName="Segoe UI" ss:Size="12" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#0B1A2E" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Header"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#2563EB" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E40AF"/><Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E40AF"/><Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E40AF"/><Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#1E40AF"/></Borders></Style>
  <Style ss:ID="SubHeader"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#334155" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Cell"><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2F3"/></Borders></Style>
  <Style ss:ID="CellAlt"><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2F3"/></Borders></Style>
  <Style ss:ID="Money"><Alignment ss:Horizontal="Right"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><NumberFormat ss:Format="$#,##0.00"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2F3"/></Borders></Style>
  <Style ss:ID="MoneyAlt"><Alignment ss:Horizontal="Right"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><NumberFormat ss:Format="$#,##0.00"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2F3"/></Borders></Style>
  <Style ss:ID="Center"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#0F172A"/><Interior ss:Color="#FFFFFF" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Ok"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#166534"/><Interior ss:Color="#DCFCE7" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Warn"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#92400E"/><Interior ss:Color="#FEF3C7" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Bad"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#991B1B"/><Interior ss:Color="#FEE2E2" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Info"><Alignment ss:Horizontal="Center"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Bold="1" ss:Color="#1D4ED8"/><Interior ss:Color="#DBEAFE" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarBlue"><Interior ss:Color="#268BFF" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarCyan"><Interior ss:Color="#22D3EE" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarYellow"><Interior ss:Color="#FBBF24" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarGreen"><Interior ss:Color="#22C55E" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarRed"><Interior ss:Color="#EF4444" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarPurple"><Interior ss:Color="#8B5CF6" ss:Pattern="Solid"/></Style>
  <Style ss:ID="BarEmpty"><Interior ss:Color="#E2E8F0" ss:Pattern="Solid"/></Style>
  <Style ss:ID="Note"><Alignment ss:WrapText="1" ss:Vertical="Top"/><Font ss:FontName="Segoe UI" ss:Size="9" ss:Color="#334155"/><Interior ss:Color="#F8FAFC" ss:Pattern="Solid"/></Style>
</Styles>

${sheetDashboard()}
${sheetResumenes()}
${sheetTipo('Solicitudes', solicitudRows)}
${sheetTipo('Ahorro', ahorroRows)}
${sheetTipo('Inversion', inversionRows)}
${sheetTipo('Complemento', complementoRows)}
${sheetDetalle(detalle)}
</Workbook>`;

  const blob = new Blob(['\ufeff', workbook], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `Reporte_Ejecutivo_CIP_${fechaArchivo}.xls`;
  document.body.appendChild(a);
  a.click();
  a.remove();
  URL.revokeObjectURL(a.href);

  function sheetDashboard() {
    const rowsXml = [];
    rowsXml.push(row([cell('CIP', 'Logo', 'String', 1), cell('Centro de Instrumentos CIP', 'Title', 'String', 9)], 34));
    rowsXml.push(row([cell('', 'Logo', 'String', 1), cell('Reporte ejecutivo general · Solicitud, Ahorro, Inversión y Complemento', 'Subtitle', 'String', 9)], 22));
    rowsXml.push(row([cell('', 'BlueLine', 'String', 7), cell('', 'GoldLine', 'String', 3)], 6));
    rowsXml.push(blankRow(6));
    rowsXml.push(row([cell('Fecha de generación', 'FilterTitle', 'String', 2), cell('Tipo', 'FilterTitle', 'String', 2), cell('Estado', 'FilterTitle', 'String', 2), cell('Asesor', 'FilterTitle', 'String', 2), cell('Búsqueda', 'FilterTitle', 'String', 2)], 20));
    rowsXml.push(row([cell(fechaReporte, 'FilterValue', 'String', 2), cell(filtroTipo, 'FilterValue', 'String', 2), cell(filtroEstado || 'Todos', 'FilterValue', 'String', 2), cell(filtroAsesor || 'Todos', 'FilterValue', 'String', 2), cell(filtroBusqueda || 'Sin búsqueda', 'FilterValue', 'String', 2)], 22));
    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Total instrumentos', 'CardLabel', 'String', 1), cell('Monto colocado', 'CardLabel', 'String', 1), cell('Asesores activos', 'CardLabel', 'String', 1), cell('Clientes únicos', 'CardLabel', 'String', 1), cell('Pendientes', 'CardLabel', 'String', 1), cell('Cancelados', 'CardLabel', 'String', 1)], 20));
    rowsXml.push(row([cell(totalInstrumentos, 'CardBlue', 'Number', 1), cell(montoTotal, 'CardGreen', 'Number', 1), cell(asesoresActivos, 'CardBlue', 'Number', 1), cell(clientesUnicos, 'CardBlue', 'Number', 1), cell(pendientes, 'CardYellow', 'Number', 1), cell(cancelados, 'CardRed', 'Number', 1)], 36));
    rowsXml.push(row([cell('Registros que suman', 'CardLabel', 'String', 1), cell('Monto excluido', 'CardLabel', 'String', 1), cell('Capturados / activos', 'CardLabel', 'String', 1), cell('Ticket promedio', 'CardLabel', 'String', 1), cell('Regla de suma', 'CardLabel', 'String', 3)], 20));
    rowsXml.push(row([cell(totalSumables, 'CardBlue', 'Number', 1), cell(montoExcluido, 'CardRed', 'Number', 1), cell(capturados, 'CardBlue', 'Number', 1), cell(ticketPromedio, 'CardGreen', 'Number', 1), cell('No suma cancelado, rechazado, pendiente ni sin folio', 'CardBlue', 'String', 3)], 36));
    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Gráficas de control', 'Section', 'String', 10)], 22));
    rowsXml.push(row([cell('Registros por asesor', 'SubHeader', 'String', 5), cell('Distribución por estado', 'SubHeader', 'String', 5)], 20));
    rowsXml.push(row([cell('Asesor', 'Header'), cell('Registros', 'Header'), cell('Visual', 'Header', 'String', 3), cell('Estado', 'Header'), cell('Registros', 'Header'), cell('Visual', 'Header', 'String', 3)], 20));

    for (let i = 0; i < Math.max(topAsesores.length, topEstados.length, 1); i++) {
      const a = topAsesores[i] || {};
      const e = topEstados[i] || {};
      rowsXml.push(row([cell(a.grupo || '—', i % 2 ? 'CellAlt' : 'Cell'), cell(a.registros || 0, 'Center', 'Number'), ...barCells(a.registros || 0, maxAsesor, 3, 'BarBlue'), cell(e.grupo || '—', i % 2 ? 'CellAlt' : 'Cell'), cell(e.registros || 0, 'Center', 'Number'), ...barCells(e.registros || 0, maxEstado, 3, estadoBarStyle(e.grupo))], 19));
    }

    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Monto por instrumento', 'SubHeader', 'String', 5), cell('Monto mensual sumable', 'SubHeader', 'String', 5)], 20));
    rowsXml.push(row([cell('Instrumento', 'Header'), cell('Monto', 'Header'), cell('Visual', 'Header', 'String', 3), cell('Mes', 'Header'), cell('Monto', 'Header'), cell('Visual', 'Header', 'String', 3)], 20));

    for (let i = 0; i < Math.max(topTipos.length, topMeses.length, 1); i++) {
      const t = topTipos[i] || {};
      const m = topMeses[i] || {};
      rowsXml.push(row([cell(t.grupo || '—', i % 2 ? 'CellAlt' : 'Cell'), cell(t.montoSumable || 0, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), ...barCells(t.montoSumable || 0, maxTipoMonto, 3, tipoBarStyle(t.grupo)), cell(m.grupo || '—', i % 2 ? 'CellAlt' : 'Cell'), cell(m.montoSumable || 0, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), ...barCells(m.montoSumable || 0, maxMesMonto, 3, 'BarGreen')], 19));
    }

    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Resumen por instrumento', 'Section', 'String', 10)], 22));
    rowsXml.push(row(['Instrumento','Registros','Sumables','Monto total','Monto colocado','Pendientes','Cancelados','Clientes','Asesores','Alerta'].map(h => cell(h, 'Header')), 20));
    resumenTipo.forEach((r, i) => rowsXml.push(row([cell(r.grupo, i % 2 ? 'CellAlt' : 'Cell'), cell(r.registros, 'Center', 'Number'), cell(r.sumables, 'Center', 'Number'), cell(r.monto, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.montoSumable, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.pendientes, 'Center', 'Number'), cell(r.cancelados, 'Center', 'Number'), cell(r.clientes, 'Center', 'Number'), cell(r.asesores, 'Center', 'Number'), cell(alertaGrupoReporte(r), alertaStyleReporte(r))], 18)));
    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Lectura ejecutiva', 'Section', 'String', 10)], 22));
    rowsXml.push(row([cell(`El reporte consolida ${totalInstrumentos} instrumento(s). De ellos, ${totalSumables} son válidos para el monto colocado, acumulando ${money(montoTotal)}. Se excluyeron ${rowsExcluidos.length} registro(s) por estar cancelados, rechazados, pendientes o sin folio, con un monto referencial de ${money(montoExcluido)}.`, 'Note', 'String', 10)], 46));

    return worksheet('Dashboard Ejecutivo', [100,120,80,55,55,55,115,85,55,55,55], rowsXml, true, '', { fitHeight: 1 });
  }

  function sheetResumenes() {
    const rowsXml = [];
    rowsXml.push(row([cell('CIP Financial México', 'Title', 'String', 10)], 32));
    rowsXml.push(row([cell('Resumen Ejecutivo por Categorías', 'Subtitle', 'String', 10)], 22));
    rowsXml.push(row([cell('', 'BlueLine', 'String', 7), cell('', 'GoldLine', 'String', 3)], 6));
    rowsXml.push(blankRow(8));
    addSummaryBlock(rowsXml, 'Resumen por instrumento', resumenTipo);
    addSummaryBlock(rowsXml, 'Resumen por estado', resumenEstado);
    addSummaryBlock(rowsXml, 'Ranking por asesor', resumenAsesor);
    addSummaryBlock(rowsXml, 'Resumen mensual', resumenMes);
    return worksheet('Resumenes', [55,230,90,105,120,120,90,90,90,150,190], rowsXml, true);
  }

  function addSummaryBlock(rowsXml, title, data) {
    rowsXml.push(row([cell(title, 'Section', 'String', 10)], 22));
    rowsXml.push(row(['#','Grupo','Registros','Sumables','Monto total','Monto colocado','Pendientes','Cancelados','Clientes','Asesores','Alerta'].map(h => cell(h, 'Header')), 20));
    data.forEach((r, i) => rowsXml.push(row([cell(i + 1, 'Center', 'Number'), cell(r.grupo || '—', i % 2 ? 'CellAlt' : 'Cell'), cell(r.registros || 0, 'Center', 'Number'), cell(r.sumables || 0, 'Center', 'Number'), cell(r.monto || 0, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.montoSumable || 0, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.pendientes || 0, 'Center', 'Number'), cell(r.cancelados || 0, 'Center', 'Number'), cell(r.clientes || 0, 'Center', 'Number'), cell(r.asesores || 0, 'Center', 'Number'), cell(alertaGrupoReporte(r), alertaStyleReporte(r))], 18)));
    rowsXml.push(blankRow(8));
  }

  function sheetTipo(nombre, data) {
    const rowsXml = [];
    const montoTipo = data.reduce((a, r) => a + Number(r.montoSumable || 0), 0);
    const pendientesTipo = data.filter(r => esPendienteReporte(r.estado)).length;
    const canceladosTipo = data.filter(r => esCanceladoReporte(r.estado)).length;
    rowsXml.push(row([cell(`CIP · ${nombre}`, 'Title', 'String', 10)], 32));
    rowsXml.push(row([cell(`Reporte detallado de ${nombre} · Generado: ${fechaReporte}`, 'Subtitle', 'String', 10)], 22));
    rowsXml.push(row([cell('', 'BlueLine', 'String', 7), cell('', 'GoldLine', 'String', 3)], 6));
    rowsXml.push(blankRow(8));
    rowsXml.push(row([cell('Registros', 'CardLabel', 'String', 1), cell('Monto colocado', 'CardLabel', 'String', 1), cell('Pendientes', 'CardLabel', 'String', 1), cell('Cancelados', 'CardLabel', 'String', 1), cell('Filtro asesor', 'CardLabel', 'String', 2), cell('Filtro estado', 'CardLabel', 'String', 2)], 20));
    rowsXml.push(row([cell(data.length, 'CardBlue', 'Number', 1), cell(montoTipo, 'CardGreen', 'Number', 1), cell(pendientesTipo, 'CardYellow', 'Number', 1), cell(canceladosTipo, 'CardRed', 'Number', 1), cell(filtroAsesor || 'Todos', 'CardBlue', 'String', 2), cell(filtroEstado || 'Todos', 'CardBlue', 'String', 2)], 34));
    rowsXml.push(blankRow(8));
    rowsXml.push(row(['#','Tipo','Folio','Cliente','Asesor','Monto original','Monto sumable','Fecha','Estado','¿Suma?','Motivo / Observación'].map(h => cell(h, 'Header')), 20));
    data.forEach((r, i) => rowsXml.push(row([cell(r.num, 'Center', 'Number'), cell(r.tipo, i % 2 ? 'CellAlt' : 'Cell'), cell(r.folio, i % 2 ? 'CellAlt' : 'Cell'), cell(r.cliente, i % 2 ? 'CellAlt' : 'Cell'), cell(r.asesor, i % 2 ? 'CellAlt' : 'Cell'), cell(r.monto, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.montoSumable, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.fecha, 'Center'), cell(r.estado, estadoExcelStyleReporte(r.estado)), cell(r.suma, r.suma === 'Sí suma' ? 'Ok' : 'Warn'), cell(r.motivo, i % 2 ? 'CellAlt' : 'Cell')], 18)));
    return worksheet(nombre, [45,90,135,235,210,115,115,95,115,95,280], rowsXml, true, `R8C1:R${data.length + 8}C11`);
  }

  function sheetDetalle(data) {
    const rowsXml = [];
    rowsXml.push(row([cell('CIP Financial México', 'Title', 'String', 11)], 32));
    rowsXml.push(row([cell(`Detalle completo de instrumentos · ${data.length} registros · Generado: ${fechaReporte}`, 'Subtitle', 'String', 11)], 22));
    rowsXml.push(row([cell('', 'BlueLine', 'String', 8), cell('', 'GoldLine', 'String', 3)], 6));
    rowsXml.push(blankRow(8));
    rowsXml.push(row(['#','Tipo','Folio','Cliente','Asesor','Monto original','Monto sumable','Fecha','Estado','¿Suma?','Motivo / Observación','URL'].map(h => cell(h, 'Header')), 20));
    data.forEach((r, i) => rowsXml.push(row([cell(r.num, 'Center', 'Number'), cell(r.tipo, i % 2 ? 'CellAlt' : 'Cell'), cell(r.folio, i % 2 ? 'CellAlt' : 'Cell'), cell(r.cliente, i % 2 ? 'CellAlt' : 'Cell'), cell(r.asesor, i % 2 ? 'CellAlt' : 'Cell'), cell(r.monto, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.montoSumable, i % 2 ? 'MoneyAlt' : 'Money', 'Number'), cell(r.fecha, 'Center'), cell(r.estado, estadoExcelStyleReporte(r.estado)), cell(r.suma, r.suma === 'Sí suma' ? 'Ok' : 'Warn'), cell(r.motivo, i % 2 ? 'CellAlt' : 'Cell'), cell(r.url, i % 2 ? 'CellAlt' : 'Cell')], 18)));
    return worksheet('Detalle completo', [45,95,135,240,220,115,115,95,115,95,285,320], rowsXml, true, `R5C1:R${data.length + 5}C12`);
  }

  function worksheet(name, widths, rowsXml, freeze = false, autoFilterRange = '', print = {}) {
    const columns = widths.map(w => `<Column ss:AutoFitWidth="0" ss:Width="${w}"/>`).join('');
    const autoFilter = autoFilterRange ? `<AutoFilter x:Range="${autoFilterRange}" xmlns="urn:schemas-microsoft-com:office:excel"></AutoFilter>` : '';
    const fitHeight = Number.isFinite(print.fitHeight) ? print.fitHeight : 0;
    return `
<Worksheet ss:Name="${xmlReporte(name).slice(0, 31)}">
  <Table>${columns}${rowsXml.join('')}</Table>
  <WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel">
    <PageSetup><Layout x:Orientation="Landscape"/><PageMargins x:Bottom="0.35" x:Left="0.25" x:Right="0.25" x:Top="0.35"/></PageSetup>
    <FitToPage/><Print><FitWidth>1</FitWidth><FitHeight>${fitHeight}</FitHeight><ValidPrinterInfo/></Print>
    ${freeze ? '<FreezePanes/><FrozenNoSplit/><SplitHorizontal>5</SplitHorizontal><TopRowBottomPane>5</TopRowBottomPane><ActivePane>2</ActivePane>' : ''}
    <ProtectObjects>False</ProtectObjects><ProtectScenarios>False</ProtectScenarios>
  </WorksheetOptions>
  ${autoFilter}
</Worksheet>`;
  }

  function row(cells, height = 18) { return `<Row ss:Height="${height}">${cells.join('')}</Row>`; }
  function blankRow(height = 8) { return `<Row ss:Height="${height}"></Row>`; }

  function cell(value, style = 'Cell', type = 'String', mergeAcross = 0) {
    const merge = mergeAcross ? ` ss:MergeAcross="${mergeAcross}"` : '';
    if (type === 'Number') return `<Cell ss:StyleID="${style}"${merge}><Data ss:Type="Number">${Number(value || 0)}</Data></Cell>`;
    return `<Cell ss:StyleID="${style}"${merge}><Data ss:Type="String">${xmlReporte(value)}</Data></Cell>`;
  }

  function barCells(value, max, count, style) {
    const safeMax = Math.max(Number(max || 0), 1);
    const filled = Math.max(0, Math.min(count, Math.round((Number(value || 0) / safeMax) * count)));
    return Array.from({ length: count }, (_, i) => cell('', i < filled ? style : 'BarEmpty'));
  }

  function tipoBarStyle(tipo) {
    const t = norm(tipo);
    if (t.includes('solicitud')) return 'BarYellow';
    if (t.includes('ahorro')) return 'BarCyan';
    if (t.includes('inversion')) return 'BarBlue';
    if (t.includes('complemento')) return 'BarPurple';
    return 'BarBlue';
  }

  function estadoBarStyle(estado) {
    const e = norm(estado);
    if (e.includes('cancel') || e.includes('rechaz')) return 'BarRed';
    if (e.includes('pend')) return 'BarYellow';
    if (e.includes('activo') || e.includes('capturado') || e.includes('listo')) return 'BarGreen';
    return 'BarBlue';
  }
}

/* =========================================================
   HELPERS DEL REPORTE
========================================================= */
function obtenerResumenGrupoReporte(rows, keyFn) {
  const map = new Map();
  rows.forEach(r => {
    const grupo = keyFn(r) || '—';
    const actual = map.get(grupo) || { grupo, registros: 0, sumables: 0, monto: 0, montoSumable: 0, clientes: new Set(), asesores: new Set(), pendientes: 0, cancelados: 0 };
    const monto = Number(r.monto || 0);
    actual.registros += 1;
    actual.monto += monto;
    if (esRegistroSumable(r)) { actual.sumables += 1; actual.montoSumable += monto; }
    if (r.cliente) actual.clientes.add(norm(r.cliente));
    if (r.asesor && r.asesor !== '—') actual.asesores.add(r.asesor);
    if (esPendienteReporte(r.estado)) actual.pendientes += 1;
    if (esCanceladoReporte(r.estado)) actual.cancelados += 1;
    map.set(grupo, actual);
  });
  return Array.from(map.values()).map(x => ({ grupo: x.grupo, registros: x.registros, sumables: x.sumables, monto: x.monto, montoSumable: x.montoSumable, clientes: x.clientes.size, asesores: x.asesores.size, pendientes: x.pendientes, cancelados: x.cancelados }));
}

function xmlReporte(value) {
  return String(value ?? '').replace(/[&<>'"]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&apos;', '"': '&quot;' }[m]));
}

function esCanceladoReporte(estado) {
  const e = norm(estado);
  return e.includes('cancel') || e.includes('rechaz');
}

function esPendienteReporte(estado) {
  const e = norm(estado);
  return e.includes('pendiente') || e.includes('por vencer') || e.includes('revision') || e.includes('proceso');
}

function esCapturadoReporte(estado) {
  const e = norm(estado);
  return e.includes('capturado') || e.includes('activo') || e.includes('listo') || e.includes('firmado') || e.includes('validado') || e.includes('aprobado') || e.includes('completo');
}

function normalizarEstadoReporte(estado) {
  if (esCanceladoReporte(estado)) return 'Cancelado';
  if (esPendienteReporte(estado)) return 'Pendiente';
  if (esCapturadoReporte(estado)) return 'Capturado / Activo';
  return estado || 'Sin estado';
}

function motivoExclusionReporte(r) {
  const estado = norm(r.estado);
  const folio = norm(r.folio);
  if (estado.includes('cancel') || estado.includes('rechaz')) return 'No suma por estado cancelado/rechazado.';
  if (estado.includes('pendiente') || estado.includes('por vencer') || estado.includes('revision') || estado.includes('proceso')) return 'No suma por estar pendiente o en proceso.';
  if (folio.includes('sin folio')) return 'No suma porque no tiene folio.';
  return 'Registro válido para monto colocado.';
}

function estadoExcelStyleReporte(estado) {
  if (esCanceladoReporte(estado)) return 'Bad';
  if (esPendienteReporte(estado)) return 'Warn';
  if (esCapturadoReporte(estado)) return 'Ok';
  return 'Info';
}

function alertaGrupoReporte(r) {
  if (!r) return '—';
  if (r.cancelados > 0) return `${r.cancelados} cancelado(s)`;
  if (r.pendientes > 0) return `${r.pendientes} pendiente(s)`;
  return 'Sin alertas';
}

function alertaStyleReporte(r) {
  if (!r) return 'Info';
  if (r.cancelados > 0) return 'Bad';
  if (r.pendientes > 0) return 'Warn';
  return 'Ok';
}



function verDetalle(tipo, folio) {
  if (typeof Swal !== 'undefined') {
    Swal.fire({
      icon: 'info',
      title: 'Detalle',
      text: `${tipoLabel(tipo)} · ${folio}`
    });
  } else {
    alert(`${tipoLabel(tipo)} · ${folio}`);
  }
}


/* =========================================================
   SIDEBAR / TEMA / SESIÓN
========================================================= */
function toggleSidebar(open) {
  document.getElementById('sidebar')?.classList.toggle('show', !!open);
  document.getElementById('backdrop')?.classList.toggle('show', !!open);
}

function applyTheme(theme) {
  if (theme !== 'light' && theme !== 'dark') {
    theme = 'dark';
  }

  document.documentElement.setAttribute('data-theme', theme);

  try {
    localStorage.setItem(THEME_KEY, theme);
  } catch (e) {}

  const btn = document.getElementById('btnTema2');

  if (btn) {
    btn.querySelector('.ico').textContent = theme === 'dark' ? '☀️' : '🌙';
    btn.querySelector('.txt').textContent = theme === 'dark' ? 'Modo claro' : 'Modo oscuro';
  }

  renderCharts();
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

async function cerrarSesion() {
  try {
    await fetch(URL_LOGOUT, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      body: ''
    });
  } catch (e) {}

  location.href = `${BASE_URL}/login.html`;
}


/* =========================================================
   INIT
========================================================= */
document.addEventListener('DOMContentLoaded', async () => {
  try {
    applyTheme(localStorage.getItem(THEME_KEY) || 'dark');
  } catch (e) {
    applyTheme('dark');
  }

  const today = document.getElementById('todayLabel');
  if (today) {
    today.textContent = new Date().toLocaleDateString('es-MX');
  }

  const tipoSelect = document.getElementById('filtro-tipo');
  if (tipoSelect) {
    tipoSelect.value = TIPO_INICIAL;
  }

  document.querySelectorAll('#tipoTabs .tab').forEach(btn => {
    btn.addEventListener('click', () => {
      const filtroTipo = document.getElementById('filtro-tipo');
      if (filtroTipo) filtroTipo.value = btn.dataset.tipo;

      aplicarFiltros();
    });
  });

  ['filtro-busqueda', 'filtro-asesor', 'filtro-tipo', 'filtro-estado'].forEach(id => {
    const el = document.getElementById(id);
    if (!el) return;

    el.addEventListener(id === 'filtro-busqueda' ? 'input' : 'change', () => {
      clearTimeout(window.__ft);

      window.__ft = setTimeout(aplicarFiltros, 160);
    });
  });

  await cargarTodo();
});
</script>
</body>
</html>
