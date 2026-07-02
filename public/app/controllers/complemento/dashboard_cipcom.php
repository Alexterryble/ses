<?php
// /sempiternal/public/app/controllers/complemento/dashboard_cipcom.php
declare(strict_types=1);

/* =========================================================
   BASE URL DINÁMICA
   - Local XAMPP: /sempiternal/public
   - Railway / producción: raíz del dominio
========================================================= */
$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocal = str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
$BASE_URL = $isLocal ? '/sempiternal/public' : '';
?>
<!DOCTYPE html>
<html lang="es" data-theme="dark">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Dashboard CIP - Complemento</title>

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <style>
/* =========================================================
   DASHBOARD COMPLEMENTO CIP
   Diseño oscuro estilo ejecutivo con soporte claro/oscuro
========================================================= */
:root{
  --page-bg:#071426;
  --shell-bg:#071426;
  --sidebar-bg:#071b31;
  --surface:#0d2138;
  --surface-2:#0a1a2d;
  --surface-3:#102a47;
  --card:#0d223b;
  --card-soft:#102945;
  --line:rgba(78,142,216,.28);
  --line-2:rgba(125,180,255,.18);
  --text:#f6fbff;
  --muted:#8fa8c6;
  --muted-2:#b8c9dd;
  --blue:#2d8cff;
  --blue-2:#0d6efd;
  --cyan:#2dd4ff;
  --green:#22c55e;
  --green-soft:rgba(34,197,94,.13);
  --red:#ef4444;
  --red-soft:rgba(239,68,68,.13);
  --purple:#8b5cf6;
  --orange:#f59e0b;
  --shadow:0 22px 60px rgba(0,0,0,.34);
  --shadow-soft:0 12px 28px rgba(0,0,0,.24);
  --radius:18px;
}

:root[data-theme="light"]{
  --page-bg:#eef3f9;
  --shell-bg:#f8fbff;
  --sidebar-bg:#ffffff;
  --surface:#ffffff;
  --surface-2:#f7fbff;
  --surface-3:#edf5ff;
  --card:#ffffff;
  --card-soft:#f5f9ff;
  --line:rgba(37,99,235,.18);
  --line-2:rgba(15,23,42,.10);
  --text:#101828;
  --muted:#64748b;
  --muted-2:#334155;
  --blue:#2563eb;
  --blue-2:#1d4ed8;
  --cyan:#0891b2;
  --green:#15803d;
  --green-soft:rgba(21,128,61,.10);
  --red:#b91c1c;
  --red-soft:rgba(185,28,28,.10);
  --purple:#6d28d9;
  --orange:#b45309;
  --shadow:0 18px 45px rgba(15,23,42,.10);
  --shadow-soft:0 10px 24px rgba(15,23,42,.08);
}

*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  min-height:100vh;
  padding:14px;
  font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  color:var(--text);
  background:
    radial-gradient(900px 500px at 18% -5%, rgba(45,140,255,.22), transparent 60%),
    radial-gradient(700px 400px at 86% 10%, rgba(45,212,255,.10), transparent 58%),
    var(--page-bg);
}

a{color:inherit;text-decoration:none}
button,input{font:inherit}

/* =========================================================
   LAYOUT
========================================================= */
.app-shell{
  width:min(1500px,100%);
  min-height:calc(100vh - 28px);
  margin:0 auto;
  display:grid;
  grid-template-columns:240px minmax(0,1fr);
  gap:14px;
  padding:8px;
  border:1px solid var(--line);
  border-radius:0;
  background:linear-gradient(180deg, rgba(255,255,255,.035), rgba(255,255,255,.015));
  box-shadow:var(--shadow);
}

.sidebar{
  position:sticky;
  top:22px;
  min-height:calc(100vh - 44px);
  border:1px solid var(--line);
  border-radius:12px;
  background:
    radial-gradient(500px 260px at 40% 0%, rgba(45,140,255,.22), transparent 60%),
    linear-gradient(180deg, rgba(13,33,56,.95), rgba(6,18,33,.96));
  overflow:hidden;
  display:flex;
  flex-direction:column;
  padding:18px 16px;
}
:root[data-theme="light"] .sidebar{
  background:linear-gradient(180deg,#ffffff,#f7fbff);
}

.sidebar::after{
  content:"";
  position:absolute;
  inset:auto -50px 60px -40px;
  height:220px;
  background:
    repeating-linear-gradient(160deg, transparent 0 12px, rgba(45,140,255,.18) 13px 14px),
    radial-gradient(300px 180px at 60% 70%, rgba(45,140,255,.18), transparent 65%);
  transform:rotate(-8deg);
  pointer-events:none;
}

.sidebar-head{position:relative;z-index:1;margin-bottom:34px}
.brand-row{display:flex;align-items:center;gap:12px}
.logo-mark{
  width:46px;height:46px;border-radius:13px;
  background:linear-gradient(135deg,#34d4ff,#125cff 55%,#0a2e73);
  box-shadow:0 16px 35px rgba(45,140,255,.28);
  position:relative;overflow:hidden;
}
.logo-mark::after{
  content:"";position:absolute;width:70px;height:18px;left:-10px;top:17px;
  background:rgba(255,255,255,.32);transform:rotate(-45deg);
}
.brand-text strong{font-size:2rem;letter-spacing:.02em;line-height:.9;display:block}
.brand-text span{font-size:.68rem;letter-spacing:.08em;color:var(--muted-2);font-weight:800;text-transform:uppercase;line-height:1.25;display:block;margin-top:7px}

.sidebar-links{position:relative;z-index:1;display:flex;flex-direction:column;gap:13px}
.btn{
  min-height:48px;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid var(--line);
  background:rgba(255,255,255,.035);
  color:var(--text);
  display:inline-flex;align-items:center;gap:12px;
  cursor:pointer;font-weight:800;font-size:.92rem;
  transition:transform .13s ease, border-color .13s ease, background .13s ease, box-shadow .13s ease;
}
.btn:hover{transform:translateY(-1px);border-color:rgba(45,140,255,.55);background:rgba(45,140,255,.10)}
.btn:active{transform:translateY(0)}
.btn .ico{font-size:1.08rem;opacity:.95}
.sidebar .btn{width:100%;justify-content:flex-start}
.btn-primary{border-color:rgba(45,140,255,.65);background:linear-gradient(135deg,#48a6ff,#125cff);box-shadow:0 14px 30px rgba(18,92,255,.24);color:#fff}
.btn-outline{background:rgba(255,255,255,.04)}
.btn-secondary{background:rgba(45,140,255,.09);color:#d8eaff}
:root[data-theme="light"] .btn{background:#fff;color:var(--text)}
:root[data-theme="light"] .btn-primary{color:#fff;background:linear-gradient(135deg,#3b82f6,#1d4ed8)}
:root[data-theme="light"] .btn-secondary{color:#1d4ed8;background:#eef5ff}

.sidebar-user{
  position:relative;z-index:1;margin-top:auto;
  padding:12px;border:1px solid var(--line);border-radius:14px;
  background:rgba(255,255,255,.04);
  display:flex;align-items:center;gap:10px;
}
.user-avatar{
  width:38px;height:38px;border-radius:50%;display:grid;place-items:center;
  background:linear-gradient(135deg,#4ea3ff,#155cff);font-weight:900;color:#fff;
}
.user-meta strong{display:block;font-size:.83rem}.user-meta span{display:block;color:var(--muted);font-size:.73rem;margin-top:2px}

.main{min-width:0;padding:12px 10px 0}
.topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:14px}
.title-block h1{font-size:1.65rem;letter-spacing:-.03em;margin:0 0 6px}
.title-block p{color:var(--muted);font-size:.92rem}
.top-actions{display:flex;align-items:center;gap:10px}
.icon-pill{
  width:42px;height:42px;border-radius:50%;display:grid;place-items:center;
  border:1px solid var(--line);background:var(--surface);color:var(--text);
}

/* =========================================================
   KPI CARDS
========================================================= */
.dashboard-grid{
  display:grid;
  grid-template-columns:repeat(5,minmax(0,1fr));
  gap:12px;margin-bottom:14px;
}
.metric-card{
  min-height:116px;
  border:1px solid var(--line);
  border-radius:12px;
  background:
    radial-gradient(260px 120px at 95% 90%, rgba(45,140,255,.20), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,.045), rgba(255,255,255,.018)),
    var(--card);
  box-shadow:var(--shadow-soft);
  padding:16px;
  display:grid;
  grid-template-columns:auto 1fr;
  gap:12px;
  align-items:center;
  overflow:hidden;
  position:relative;
}
.metric-icon{
  width:46px;height:46px;border-radius:16px;display:grid;place-items:center;
  font-size:1.28rem;color:#dff3ff;background:rgba(45,140,255,.18);
  box-shadow:inset 0 0 0 1px rgba(45,140,255,.23);
}
.metric-icon.green{background:rgba(34,197,94,.18)}
.metric-icon.purple{background:rgba(139,92,246,.18)}
.metric-icon.orange{background:rgba(245,158,11,.18)}
.metric-icon.cyan{background:rgba(45,212,255,.16)}
.metric-label{color:var(--muted-2);font-weight:800;font-size:.79rem;margin-bottom:6px}
.metric-value{font-size:1.45rem;font-weight:950;letter-spacing:-.03em}
.metric-sub{margin-top:7px;color:var(--muted);font-size:.72rem;font-weight:700;display:flex;align-items:center;gap:5px}
.metric-sub.up{color:#45e481}.metric-sub.blue{color:#61b7ff}.metric-sub.warn{color:#f7bd46}
.spark{position:absolute;right:12px;bottom:15px;width:58px;height:30px;opacity:.9}
.spark path{fill:none;stroke:currentColor;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}
.metric-card:nth-child(1){color:#44a7ff}.metric-card:nth-child(2){color:#37e59c}.metric-card:nth-child(3){color:#b084ff}.metric-card:nth-child(4){color:#f5b437}.metric-card:nth-child(5){color:#42d4ff}

/* =========================================================
   CHART + RECENTS
========================================================= */
.dashboard-grid--bottom{
  display:grid;
  grid-template-columns:minmax(0,1.8fr) minmax(330px,.8fr);
  gap:14px;margin-bottom:14px;
}
.panel{
  border:1px solid var(--line);
  border-radius:12px;
  background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,.018)), var(--surface);
  box-shadow:var(--shadow-soft);
}
.chart-card{padding:16px}
.chart-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.chart-title{font-weight:900;font-size:1rem}.chart-sub{color:var(--muted);font-size:.78rem;margin-top:3px}
.chart-filter{border:1px solid var(--line);border-radius:10px;padding:8px 10px;background:var(--surface-2);color:var(--muted-2);font-weight:800;font-size:.78rem}
.chart-wrap{position:relative;height:280px}
.chart-wrap canvas{width:100%!important;height:100%!important}
.empty-chart-msg{text-align:center;color:var(--muted);font-size:.86rem;margin-top:10px}
.years-box{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
.year-pill{border:1px solid var(--line);background:var(--surface-2);color:var(--muted-2);border-radius:999px;padding:7px 10px;font-size:.76rem;font-weight:800}

.recent-card{padding:16px}
.recent-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.recent-title{font-weight:900}.recent-link{color:#5eb3ff;font-size:.8rem;font-weight:900}
.recent-sub{font-size:.76rem;color:var(--muted);margin-top:2px}
.recent-list{list-style:none;display:flex;flex-direction:column;gap:0}
.recent-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--line-2)}
.recent-item:last-child{border-bottom:none}
.recent-dot{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:rgba(45,140,255,.18);color:#72c5ff;font-weight:900;flex:0 0 36px}
.recent-main{min-width:0;flex:1}.recent-folio{font-size:.76rem;color:#7cc8ff;font-weight:900}.recent-name{font-size:.86rem;font-weight:850;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.recent-meta{font-size:.72rem;color:var(--muted);margin-top:2px}.recent-status{width:8px;height:8px;border-radius:999px;background:var(--green)}
.recent-empty{color:var(--muted);text-align:center;padding:18px 0}

/* =========================================================
   SEARCH + TABLE
========================================================= */
.search-row{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:0 0 10px}
.search-bar{position:relative;flex:1;max-width:570px}
.search-bar::before{content:"⌕";position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:1.1rem}
.search-bar input{width:100%;height:44px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);padding:0 16px 0 40px;outline:none}
.search-bar input:focus{border-color:rgba(45,140,255,.68);box-shadow:0 0 0 3px rgba(45,140,255,.10)}
.table-actions{display:flex;align-items:center;gap:9px}
.table-panel{overflow:hidden}
table{width:100%;border-collapse:collapse;font-size:.82rem}
thead{background:rgba(255,255,255,.045)}
th{padding:12px 14px;text-align:left;text-transform:uppercase;font-size:.68rem;letter-spacing:.08em;color:#e5f0ff;border-bottom:1px solid var(--line)}
td{padding:12px 14px;border-bottom:1px solid var(--line-2);color:var(--muted-2);vertical-align:middle}
tbody tr:nth-child(odd){background:rgba(255,255,255,.015)}
tbody tr:hover{background:rgba(45,140,255,.08)}
:root[data-theme="light"] th{color:#334155;background:#edf5ff}
:root[data-theme="light"] td{color:#334155}
.no-result{text-align:center;color:var(--muted);padding:30px;font-style:italic}.no-result span{display:block;margin-top:5px;font-size:.78rem}
.acciones{display:flex;align-items:center;justify-content:flex-end;gap:8px}.btn-sm{height:34px;min-width:42px;border:none;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;padding:0 12px;font-weight:900;cursor:pointer;color:#fff;background:#1e74ef}.btn-sm:hover{filter:brightness(1.08)}
.badge-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;font-size:.72rem;font-weight:950;border:1px solid transparent;white-space:nowrap}
.badge-status::before{content:"";width:7px;height:7px;border-radius:50%}
.badge-status.activo{color:#5cf08e;background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.25)}.badge-status.activo::before{background:#22c55e}
.badge-status.cancelado{color:#ff8d9a;background:rgba(239,68,68,.12);border-color:rgba(239,68,68,.25)}.badge-status.cancelado::before{background:#ef4444}
.table-foot{display:flex;align-items:center;justify-content:space-between;padding:12px 14px;color:var(--muted);font-size:.78rem;border-top:1px solid var(--line-2)}

/* =========================================================
   MODAL
========================================================= */
.modal{position:fixed;inset:0;background:rgba(1,8,18,.78);display:none;align-items:center;justify-content:center;padding:20px;z-index:60;backdrop-filter:blur(6px)}
.modal.show{display:flex}.dialog{width:min(1080px,100%);max-height:88vh;background:var(--surface);border:1px solid var(--line);border-radius:18px;overflow:hidden;box-shadow:0 30px 90px rgba(0,0,0,.48)}
.dialog header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:linear-gradient(90deg,#0e2a4a,#123a68);border-bottom:1px solid var(--line)}
.dialog h3{font-size:1rem}.close{background:transparent;border:none;color:#fff;font-size:1.2rem;cursor:pointer}.content{padding:18px;overflow:auto;max-height:65vh}.foot{display:flex;justify-content:flex-end;gap:8px;padding:12px 18px;border-top:1px solid var(--line)}
.docs-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.doc-card{border:1px solid var(--line);border-radius:16px;background:var(--surface-2);padding:14px;display:flex;gap:12px}.doc-icon{width:44px;height:44px;border-radius:16px;background:linear-gradient(135deg,#2d8cff,#1c51c7);display:grid;place-items:center;font-size:1.2rem;flex:0 0 44px}.doc-title{font-weight:950}.doc-meta{margin-top:6px;color:var(--muted);font-size:.8rem;line-height:1.5}.badge{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:5px 10px;background:rgba(34,197,94,.12);color:#86efac;font-size:.75rem;font-weight:900;margin-top:10px}.badge .dot{width:8px;height:8px;border-radius:50%;background:#22c55e}

/* =========================================================
   RESPONSIVE
========================================================= */
@media (max-width:1180px){.dashboard-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.dashboard-grid--bottom{grid-template-columns:1fr}.app-shell{grid-template-columns:220px minmax(0,1fr)}}
@media (max-width:900px){body{padding:10px}.app-shell{grid-template-columns:1fr}.sidebar{position:relative;top:0;min-height:auto}.sidebar-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr))}.sidebar-user{display:none}.dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.topbar{flex-direction:column}.search-row{flex-direction:column;align-items:stretch}.table-actions{justify-content:flex-end}.docs-grid{grid-template-columns:1fr}}
@media (max-width:620px){.dashboard-grid{grid-template-columns:1fr}.sidebar-links{grid-template-columns:1fr}table{min-width:900px}.table-panel{overflow:auto}.app-shell{padding:6px}.brand-text strong{font-size:1.6rem}}
  </style>
</head>

<body>
  <div class="app-shell">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-head">
        <div class="brand-row">
          <div class="logo-mark"></div>
          <div class="brand-text">
            <strong>CIP</strong>
            <span>Complemento<br>Información de pagos</span>
          </div>
        </div>
      </div>

      <nav class="sidebar-links">
        <button class="btn btn-outline" id="btnBack" type="button"><span class="ico">←</span><span>Regresar</span></button>
        <button class="btn btn-outline" id="btnTheme" type="button"><span class="ico">◐</span><span>Tema</span></button>
        <button class="btn btn-secondary" id="btnRefresh" type="button"><span class="ico">⟳</span><span>Actualizar</span></button>
        <button class="btn btn-primary" id="btnAgregar" type="button"><span class="ico">＋</span><span>Agregar / Simular</span></button>
      </nav>

      <div class="sidebar-user">
        <div class="user-avatar">A</div>
        <div class="user-meta">
          <strong>Administrador</strong>
          <span>admin@cip.com.mx</span>
        </div>
      </div>
    </aside>

    <!-- MAIN -->
    <main class="main">
      <div class="topbar">
        <div class="title-block">
          <h1>Dashboard Complemento CIP</h1>
          <p>Resumen de clientes, ingresos, recuperación y registros de complemento.</p>
        </div>
        <div class="top-actions">
          <div class="icon-pill">🔔</div>
          <div class="icon-pill">A</div>
        </div>
      </div>

      <!-- KPIs -->
      <section class="dashboard-grid" aria-label="Indicadores principales">
        <article class="metric-card">
          <div class="metric-icon">👥</div>
          <div>
            <div class="metric-label">Usuarios</div>
            <div class="metric-value" id="kpiUsers">0</div>
            <div class="metric-sub up">↑ Clientes únicos</div>
          </div>
          <svg class="spark" viewBox="0 0 80 40"><path d="M4 30 L18 25 L28 31 L42 19 L52 23 L64 12 L76 7"/></svg>
        </article>

        <article class="metric-card">
          <div class="metric-icon green">▣</div>
          <div>
            <div class="metric-label">Registros</div>
            <div class="metric-value" id="kpiTotal">0</div>
            <div class="metric-sub up">↑ Total guardados</div>
          </div>
          <svg class="spark" viewBox="0 0 80 40"><path d="M4 33 L14 31 L24 21 L38 25 L49 15 L62 18 L76 8"/></svg>
        </article>

        <article class="metric-card">
          <div class="metric-icon purple">$</div>
          <div>
            <div class="metric-label">Ingresos (<?= date('Y') ?>)</div>
            <div class="metric-value" id="kpiIngresoYear">$0.00</div>
            <div class="metric-sub up">↑ Año actual</div>
          </div>
          <svg class="spark" viewBox="0 0 80 40"><path d="M4 31 L16 25 L28 29 L40 17 L52 23 L64 12 L76 19"/></svg>
        </article>

        <article class="metric-card">
          <div class="metric-icon orange">$</div>
          <div>
            <div class="metric-label">Ingresos (<?= date('Y') - 1 ?>)</div>
            <div class="metric-value" id="kpiIngresoPrev">$0.00</div>
            <div class="metric-sub warn">Comparativo previo</div>
          </div>
          <svg class="spark" viewBox="0 0 80 40"><path d="M4 31 L14 26 L24 12 L35 32 L48 16 L60 25 L76 8"/></svg>
        </article>

        <article class="metric-card">
          <div class="metric-icon cyan">◔</div>
          <div>
            <div class="metric-label">Recuperación (<?= date('Y') ?>)</div>
            <div class="metric-value" id="kpiRecYear">$0.00</div>
            <div class="metric-sub blue">Suma anual</div>
          </div>
          <svg class="spark" viewBox="0 0 80 40"><path d="M4 33 L16 30 L28 22 L40 28 L51 20 L62 12 L76 7"/></svg>
        </article>
      </section>

      <!-- Gráfica + recientes -->
      <section class="dashboard-grid--bottom">
        <article class="panel chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Clientes agregados por mes</div>
              <div class="chart-sub">Últimos 12 meses</div>
            </div>
            <button class="chart-filter" type="button">Últimos 12 meses⌄</button>
          </div>
          <div class="chart-wrap">
            <canvas id="chartMain"></canvas>
          </div>
          <div class="empty-chart-msg" id="chartEmpty" style="display:none;">Sin datos para graficar</div>
          <div class="years-box" id="yearsBox"></div>
        </article>

        <aside class="panel recent-card">
          <div class="recent-head">
            <div>
              <div class="recent-title">Últimos registros</div>
              <div class="recent-sub">Los más recientes guardados</div>
            </div>
            <a href="#tabla" class="recent-link">Ver todos ›</a>
          </div>
          <ul class="recent-list" id="recentList"></ul>
          <div class="recent-empty" id="recentEmpty" style="display:none;">Sin registros</div>
        </aside>
      </section>

      <!-- Buscador -->
      <div class="search-row">
        <div class="search-bar">
          <input id="q" placeholder="Buscar por nombre, RFC o beneficiario...">
        </div>
        <div class="table-actions">
          <button class="btn btn-outline" id="btnBuscar" type="button">Filtros</button>
        </div>
      </div>

      <!-- Tabla -->
      <section class="panel table-panel" id="tabla">
        <table>
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Nombre</th>
              <th>RFC</th>
              <th>Beneficiario</th>
              <th>Ingreso</th>
              <th>Pensión</th>
              <th>Rate</th>
              <th>Recuperación</th>
              <th>Estado</th>
              <th style="text-align:right;">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody">
            <tr><td class="no-result" colspan="10">Cargando...</td></tr>
          </tbody>
        </table>
        <div class="table-foot">
          <span id="tableInfo">Mostrando 0 registros</span>
          <span>10 por página</span>
        </div>
      </section>

      <!-- MODAL DETALLE -->
      <div class="modal" id="modalDetalle" aria-hidden="true">
        <div class="dialog dialog-lg">
          <header>
            <h3>📄 Detalle del registro</h3>
            <button class="close" id="btnCloseDetalle" type="button">✖</button>
          </header>
          <div class="content" id="detalleBody"></div>
          <div class="foot">
            <button class="btn btn-outline" id="btnCerrar2" type="button">Cerrar</button>
            <button class="btn btn-secondary" id="btnAbrirComplemento" type="button" style="display:none;">📄 Abrir complemento</button>
          </div>
        </div>
      </div>
    </main>
  </div>

<script>
(function(){
  const BASE_URL = <?= json_encode($BASE_URL, JSON_UNESCAPED_SLASHES) ?>;

  // ===== Tema =====
  const root = document.documentElement;
  const savedTheme = localStorage.getItem('cip_theme') || 'dark';
  root.setAttribute('data-theme', savedTheme);

  let chart = null;
  let lastChartData = null;

  const btnTheme = document.getElementById('btnTheme');
  btnTheme?.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('cip_theme', next);
    if (lastChartData) renderChart(lastChartData.labels, lastChartData.values);
  });

  // ===== Utils =====
  const fmtMoney = (n) => new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN' }).format(Number(n || 0));
  const fmtPct = (n) => ((Number(n || 0) * 100).toFixed(2) + '%');

  function escapeHtml(s){
    return String(s ?? '').replace(/[&<>"']/g, m => ({
      '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'
    }[m]));
  }

  function isCanceladoComplemento(r){
    const estatus = String(r.estatus || '').toLowerCase().trim();
    const canceladoAt = String(r.cancelado_at || '').trim();
    const canceladoPor = String(r.cancelado_por || '').trim();
    const motivo = String(r.motivo_cancelacion || '').trim();
    return estatus.includes('cancel') || canceladoAt !== '' || canceladoPor !== '' || motivo !== '';
  }

  function estadoBadge(r){
    return isCanceladoComplemento(r)
      ? '<span class="badge-status cancelado">Cancelado</span>'
      : '<span class="badge-status activo">Activo</span>';
  }

  async function fetchJsonDebug(url, opts = {}){
    const res  = await fetch(url, { cache:'no-store', ...opts });
    const text = await res.text();

    if (!res.ok){
      console.error('❌ HTTP', res.status, url, '\nBody:\n', text);
      throw new Error(`HTTP ${res.status}: ${text}`);
    }

    try{
      return JSON.parse(text);
    }catch(e){
      console.error('❌ Respuesta NO es JSON:', url, '\nBody:\n', text);
      throw new Error('Respuesta no es JSON');
    }
  }

  // ===== Chart =====
  const canvasEl = document.getElementById('chartMain');

  function renderChart(labels, values){
    const empty = document.getElementById('chartEmpty');
    if (!canvasEl) return;

    const nums = (values || []).map(v => Number(v || 0));

    if (!labels || labels.length === 0){
      empty && (empty.style.display = 'block');
      if (chart) { chart.destroy(); chart = null; }
      return;
    }

    empty && (empty.style.display = 'none');
    if (chart) { chart.destroy(); chart = null; }

    lastChartData = { labels: [...labels], values: [...nums] };
    const textColor = getComputedStyle(root).getPropertyValue('--muted-2').trim();
    const gridColor = getComputedStyle(root).getPropertyValue('--line-2').trim() || 'rgba(148,163,184,.18)';

    chart = new Chart(canvasEl, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          label: 'Clientes',
          data: nums,
          tension: 0.42,
          fill: true,
          borderWidth: 3,
          pointRadius: 4,
          pointHoverRadius: 7,
          pointBorderWidth: 2,
          borderColor: '#2d8cff',
          pointBackgroundColor: '#071426',
          pointBorderColor: '#7fc4ff',
          backgroundColor: (context) => {
            const c = context.chart;
            const { ctx, chartArea } = c;
            if (!chartArea) return 'rgba(45,140,255,.12)';
            const g = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
            g.addColorStop(0, 'rgba(45,140,255,.34)');
            g.addColorStop(1, 'rgba(45,140,255,0)');
            return g;
          }
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: { callbacks: { label: (c) => ` Clientes: ${c.parsed.y ?? 0}` } }
        },
        scales: {
          x: { grid: { color: gridColor }, ticks: { color: textColor } },
          y: {
            beginAtZero: true,
            suggestedMax: Math.max(...nums, 1),
            grid: { color: gridColor },
            ticks: { stepSize: 1, color: textColor }
          }
        }
      }
    });
  }

  // ===== DOM =====
  const tbody = document.getElementById('tbody');
  const recentList = document.getElementById('recentList');
  const recentEmpty = document.getElementById('recentEmpty');
  const yearsBox = document.getElementById('yearsBox');
  const tableInfo = document.getElementById('tableInfo');

  // ===== Modal detalle =====
  const modal = document.getElementById('modalDetalle');
  const detalleBody = document.getElementById('detalleBody');
  const btnCloseX = document.getElementById('btnCloseDetalle');
  const btnCerrar2 = document.getElementById('btnCerrar2');
  const btnAbrir = document.getElementById('btnAbrirComplemento');
  let lastFocusEl = null;

  function openModal(){
    if (!modal) return;
    lastFocusEl = document.activeElement;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    setTimeout(() => btnCloseX?.focus?.(), 0);
  }

  function closeModal(){
    if (!modal) return;
    const active = document.activeElement;
    if (active && modal.contains(active)) active.blur();
    modal.classList.remove('show');
    if (btnAbrir) btnAbrir.style.display = 'none';

    setTimeout(() => {
      (lastFocusEl && typeof lastFocusEl.focus === 'function') ? lastFocusEl.focus() : document.body?.focus?.();
      modal.setAttribute('aria-hidden', 'true');
      modal.removeAttribute('aria-modal');
      modal.removeAttribute('role');
    }, 0);
  }

  btnCloseX?.addEventListener('click', closeModal);
  btnCerrar2?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal?.classList.contains('show')) closeModal(); });

  if (btnAbrir){
    btnAbrir.addEventListener('click', () => {
      const id = modal?.dataset?.currentId;
      if (!id) return;

      const url = new URL(`${BASE_URL}/app/controllers/complemento/complemento.html`, location.origin);
      url.searchParams.set('cid', id);
      url.searchParams.set('mode', 'view');
      location.href = url.toString();
    });
  }

  // ===== Load =====
  async function load(){
    const q = document.getElementById('q')?.value.trim() || '';
    const url = new URL('./cipcom_dashboard_data.php', location.href);
    if (q) url.searchParams.set('q', q);
    url.searchParams.set('page_size', '10');

    try{
      const json = await fetchJsonDebug(url.toString());

      if (!json.ok){
        const errMsg = json.error || 'No se pudo cargar';
        if (tbody) tbody.innerHTML = `<tr><td class="no-result" colspan="10">❌ ${escapeHtml(errMsg)}</td></tr>`;
        return;
      }

      const setTxt = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
      setTxt('kpiUsers', json.stats?.total_usuarios ?? 0);
      setTxt('kpiTotal', json.stats?.total_registros ?? 0);
      setTxt('kpiIngresoYear', fmtMoney(json.stats?.ingreso_anio_actual));
      setTxt('kpiIngresoPrev', fmtMoney(json.stats?.ingreso_anio_anterior));
      setTxt('kpiRecYear', fmtMoney(json.stats?.recuperacion_anio_actual));

      renderChart(json.chart?.labels || [], json.chart?.values || []);

      if (yearsBox){
        yearsBox.innerHTML = '';
        (json.years || []).forEach(y => {
          const el = document.createElement('div');
          el.className = 'year-pill';
          el.textContent = `${y.anio}: ${fmtMoney(y.ingreso_total)} • ${y.usuarios} usuarios`;
          yearsBox.appendChild(el);
        });
      }

      if (recentList){
        recentList.innerHTML = '';
        if (!json.recent?.length){
          if (recentEmpty) recentEmpty.style.display = 'block';
        } else {
          if (recentEmpty) recentEmpty.style.display = 'none';
          json.recent.forEach(r => {
            const li = document.createElement('li');
            li.className = 'recent-item';
            const initials = String(r.nombre_completo || 'C').trim().charAt(0).toUpperCase() || 'C';
            li.innerHTML = `
              <div class="recent-dot">${escapeHtml(initials)}</div>
              <div class="recent-main">
                <div class="recent-name">${escapeHtml(r.nombre_completo || '')}</div>
                <div class="recent-folio">${escapeHtml(r.rfc || '')}</div>
                <div class="recent-meta">${escapeHtml(r.created_at || '')}</div>
              </div>
              <div class="recent-status"></div>`;
            recentList.appendChild(li);
          });
        }
      }

      const rows = Array.isArray(json.rows) ? json.rows : [];
      if (tableInfo) tableInfo.textContent = `Mostrando 1 a ${rows.length} de ${json.total_rows ?? rows.length} registros`;

      if (!rows.length){
        if (tbody) tbody.innerHTML = `<tr><td class="no-result" colspan="10">Sin resultados<span>Prueba otra búsqueda</span></td></tr>`;
      } else {
        if (tbody) tbody.innerHTML = rows.map(r => `
          <tr>
            <td>${escapeHtml(r.created_at || '')}</td>
            <td>${escapeHtml(r.nombre_completo || '')}</td>
            <td>${escapeHtml(r.rfc || '')}</td>
            <td>${escapeHtml(r.beneficiario || '')}</td>
            <td>${fmtMoney(r.ingreso_capital)}</td>
            <td>${fmtMoney(r.pension_base)}</td>
            <td>${fmtPct(r.rendimiento_rate)}</td>
            <td>${fmtMoney(r.resumen_total)}</td>
            <td>${estadoBadge(r)}</td>
            <td>
              <div class="acciones">
                <button class="btn-sm" data-detalle="${escapeHtml(r.id)}" type="button">👁</button>
              </div>
            </td>
          </tr>
        `).join('');
      }

    } catch(err){
      console.error(err);
      if (tbody) tbody.innerHTML = `<tr><td class="no-result" colspan="10">❌ ${escapeHtml(err.message || 'Error de red / servidor')}</td></tr>`;
    }
  }

  // ===== Click Ver detalle =====
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-detalle]');
    if (!btn) return;

    const id = btn.getAttribute('data-detalle');
    if (!id || !modal) return;

    modal.dataset.currentId = id;
    if (detalleBody) detalleBody.innerHTML = 'Cargando...';
    openModal();
    if (btnAbrir) btnAbrir.style.display = 'inline-flex';

    try{
      const url = new URL('./cipcom_detalle.php', location.href);
      url.searchParams.set('id', id);

      const json = await fetchJsonDebug(url.toString());

      if (!json.ok){
        if (detalleBody) detalleBody.innerHTML = `<div class="no-result">❌ ${escapeHtml(json.error || 'Error')}</div>`;
        return;
      }

      const d = json.data || {};
      if (detalleBody) detalleBody.innerHTML = `
        <div class="docs-grid">
          <div class="doc-card">
            <div class="doc-icon">👤</div>
            <div class="doc-body">
              <div class="doc-title">${escapeHtml(d.nombre_completo || '')}</div>
              <div class="doc-meta">
                RFC: <b>${escapeHtml(d.rfc || '')}</b> • Beneficiario: <b>${escapeHtml(d.beneficiario || '')}</b><br>
                Fecha: ${escapeHtml(d.created_at || '')}<br>
                Estado: ${estadoBadge(d)}
              </div>
              <span class="badge"><span class="dot"></span> Guardado</span>
            </div>
          </div>

          <div class="doc-card">
            <div class="doc-icon">💰</div>
            <div class="doc-body">
              <div class="doc-title">Resumen</div>
              <div class="doc-meta">
                Ingreso: <b>${fmtMoney(d.ingreso_capital)}</b> •
                Aportación: <b>${fmtMoney(d.aportacion_mensual)}</b> •
                Pensión: <b>${fmtMoney(d.pension_base)}</b> •
                Rate: <b>${fmtPct(d.rendimiento_rate)}</b>
              </div>
              <div class="doc-meta">
                Rendimiento: <b>${fmtMoney(d.resumen_rendimiento)}</b> •
                Capital: <b>${fmtMoney(d.resumen_capital)}</b> •
                Total: <b>${fmtMoney(d.resumen_total)}</b>
              </div>
            </div>
          </div>
        </div>
      `;

    } catch(err){
      console.error(err);
      if (detalleBody) detalleBody.innerHTML = `<div class="no-result">❌ ${escapeHtml(err.message || 'Error al cargar detalle')}</div>`;
    }
  });

  // ===== Eventos =====
  document.getElementById('btnRefresh')?.addEventListener('click', load);
  document.getElementById('btnBuscar')?.addEventListener('click', load);
  document.getElementById('q')?.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') load(); });

  document.getElementById('btnBack')?.addEventListener('click', () => {
    location.href = new URL(`${BASE_URL}/home.php`, location.origin).toString();
  });

  document.getElementById('btnAgregar')?.addEventListener('click', () => {
    location.href = new URL(`${BASE_URL}/app/controllers/complemento/complemento.html`, location.origin).toString();
  });

  load();
})();
</script>

</body>
</html>
