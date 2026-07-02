<?php
// /public/ahorro_dashboard.php
declare(strict_types=1);
require_once __DIR__ . '/app/controllers/auth/require_login.php';

$asesor = $_SESSION['asesor'] ?? [];
$userName = htmlspecialchars((string)($asesor['nombre'] ?? $_SESSION['user_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es" data-theme="dark">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Dashboard Ahorro | CIP</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root{
  --bg-page:#071426;
  --bg-deep:#020817;
  --bg-sidebar:#061121;
  --bg-card:#101d31;
  --bg-card-2:#0d1a2c;
  --border:#1c4168;
  --border-soft:rgba(96,165,250,.24);
  --text:#f8fafc;
  --muted:#9fb4d0;
  --blue:#268bff;
  --blue-2:#0d6efd;
  --green:#22c55e;
  --purple:#7c3aed;
  --orange:#f59e0b;
  --red:#ef4444;
  --cyan:#22d3ee;
  --shadow:0 24px 70px rgba(0,0,0,.35);
  --radius:18px;
  --sidebar-w:292px;
}

:root[data-theme="light"]{
  --bg-page:#edf4ff;
  --bg-deep:#f8fbff;
  --bg-sidebar:#ffffff;
  --bg-card:#ffffff;
  --bg-card-2:#f8fbff;
  --border:#cbdaf0;
  --border-soft:rgba(37,99,235,.20);
  --text:#0f172a;
  --muted:#64748b;
  --shadow:0 16px 40px rgba(15,23,42,.12);
}

*{box-sizing:border-box;margin:0;padding:0}
body{
  min-height:100vh;
  font-family:system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
  background:
    radial-gradient(900px 400px at 20% -10%, rgba(37,99,235,.22), transparent 60%),
    radial-gradient(700px 420px at 90% 10%, rgba(34,211,238,.10), transparent 55%),
    linear-gradient(135deg, var(--bg-deep), var(--bg-page));
  color:var(--text);
  overflow-x:hidden;
}

.app-shell{
  min-height:100vh;
  display:grid;
  grid-template-columns:var(--sidebar-w) minmax(0,1fr);
}

/* ================= SIDEBAR ================= */
.side-nav{
  position:sticky;
  top:0;
  height:100vh;
  padding:22px 18px;
  background:
    radial-gradient(560px 260px at 40% -10%, rgba(37,99,235,.18), transparent 65%),
    linear-gradient(180deg, rgba(5,15,31,.96), rgba(1,8,20,.98));
  border-right:1px solid var(--border);
  display:flex;
  flex-direction:column;
  gap:18px;
  z-index:30;
}
:root[data-theme="light"] .side-nav{background:#ffffff}

.brand{
  display:flex;
  align-items:center;
  gap:10px;
  padding:0 6px 20px;
  border-bottom:1px solid var(--border-soft);
}
.brand-logo{
  font-size:2.15rem;
  line-height:1;
  font-weight:950;
  letter-spacing:-.08em;
}
.brand-text{
  display:flex;
  flex-direction:column;
  color:#93c5fd;
  font-size:.86rem;
  line-height:1.08;
}

.user-card{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px;
  border:1px solid var(--border-soft);
  border-radius:18px;
  background:rgba(37,99,235,.08);
}
.user-avatar{
  width:48px;height:48px;border-radius:999px;
  display:grid;place-items:center;
  background:linear-gradient(135deg,var(--blue),var(--purple));
  box-shadow:0 16px 35px rgba(37,99,235,.28);
  flex:0 0 48px;
}
.user-meta{min-width:0;line-height:1.2}
.user-meta strong{display:block;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-transform:uppercase}
.user-meta span{color:var(--muted);font-size:.78rem}

.side-links{
  display:flex;
  flex-direction:column;
  gap:8px;
  overflow:auto;
  padding-right:2px;
}
.side-link{
  width:100%;
  min-height:48px;
  display:flex;
  align-items:center;
  gap:12px;
  padding:12px 14px;
  border-radius:14px;
  border:1px solid transparent;
  background:transparent;
  color:var(--text);
  text-decoration:none;
  cursor:pointer;
  text-align:left;
  font-weight:700;
  font-size:.94rem;
  transition:.16s ease;
}
.side-link:hover{background:rgba(37,99,235,.12);border-color:var(--border-soft);transform:translateY(-1px)}
.side-link.is-active{background:linear-gradient(135deg,#1e88ff,#0d6efd);box-shadow:0 16px 35px rgba(37,99,235,.34);}
.side-link.danger{color:#fecaca;margin-top:auto;border-top:1px solid transparent}
.side-link.danger:hover{background:rgba(239,68,68,.10);border-color:rgba(239,68,68,.28)}
.side-sep{height:1px;background:var(--border-soft);margin:8px 0}
.side-close{display:none}
.side-backdrop{display:none}

/* ================= MAIN ================= */
.main-area{
  min-width:0;
  padding:28px 28px 32px;
}
.topbar{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:18px;
  margin-bottom:18px;
}
.title-block h1{font-size:1.75rem;letter-spacing:-.03em;margin-bottom:4px}
.title-block p{color:var(--muted);font-size:.95rem}
.top-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;justify-content:flex-end}
.date-pill,
.btn-primary,
.btn-ghost{
  min-height:46px;
  border-radius:14px;
  border:1px solid var(--border-soft);
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:0 16px;
  color:var(--text);
  text-decoration:none;
  background:rgba(12,25,45,.72);
  font-weight:800;
  cursor:pointer;
}
:root[data-theme="light"] .date-pill,
:root[data-theme="light"] .btn-ghost{background:#fff}
.btn-primary{border-color:rgba(37,99,235,.55);background:linear-gradient(135deg,#1e88ff,#0d6efd);box-shadow:0 16px 35px rgba(37,99,235,.30)}
.btn-primary:hover,.btn-ghost:hover{filter:brightness(1.08)}
.hamburger{display:none}

.dashboard-layout{
  display:grid;
  grid-template-columns:minmax(0,1fr) 330px;
  gap:18px;
  align-items:start;
}
.left-stack{display:flex;flex-direction:column;gap:18px;min-width:0}
.right-stack{display:flex;flex-direction:column;gap:18px;min-width:0}

/* ================= CARDS ================= */
.kpi-grid{
  display:grid;
  grid-template-columns:repeat(4,minmax(190px,1fr));
  gap:14px;
}
.kpi-card,.panel,.table-panel{
  position:relative;
  overflow:hidden;
  border-radius:var(--radius);
  border:1px solid var(--border-soft);
  background:
    radial-gradient(360px 160px at 95% 100%, rgba(37,99,235,.18), transparent 60%),
    linear-gradient(180deg, rgba(20,35,58,.92), rgba(11,24,43,.92));
  box-shadow:var(--shadow);
}
:root[data-theme="light"] .kpi-card,
:root[data-theme="light"] .panel,
:root[data-theme="light"] .table-panel{background:#fff}
.kpi-card{padding:18px;min-height:132px;display:flex;gap:14px;align-items:flex-start}
.kpi-icon{
  width:56px;height:56px;border-radius:18px;display:grid;place-items:center;
  font-size:1.4rem;flex:0 0 56px;
  box-shadow:0 16px 34px rgba(0,0,0,.22);
}
.kpi-icon.blue{background:rgba(37,99,235,.25);color:#93c5fd}
.kpi-icon.green{background:rgba(34,197,94,.22);color:#86efac}
.kpi-icon.purple{background:rgba(124,58,237,.24);color:#c4b5fd}
.kpi-icon.orange{background:rgba(245,158,11,.22);color:#fde68a}
.kpi-body span{font-size:.84rem;color:#dbeafe}
:root[data-theme="light"] .kpi-body span{color:#475569}
.kpi-body strong{display:block;margin-top:6px;font-size:1.7rem;line-height:1;font-weight:950}
.kpi-body small{display:block;margin-top:10px;color:#22c55e;font-weight:800;font-size:.78rem}
.kpi-body small.red{color:#f87171}

.panel{padding:18px}
.panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:12px}
.panel-title h2{font-size:1.03rem;letter-spacing:-.01em}
.panel-title p{font-size:.84rem;color:var(--muted);margin-top:2px}
.panel-link{color:#60a5fa;text-decoration:none;font-size:.78rem;font-weight:800;white-space:nowrap}
.chart-box{height:300px;position:relative}
.chart-box.small{height:260px}
.chart-box.donut{height:260px}
.main-chart .chart-box{height:310px}
.two-cols{display:grid;grid-template-columns:1fr 1fr;gap:18px}

/* ================= RIGHT PANELS ================= */
.status-list,.side-list{display:flex;flex-direction:column;gap:0}
.status-row,.side-item,.activity-item{
  display:flex;align-items:center;gap:12px;padding:13px 0;border-bottom:1px solid var(--border-soft)
}
.status-dot{width:11px;height:11px;border-radius:999px;flex:0 0 11px}
.status-dot.blue{background:var(--blue)}.status-dot.green{background:var(--green)}.status-dot.red{background:var(--red)}
.status-row b{margin-left:auto;font-size:1rem}
.status-row div{min-width:0}.status-row strong{font-size:.87rem}.status-row span{display:block;color:var(--muted);font-size:.78rem}
.side-icon{width:34px;height:34px;border-radius:12px;display:grid;place-items:center;background:rgba(37,99,235,.18);flex:0 0 34px}
.side-info{min-width:0;flex:1}.side-info strong{display:block;font-size:.83rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.side-info span{display:block;font-size:.76rem;color:var(--muted)}
.days-badge{color:#fde047;font-weight:950;font-size:.86rem;white-space:nowrap}
.empty-side{text-align:center;color:#93a8c5;padding:28px 10px;line-height:1.35}
.percent-row{display:grid;grid-template-columns:42px 1fr 88px;gap:10px;align-items:center;padding:9px 0;font-size:.9rem}
.progress{height:9px;border-radius:999px;background:rgba(148,163,184,.13);overflow:hidden}.progress span{display:block;height:100%;border-radius:999px;background:linear-gradient(90deg,var(--green),var(--cyan));width:0%}
.activity-item .act-icon{width:38px;height:38px;border-radius:999px;display:grid;place-items:center;background:rgba(37,99,235,.28);color:#bfdbfe;flex:0 0 38px;font-weight:950}
.activity-item.cancel .act-icon{background:rgba(239,68,68,.22);color:#fecaca}

/* ================= TABLE ================= */
.table-panel{padding:0}
.table-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px;border-bottom:1px solid var(--border-soft)}
.table-head h2{font-size:1.05rem}.table-head p{color:var(--muted);font-size:.84rem;margin-top:2px}
.filters{display:flex;gap:10px;align-items:center;flex-wrap:wrap;justify-content:flex-end}
.filter-input,.filter-select{
  min-height:42px;border-radius:12px;border:1px solid var(--border-soft);
  background:rgba(7,18,35,.72);color:var(--text);padding:0 13px;outline:none;min-width:210px
}
.filter-select{min-width:170px}
:root[data-theme="light"] .filter-input,:root[data-theme="light"] .filter-select{background:#fff;color:#0f172a}
.table-wrap{overflow:auto}
table{width:100%;border-collapse:collapse;min-width:920px}
th,td{padding:13px 18px;border-bottom:1px solid var(--border-soft);text-align:left;vertical-align:middle}
th{font-size:.74rem;letter-spacing:.05em;text-transform:uppercase;color:#c9d8ee;background:rgba(37,99,235,.12)}
td{font-size:.88rem;color:#eaf2ff}:root[data-theme="light"] td{color:#0f172a}
tbody tr:hover{background:rgba(37,99,235,.08)}
.badge{display:inline-flex;align-items:center;justify-content:center;min-width:82px;padding:6px 10px;border-radius:9px;font-size:.78rem;font-weight:900;color:#fff}
.badge.activo{background:#15803d}.badge.finalizado{background:#0d6efd}.badge.cancelado{background:#b91c1c}
.percent-badge{display:inline-flex;align-items:center;justify-content:center;min-width:54px;padding:6px 10px;border-radius:9px;background:#0d6efd;color:#fff;font-weight:900;font-size:.8rem}
.row-actions{display:flex;gap:8px;align-items:center;justify-content:flex-end}
.icon-btn{width:34px;height:34px;border-radius:10px;border:1px solid var(--border-soft);background:rgba(148,163,184,.10);color:#dbeafe;display:grid;place-items:center;cursor:pointer;text-decoration:none;font-weight:900}
.icon-btn:hover{background:rgba(37,99,235,.22)}
.icon-btn.danger{color:#fca5a5;background:rgba(239,68,68,.10)}
.table-foot{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 18px;color:var(--muted);font-size:.84rem;flex-wrap:wrap}
.pager{display:flex;gap:8px;align-items:center}.page-btn{min-width:38px;height:38px;border-radius:10px;border:1px solid var(--border-soft);background:rgba(7,18,35,.70);color:var(--text);cursor:pointer;padding:0 12px;font-weight:800}.page-btn.active{background:#0d6efd}.page-btn:disabled{opacity:.45;cursor:not-allowed}
.no-result{text-align:center;color:var(--muted);padding:30px;font-style:italic}

/* ================= MODAL DOCUMENTOS ================= */
#docsBackdrop{display:none;position:fixed;inset:0;z-index:90;background:rgba(7,10,18,.66);backdrop-filter:blur(10px)}
#docsModal{display:none;position:fixed;inset:0;z-index:95;padding:22px;overflow:auto}
#docsModal .modal-card{max-width:980px;margin:46px auto;border-radius:20px;overflow:hidden;border:1px solid rgba(255,255,255,.10);box-shadow:0 40px 110px rgba(0,0,0,.55);background:radial-gradient(1100px 460px at 18% 0%,rgba(66,153,225,.22),transparent 56%),radial-gradient(900px 520px at 85% 12%,rgba(72,187,120,.18),transparent 55%),linear-gradient(180deg,#0b1220 0%,#090f1e 100%)}
#docsModal .modal-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:16px 18px 12px;border-bottom:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.03)}
#docsModal .modal-head h2{font-size:1.05rem;color:#eaf0ff}.modal-sub{margin-top:4px;color:rgba(234,240,255,.70);font-size:.82rem}
#docsModal .modal-close{width:42px;height:42px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#eaf0ff;cursor:pointer}
#docsModal .docs-grid{padding:16px 18px 18px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
#docsModal .doc-card2{border-radius:18px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);box-shadow:0 14px 34px rgba(0,0,0,.24);padding:14px;display:flex;gap:12px;align-items:flex-start}
#docsModal .doc-ico{width:46px;height:46px;border-radius:14px;display:grid;place-items:center;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);flex:0 0 46px}
#docsModal .doc-name{font-weight:900;font-size:.95rem;color:#eaf0ff}#docsModal .doc-desc{margin-top:2px;font-size:.80rem;color:rgba(234,240,255,.72)}
#docsModal .doc-actions{display:flex;gap:10px;margin-top:10px;flex-wrap:wrap}.doc-btn{border-radius:999px;padding:10px 14px;font-weight:800;font-size:12px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.06);color:#eaf0ff;cursor:pointer}.doc-btn.upload{background:rgba(34,197,94,.18);border-color:rgba(34,197,94,.35);color:#bbf7d0}.doc-btn.view{background:rgba(59,130,246,.16);border-color:rgba(59,130,246,.34);color:#bfdbfe}.doc-btn:disabled{opacity:.45;cursor:not-allowed}
.doc-status{margin-top:10px;font-size:13px;font-weight:800;color:rgba(234,240,255,.70)}.doc-status.ok{color:#4ade80}.modal-foot{display:flex;justify-content:flex-end;padding:12px 18px 16px;border-top:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.02)}
.chart-year-select{
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  min-width: 130px;
  text-align: center;
  padding-right: 34px;
  background-image:
    linear-gradient(45deg, transparent 50%, #cbd5e1 50%),
    linear-gradient(135deg, #cbd5e1 50%, transparent 50%);
  background-position:
    calc(100% - 18px) 50%,
    calc(100% - 12px) 50%;
  background-size: 6px 6px, 6px 6px;
  background-repeat: no-repeat;
}

.chart-year-select option{
  background:#0f172a;
  color:#e5e7eb;
}
@media(max-width:1380px){.dashboard-layout{grid-template-columns:1fr}.right-stack{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}.kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:1100px){.app-shell{grid-template-columns:1fr}.side-nav{position:fixed;left:0;top:0;transform:translateX(-105%);transition:.18s ease;width:min(320px,86vw);height:100vh}.side-nav.open{transform:translateX(0)}.side-close{display:grid;place-items:center;width:36px;height:36px;border-radius:12px;border:1px solid var(--border-soft);background:transparent;color:var(--text)}.side-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.62);z-index:25}.side-backdrop.show{display:block}.hamburger{display:grid;place-items:center;width:46px;height:46px;border-radius:14px;border:1px solid var(--border-soft);background:rgba(12,25,45,.72);color:var(--text);cursor:pointer}.main-area{padding:20px}.right-stack{grid-template-columns:1fr}.two-cols{grid-template-columns:1fr}}
@media(max-width:720px){.topbar{flex-direction:column}.top-actions{justify-content:flex-start}.kpi-grid{grid-template-columns:1fr}.table-head{align-items:flex-start;flex-direction:column}.filters{width:100%;justify-content:flex-start}.filter-input,.filter-select{width:100%;min-width:0}#docsModal .docs-grid{grid-template-columns:1fr}.main-area{padding:14px}}
</style>
</head>
<body>
  <div class="app-shell">

    <!-- =========================
         SIDEBAR
    ========================== -->
    <aside class="side-nav" id="sideNav" aria-label="Menú lateral">

      <div class="brand">
        <div class="brand-logo">CIP</div>

        <div class="brand-text">
          <span>Financiera</span>
          <span>México</span>
        </div>

        <button
          class="side-close"
          type="button"
          onclick="toggleSidebar(false)"
          aria-label="Cerrar menú">
          ✕
        </button>
      </div>

      <div class="user-card">
        <div class="user-avatar">👤</div>

        <div class="user-meta">
          <strong><?= $userName ?></strong>
          <span>Asesor financiero</span>
        </div>
      </div>

      <nav class="side-links">
        <button
          class="side-link is-active"
          type="button"
          onclick="window.scrollTo({ top: 0, behavior: 'smooth' })">
          <span>▦</span>
          <span>Dashboard</span>
        </button>

        <button class="side-link" type="button" onclick="irInicio()">
          <span>←</span>
          <span>Regresar al panel</span>
        </button>

        <button class="side-link" type="button" onclick="nuevoAhorro()">
          <span>＋</span>
          <span>Nuevo contrato</span>
        </button>

        <button
          class="side-link"
          type="button"
          onclick="document.getElementById('q')?.focus()">
          <span>⌕</span>
          <span>Buscar contrato</span>
        </button>

        <button class="side-link" id="btnTema2" type="button" onclick="toggleTheme()">
          <span>☀️</span>
          <span>Modo claro</span>
        </button>

        <div class="side-sep"></div>

        <button class="side-link danger" type="button" onclick="cerrarSesion()">
          <span>⏻</span>
          <span>Cerrar sesión</span>
        </button>
      </nav>
    </aside>

    <div
      class="side-backdrop"
      id="sideBackdrop"
      onclick="toggleSidebar(false)">
    </div>

    <!-- =========================
         MAIN
    ========================== -->
    <main class="main-area">

      <!-- TOPBAR -->
      <div class="topbar">
        <div class="title-block">
          <button
            class="hamburger"
            type="button"
            onclick="toggleSidebar(true)"
            aria-label="Abrir menú">
            ☰
          </button>

          <div>
            <h1>Dashboard Ahorro</h1>
            <p>Resumen general de tu actividad y contratos de ahorro.</p>
          </div>
        </div>

        <div class="top-actions">
          <div class="date-pill">
            🗓️ <span id="todayLabel">—</span>
          </div>

          <a
            class="btn-primary"
            href="#"
            onclick="nuevoAhorro(); return false;">
            ＋ Nuevo contrato
          </a>
        </div>
      </div>

      <div class="dashboard-layout">

        <!-- =========================
             COLUMNA IZQUIERDA
        ========================== -->
        <div class="left-stack">

          <!-- KPIS -->
          <section class="kpi-grid" aria-label="Indicadores principales">

            <article class="kpi-card">
              <div class="kpi-icon blue">📄</div>
              <div class="kpi-body">
                <span>Contratos totales</span>
                <strong id="kpi-total">0</strong>
                <small>↑ Control general actualizado</small>
              </div>
            </article>

            <article class="kpi-card">
              <div class="kpi-icon green">$</div>
              <div class="kpi-body">
                <span>Monto semanal promedio</span>
                <strong id="kpi-prom-semanal">$0.00</strong>
                <small>↑ Promedio calculado</small>
              </div>
            </article>

            <article class="kpi-card">
              <div class="kpi-icon purple">👥</div>
              <div class="kpi-body">
                <span>Clientes activos</span>
                <strong id="kpi-activos">0</strong>
                <small>↑ En curso actualmente</small>
              </div>
            </article>

            <article class="kpi-card">
              <div class="kpi-icon orange">🧾</div>
              <div class="kpi-body">
                <span>Contratos cancelados</span>
                <strong id="kpi-cancelados">0</strong>
                <small class="red">↓ Registros cancelados</small>
              </div>
            </article>

          </section>

          <!-- GRÁFICA PRINCIPAL -->
          <section class="panel main-chart">
            <div class="panel-head">
              <div class="panel-title">
                <h2>Top 3 planes con mayor auge</h2>
                <p>Comparativo mensual de los porcentajes con más contratos.</p>
              </div>

              <select
                id="filtro-anio-grafica"
                class="btn-ghost chart-year-select"
                onchange="cambiarAnioGrafica()">
                <option value="">Cargando años...</option>
              </select>
            </div>

            <div class="chart-box">
              <canvas id="chartAhorroMensual"></canvas>
            </div>
          </section>

          <!-- GRÁFICAS SECUNDARIAS -->
          <section class="two-cols">

            <article class="panel">
              <div class="panel-head">
                <div class="panel-title">
                  <h2>Ahorros por estado</h2>
                  <p>Distribución de contratos.</p>
                </div>
              </div>

              <div class="chart-box donut">
                <canvas id="chartEstadosAhorro"></canvas>
              </div>
            </article>

            <article class="panel">
              <div class="panel-head">
                <div class="panel-title">
                  <h2>Contratos mensuales</h2>
                  <p>Número de contratos por mes.</p>
                </div>
              </div>

              <div class="chart-box small">
                <canvas id="chartContratosMensuales"></canvas>
              </div>
            </article>

          </section>

          <!-- TABLA -->
          <section class="table-panel" id="tabla">
            <div class="table-head">
              <div>
                <h2>Contratos de ahorro recientes</h2>
                <p>Administra y consulta tus contratos registrados.</p>
              </div>

              <div class="filters">
                <input
                  class="filter-input"
                  id="q"
                  placeholder="Buscar por nombre o folio..." />

                <select class="filter-select" id="estado">
                  <option value="">Todos los estados</option>
                  <option value="activo">Activo</option>
                  <option value="finalizado">Finalizado</option>
                  <option value="cancelado">Cancelado</option>
                </select>

                <select class="filter-select" id="porcentaje">
                  <option value="">Todos los porcentajes</option>
                  <option value="5">5%</option>
                  <option value="10">10%</option>
                  <option value="15">15%</option>
                  <option value="20">20%</option>
                  <option value="25">25%</option>
                </select>
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Folio</th>
                    <th>Cliente</th>
                    <th>Monto semanal</th>
                    <th>Porcentaje</th>
                    <th>Fecha inicio</th>
                    <th>Fecha devolución</th>
                    <th>Estado</th>
                    <th style="text-align:right">Acciones</th>
                  </tr>
                </thead>

                <tbody id="tbody">
                  <tr>
                    <td colspan="8" class="no-result">Cargando…</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="table-foot">
              <div id="info">0 registros</div>

              <div class="pager">
                <button class="page-btn" id="prev" type="button">Anterior</button>
                <button class="page-btn active" id="page" type="button">1</button>
                <button class="page-btn" id="next" type="button">Siguiente</button>
              </div>
            </div>
          </section>

        </div>

        <!-- =========================
             COLUMNA DERECHA
        ========================== -->
        <aside class="right-stack">

          <section class="panel">
            <div class="panel-head">
              <div class="panel-title">
                <h2>Próximos a devolución</h2>
              </div>

              <a
                class="panel-link"
                href="#tabla"
                onclick="document.getElementById('tabla')?.scrollIntoView({ behavior: 'smooth' }); return false;">
                Ver todos
              </a>
            </div>

            <div class="side-list" id="proximosList">
              <div class="empty-side">No hay contratos próximos.</div>
            </div>
          </section>

          <section class="panel">
            <div class="panel-head">
              <div class="panel-title">
                <h2>Monto por porcentaje</h2>
              </div>

              <a class="panel-link" href="#tabla">
                Ver todos
              </a>
            </div>

            <div id="percentList"></div>
          </section>

          <section class="panel">
            <div class="panel-head">
              <div class="panel-title">
                <h2>Actividad reciente</h2>
              </div>

              <a
                class="panel-link"
                href="#tabla"
                onclick="document.getElementById('tabla')?.scrollIntoView({ behavior: 'smooth' }); return false;">
                Ver tabla
              </a>
            </div>

            <div id="activityList"></div>
          </section>

        </aside>

      </div>
    </main>
  </div>

  <!-- =========================
       MODAL DOCUMENTOS
  ========================== -->
  <div
    class="modal-backdrop"
    id="docsBackdrop"
    onclick="closeDocsModal()">
  </div>

  <div class="modal" id="docsModal" aria-hidden="true">
    <div class="modal-card">

      <div class="modal-head">
        <div>
          <h2>📄 Documentos</h2>
          <div class="modal-sub" id="docsInfo">Registro: —</div>
        </div>

        <button
          class="modal-close"
          type="button"
          onclick="closeDocsModal()">
          ✕
        </button>
      </div>

      <div class="docs-grid" id="docsGrid">

        <div class="doc-card2" data-tipo="INE">
          <div class="doc-ico">🪪</div>

          <div class="doc-meta">
            <div class="doc-name">INE</div>
            <div class="doc-desc">Identificación oficial</div>

            <div class="doc-actions">
              <button class="doc-btn upload" type="button" onclick="subirDoc('INE')">
                ⬆ Subir PDF
              </button>

              <button class="doc-btn view" type="button" onclick="verDoc('INE')" disabled>
                👁 Ver
              </button>
            </div>

            <div class="doc-status" id="st-INE">⏳ Pendiente</div>
          </div>
        </div>

        <div class="doc-card2" data-tipo="DOMICILIO">
          <div class="doc-ico">🏠</div>

          <div class="doc-meta">
            <div class="doc-name">Comprobante de domicilio</div>
            <div class="doc-desc">Recibo/servicio reciente</div>

            <div class="doc-actions">
              <button class="doc-btn upload" type="button" onclick="subirDoc('DOMICILIO')">
                ⬆ Subir PDF
              </button>

              <button class="doc-btn view" type="button" onclick="verDoc('DOMICILIO')" disabled>
                👁 Ver
              </button>
            </div>

            <div class="doc-status" id="st-DOMICILIO">⏳ Pendiente</div>
          </div>
        </div>

        <div class="doc-card2" data-tipo="RFC">
          <div class="doc-ico">🧾</div>

          <div class="doc-meta">
            <div class="doc-name">RFC</div>
            <div class="doc-desc">Constancia fiscal</div>

            <div class="doc-actions">
              <button class="doc-btn upload" type="button" onclick="subirDoc('RFC')">
                ⬆ Subir PDF
              </button>

              <button class="doc-btn view" type="button" onclick="verDoc('RFC')" disabled>
                👁 Ver
              </button>
            </div>

            <div class="doc-status" id="st-RFC">⏳ Pendiente</div>
          </div>
        </div>

        <div class="doc-card2" data-tipo="ESTADO_CUENTA">
          <div class="doc-ico">🏦</div>

          <div class="doc-meta">
            <div class="doc-name">Estado de cuenta</div>
            <div class="doc-desc">Banco / últimos meses</div>

            <div class="doc-actions">
              <button class="doc-btn upload" type="button" onclick="subirDoc('ESTADO_CUENTA')">
                ⬆ Subir PDF
              </button>

              <button class="doc-btn view" type="button" onclick="verDoc('ESTADO_CUENTA')" disabled>
                👁 Ver
              </button>
            </div>

            <div class="doc-status" id="st-ESTADO_CUENTA">⏳ Pendiente</div>
          </div>
        </div>

        <div class="doc-card2" data-tipo="CONTRATO">
          <div class="doc-ico">📑</div>

          <div class="doc-meta">
            <div class="doc-name">Contrato</div>
            <div class="doc-desc">Documento firmado</div>

            <div class="doc-actions">
              <button class="doc-btn upload" type="button" onclick="subirDoc('CONTRATO')">
                ⬆ Subir PDF
              </button>

              <button class="doc-btn view" type="button" onclick="verDoc('CONTRATO')" disabled>
                👁 Ver
              </button>
            </div>

            <div class="doc-status" id="st-CONTRATO">⏳ Pendiente</div>
          </div>
        </div>

      </div>

      <div class="modal-foot">
        <button class="btn-ghost" type="button" onclick="closeDocsModal()">
          Cerrar
        </button>
      </div>

    </div>
  </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* =====================================================
   CONFIGURACIÓN BASE
===================================================== */
const BASE_URL = (() => {
  const path = window.location.pathname;

  if (path.includes('/sempiternal/public/')) {
    return '/sempiternal/public/';
  }

  return '/';
})();

const URL_LOGOUT = `${BASE_URL}app/controllers/auth/logout.php`;
const THEME_KEY = 'cip_theme';

const state = {
  page: 1,
  per: 25,
  total: 0,
  rows: [],
  anioGrafica: new Date().getFullYear()
};

let chartAhorroMensual = null;
let chartEstadosAhorro = null;
let chartContratosMensuales = null;


/* =====================================================
   HELPERS GENERALES
===================================================== */
function money(n) {
  return new Intl.NumberFormat('es-MX', {
    style: 'currency',
    currency: 'MXN',
    minimumFractionDigits: 2
  }).format(Number(n || 0));
}

function escHTML(v) {
  return String(v ?? '').replace(/[&<>"]/g, m => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;'
  }[m]));
}

const escJS = (v) => JSON.stringify(String(v ?? '')).replace(/"/g, '&quot;');

function parseDate(v) {
  if (!v) return null;

  const d = new Date(String(v).replace(' ', 'T'));
  return isNaN(d.getTime()) ? null : d;
}

function formatDateMX(v) {
  const d = parseDate(v);

  if (!d) return v || '—';

  return d.toLocaleDateString('es-MX');
}

function monthIndex(v) {
  const d = parseDate(v);
  return d ? d.getMonth() : null;
}

function getFechaRegistro(row) {
  return row.fecha_inicio_ahorro || row.created_at || row.fecha_registro || '';
}

function badge(estado) {
  const e = String(estado || 'activo').toLowerCase();
  const cls = ['activo', 'finalizado', 'cancelado'].includes(e) ? e : 'activo';

  return `<span class="badge ${cls}">${escHTML(e || 'activo')}</span>`;
}


/* =====================================================
   SIDEBAR / NAVEGACIÓN
===================================================== */
function toggleSidebar(open) {
  document.getElementById('sideNav')?.classList.toggle('open', !!open);
  document.getElementById('sideBackdrop')?.classList.toggle('show', !!open);

  document.body.style.overflow = open ? 'hidden' : '';
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 1100) {
    toggleSidebar(false);
  }
});

function irInicio() {
  window.location.href = `${BASE_URL}home.php`;
}

function nuevoAhorro() {
  window.location.href = `${BASE_URL}ahorros.php`;
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
  } catch (_) {}

  window.location.href = `${BASE_URL}login.html`;
}


/* =====================================================
   TEMA CLARO / OSCURO
===================================================== */
function applyTheme(theme) {
  if (theme !== 'light' && theme !== 'dark') {
    theme = 'dark';
  }

  document.documentElement.setAttribute('data-theme', theme);

  try {
    localStorage.setItem(THEME_KEY, theme);
  } catch (e) {}

  const label = theme === 'dark' ? '☀️ Modo claro' : '🌙 Modo oscuro';

  const btn2 = document.getElementById('btnTema2');
  if (btn2) {
    btn2.innerHTML = `
      <span>${label.split(' ')[0]}</span>
      <span>${label.replace(/^\S+\s/, '')}</span>
    `;
  }

  renderCharts(state.rows || []);
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}


/* =====================================================
   INICIALIZACIÓN
===================================================== */
document.addEventListener('DOMContentLoaded', () => {
  let savedTheme = 'dark';

  try {
    savedTheme = localStorage.getItem(THEME_KEY) || 'dark';
  } catch (e) {}

  applyTheme(savedTheme);

  const today = document.getElementById('todayLabel');
  if (today) {
    today.textContent = new Date().toLocaleDateString('es-MX');
  }

  bindEvents();
  load();
});


/* =====================================================
   AÑOS DE GRÁFICA
===================================================== */
function getAniosDesdeRows(rows) {
  const anios = new Set();

  (Array.isArray(rows) ? rows : []).forEach(r => {
    const d = parseDate(getFechaRegistro(r));

    if (d) {
      anios.add(d.getFullYear());
    }
  });

  return Array.from(anios).sort((a, b) => b - a);
}

function cargarAniosGrafica(anios) {
  const select = document.getElementById('filtro-anio-grafica');
  if (!select) return;

  const actual = new Date().getFullYear();

  anios = Array.isArray(anios) ? anios : [];
  anios = anios
    .map(a => Number(a))
    .filter(a => a >= 2000 && a <= 2100)
    .sort((a, b) => b - a);

  if (!anios.includes(actual)) {
    anios.unshift(actual);
  }

  anios = [...new Set(anios)];

  select.innerHTML = anios.map(anio => {
    const selected = Number(state.anioGrafica) === Number(anio) ? 'selected' : '';

    return `<option value="${anio}" ${selected}>${anio}</option>`;
  }).join('');

  if (!select.value) {
    state.anioGrafica = actual;
    select.value = String(actual);
  }
}

function cambiarAnioGrafica() {
  const select = document.getElementById('filtro-anio-grafica');

  state.anioGrafica = Number(select?.value || new Date().getFullYear());

  renderCharts(state.rows || []);
}


/* =====================================================
   MÉTRICAS
===================================================== */
function getRowsMetrics(rows, total) {
  const counts = {
    activo: 0,
    finalizado: 0,
    cancelado: 0
  };

  const monthlyAmount = Array(12).fill(0);
  const monthlyCount = Array(12).fill(0);
  const percentAmounts = {};

  const today = new Date();
  today.setHours(0, 0, 0, 0);

  const prox = [];
  const unique = {};
  let sum = 0;

  rows = Array.isArray(rows) ? rows : [];

  rows.forEach(r => {
    const estado = String(r.estado || 'activo').toLowerCase();
    const estadoKey = counts.hasOwnProperty(estado) ? estado : 'activo';

    counts[estadoKey]++;

    const monto = Number(r.monto_semanal || 0);
    sum += monto;

    const fechaRegistro = getFechaRegistro(r);
    const dRegistro = parseDate(fechaRegistro);

    if (dRegistro && dRegistro.getFullYear() === Number(state.anioGrafica)) {
      const mi = dRegistro.getMonth();
      monthlyAmount[mi] += monto;
      monthlyCount[mi]++;
    }

    const p = String(Number(r.porcentaje || 0).toFixed(0));
    if (Number(p) > 0) {
      percentAmounts[p] = (percentAmounts[p] || 0) + monto;
    }

    const nombre = [r.nombre, r.ap_paterno, r.ap_materno].filter(Boolean).join(' ');
    if (nombre) {
      unique[nombre.toLowerCase()] = true;
    }

    if (estado !== 'cancelado' && r.fecha_devolucion) {
      const d = parseDate(r.fecha_devolucion);

      if (d) {
        d.setHours(0, 0, 0, 0);

        const days = Math.ceil((d - today) / 86400000);

        if (days >= 0 && days <= 30) {
          prox.push({
            ...r,
            _days: days
          });
        }
      }
    }
  });

  prox.sort((a, b) => a._days - b._days);

  return {
    counts,
    monthlyAmount,
    monthlyCount,
    percentAmounts,
    prox: prox.slice(0, 4),
    clientes: Object.keys(unique).length,
    prom: rows.length ? sum / rows.length : 0,
    total: total || rows.length
  };
}


/* =====================================================
   PANELES LATERALES Y KPIS
===================================================== */
function actualizarPaneles(rows, total) {
  const m = getRowsMetrics(rows, total);

  document.getElementById('kpi-total').textContent = String(m.total);
  document.getElementById('kpi-activos').textContent = String(m.counts.activo);
  document.getElementById('kpi-cancelados').textContent = String(m.counts.cancelado);
  document.getElementById('kpi-prom-semanal').textContent = money(m.prom);

  renderProximos(m.prox);
  renderMontoPorPorcentaje(m.percentAmounts);
  renderActividad(rows);

  renderCharts(rows);
}

function renderProximos(proximos) {
  const proxEl = document.getElementById('proximosList');
  if (!proxEl) return;

  if (!proximos.length) {
    proxEl.innerHTML = `
      <div class="empty-side">
        No hay contratos próximos a devolución en los próximos 30 días.
      </div>
    `;
    return;
  }

  proxEl.innerHTML = proximos.map(r => {
    const nombre = [r.nombre, r.ap_paterno, r.ap_materno].filter(Boolean).join(' ') || '—';
    const folio = r.folio || ('AH-' + r.id);

    return `
      <div class="side-item">
        <div class="side-icon">🗓️</div>

        <div class="side-info">
          <strong>${escHTML(nombre)}</strong>
          <span>${escHTML(folio)}</span>
        </div>

        <div class="days-badge">${r._days} días</div>
      </div>
    `;
  }).join('');
}

function renderMontoPorPorcentaje(percentAmounts) {
  const percentEl = document.getElementById('percentList');
  if (!percentEl) return;

  const keys = Object.keys(percentAmounts)
    .filter(k => Number(k) > 0)
    .sort((a, b) => Number(a) - Number(b));

  if (!keys.length) {
    percentEl.innerHTML = `<div class="empty-side">Sin datos por porcentaje.</div>`;
    return;
  }

  const max = Math.max(1, ...keys.map(k => percentAmounts[k]));

  percentEl.innerHTML = keys.map(k => {
    const width = Math.max(6, (percentAmounts[k] / max) * 100);

    return `
      <div class="percent-row">
        <span>${k}%</span>

        <div class="progress">
          <span style="width:${width}%"></span>
        </div>

        <strong>${money(percentAmounts[k])}</strong>
      </div>
    `;
  }).join('');
}

function renderActividad(rows) {
  const actEl = document.getElementById('activityList');
  if (!actEl) return;

  rows = Array.isArray(rows) ? rows : [];

  if (!rows.length) {
    actEl.innerHTML = `<div class="empty-side">Sin actividad reciente.</div>`;
    return;
  }

  actEl.innerHTML = rows.slice(0, 3).map(r => {
    const nombre = [r.nombre, r.ap_paterno, r.ap_materno].filter(Boolean).join(' ') || '—';
    const folio = r.folio || ('AH-' + r.id);
    const isCancel = String(r.estado || '').toLowerCase() === 'cancelado';

    return `
      <div class="activity-item ${isCancel ? 'cancel' : ''}">
        <div class="act-icon">${isCancel ? '×' : '+'}</div>

        <div class="side-info">
          <strong>${isCancel ? 'Contrato cancelado' : 'Contrato registrado'}</strong>
          <span>${escHTML(folio)} · ${escHTML(nombre)}</span>
        </div>
      </div>
    `;
  }).join('');
}


/* =====================================================
   TOP 3 PLANES POR MES
===================================================== */
function getTop3PlanesPorMes(rows) {
  const mesesData = Array.from({ length: 12 }, () => ({}));
  const totalPorPlan = {};

  rows = Array.isArray(rows) ? rows : [];

  rows.forEach(r => {
    const porcentajeNum = Number(r.porcentaje || 0);
    if (!porcentajeNum) return;

    const fecha = getFechaRegistro(r);
    const d = parseDate(fecha);

    if (!d) return;

    if (d.getFullYear() !== Number(state.anioGrafica)) return;

    const plan = `${porcentajeNum.toFixed(0)}%`;
    const mes = d.getMonth();

    totalPorPlan[plan] = (totalPorPlan[plan] || 0) + 1;
    mesesData[mes][plan] = (mesesData[mes][plan] || 0) + 1;
  });

  const topPlanes = Object.entries(totalPorPlan)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 3)
    .map(([plan]) => plan);

  const series = topPlanes.map(plan => ({
    label: plan,
    data: mesesData.map(m => m[plan] || 0)
  }));

  return {
    topPlanes,
    series
  };
}


/* =====================================================
   GRÁFICAS
===================================================== */
function renderCharts(rows) {
  if (typeof Chart === 'undefined') {
    console.warn('Chart.js no está cargado.');
    return;
  }

  rows = Array.isArray(rows) ? rows : [];

  const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
  const text = isDark ? '#cbd5e1' : '#334155';
  const grid = isDark ? 'rgba(148,163,184,.14)' : 'rgba(148,163,184,.28)';

  const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  const m = getRowsMetrics(rows, state.total || rows.length);

  renderChartTop3Planes(meses, rows, text, grid);
  renderChartEstados(m, text);
  renderChartContratosMensuales(meses, m, text, grid);
}

function renderChartTop3Planes(meses, rows, text, grid) {
  const canvas = document.getElementById('chartAhorroMensual');
  if (!canvas) return;

  if (chartAhorroMensual) {
    chartAhorroMensual.destroy();
    chartAhorroMensual = null;
  }

  const top3 = getTop3PlanesPorMes(rows);

  const colores = [
    { bg: 'rgba(34,197,94,.30)', border: '#22c55e' },
    { bg: 'rgba(38,139,255,.30)', border: '#268bff' },
    { bg: 'rgba(168,85,247,.30)', border: '#a855f7' }
  ];

  const datasets = top3.series.length
    ? top3.series.map((serie, i) => ({
        label: serie.label,
        data: serie.data,
        backgroundColor: colores[i % colores.length].bg,
        borderColor: colores[i % colores.length].border,
        borderWidth: 1,
        borderRadius: 8,
        barThickness: 18
      }))
    : [{
        label: 'Sin datos',
        data: Array(12).fill(0),
        backgroundColor: 'rgba(148,163,184,.20)',
        borderColor: 'rgba(148,163,184,.35)',
        borderWidth: 1,
        borderRadius: 8,
        barThickness: 18
      }];

  chartAhorroMensual = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: meses,
      datasets
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          display: top3.series.length > 0,
          position: 'top',
          labels: {
            color: text,
            usePointStyle: true,
            boxWidth: 10,
            padding: 16
          }
        },
        tooltip: {
          callbacks: {
            label: ctx => {
              const label = ctx.dataset.label || '';
              const value = Number(ctx.raw || 0);

              return `${label}: ${value} contrato(s)`;
            }
          }
        }
      },
      scales: {
        x: {
          ticks: { color: text },
          grid: { color: grid }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: text,
            stepSize: 1,
            callback: value => Number.isInteger(value) ? value : ''
          },
          grid: { color: grid }
        }
      }
    }
  });
}

function renderChartEstados(m, text) {
  const canvas = document.getElementById('chartEstadosAhorro');
  if (!canvas) return;

  if (chartEstadosAhorro) {
    chartEstadosAhorro.destroy();
    chartEstadosAhorro = null;
  }

  const activo = Number(m?.counts?.activo || 0);
  const finalizado = Number(m?.counts?.finalizado || 0);
  const cancelado = Number(m?.counts?.cancelado || 0);
  const totalEstados = activo + finalizado + cancelado;

  chartEstadosAhorro = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: ['Activo', 'Finalizado', 'Cancelado'],
      datasets: [{
        data: totalEstados > 0 ? [activo, finalizado, cancelado] : [1],
        backgroundColor: totalEstados > 0
          ? ['#22c55e', '#268bff', '#ef4444']
          : ['rgba(148,163,184,.25)'],
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '64%',
      plugins: {
        legend: {
          display: totalEstados > 0,
          position: 'right',
          labels: {
            color: text,
            usePointStyle: true,
            boxWidth: 10,
            padding: 16
          }
        },
        tooltip: {
          callbacks: {
            label: ctx => {
              if (totalEstados <= 0) return 'Sin registros';

              const label = ctx.label || '';
              const value = Number(ctx.raw || 0);
              const percent = totalEstados ? ((value / totalEstados) * 100).toFixed(1) : 0;

              return `${label}: ${value} (${percent}%)`;
            }
          }
        }
      }
    }
  });
}

function renderChartContratosMensuales(meses, m, text, grid) {
  const canvas = document.getElementById('chartContratosMensuales');
  if (!canvas) return;

  if (chartContratosMensuales) {
    chartContratosMensuales.destroy();
    chartContratosMensuales = null;
  }

  chartContratosMensuales = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: meses,
      datasets: [{
        label: 'Contratos',
        data: Array.isArray(m.monthlyCount) ? m.monthlyCount : Array(12).fill(0),
        borderRadius: 8,
        backgroundColor: '#268bff',
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => `${Number(ctx.raw || 0)} contrato(s)`
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
            stepSize: 1,
            callback: value => Number.isInteger(value) ? value : ''
          },
          grid: { color: grid }
        }
      }
    }
  });
}


/* =====================================================
   CARGAR LISTADO
===================================================== */
async function load() {
  const q = (document.getElementById('q')?.value || '').trim();
  const estado = (document.getElementById('estado')?.value || '').trim();
  const porcentaje = (document.getElementById('porcentaje')?.value || '').trim();

  const url = new URL(`${BASE_URL}app/controllers/ahorro/list.php`, location.origin);

  url.searchParams.set('page', state.page);
  url.searchParams.set('per', state.per);

  if (q) url.searchParams.set('q', q);
  if (estado) url.searchParams.set('estado', estado);
  if (porcentaje) url.searchParams.set('porcentaje', porcentaje);

  const tb = document.getElementById('tbody');

  try {
    const res = await fetch(url.toString(), {
      cache: 'no-store',
      credentials: 'same-origin'
    });

    const json = await res.json();

    if (!json.ok) {
      tb.innerHTML = `
        <tr>
          <td colspan="8" class="no-result">
            Error: ${escHTML(json.error || 'No se pudo cargar')}
          </td>
        </tr>
      `;

      actualizarPaneles([], 0);
      return;
    }

    const rows = Array.isArray(json.rows) ? json.rows : [];

    state.total = Number(json.total || rows.length || 0);
    state.rows = rows;

    const anios = Array.isArray(json.anios) && json.anios.length
      ? json.anios
      : getAniosDesdeRows(rows);

    cargarAniosGrafica(anios);

    document.getElementById('page').textContent = String(state.page);

    document.getElementById('info').textContent = `
      Mostrando ${rows.length ? ((state.page - 1) * state.per) + 1 : 0}
      a ${Math.min(state.page * state.per, state.total)}
      de ${state.total} contratos
    `;

    actualizarPaneles(rows, state.total);

    renderTabla(rows, tb);

  } catch (e) {
    console.error(e);

    tb.innerHTML = `
      <tr>
        <td colspan="8" class="no-result">
          No se pudo conectar con el servidor.
        </td>
      </tr>
    `;

    actualizarPaneles([], 0);
  }
}

function renderTabla(rows, tb) {
  if (!tb) return;

  if (!rows.length) {
    tb.innerHTML = `
      <tr>
        <td colspan="8" class="no-result">Sin resultados</td>
      </tr>
    `;
    return;
  }

  tb.innerHTML = rows.map(r => {
    const nombre = [r.nombre, r.ap_paterno, r.ap_materno].filter(Boolean).join(' ') || '—';
    const folio = r.folio || ('AH-' + r.id);

    return `
      <tr>
        <td>${escHTML(folio)}</td>
        <td>${escHTML(nombre)}</td>
        <td>${money(r.monto_semanal)}</td>
        <td>
          <span class="percent-badge">
            ${Number(r.porcentaje || 0).toFixed(0)}%
          </span>
        </td>
        <td>${escHTML(formatDateMX(r.fecha_inicio_ahorro))}</td>
        <td>${escHTML(formatDateMX(r.fecha_devolucion))}</td>
        <td>${badge(r.estado)}</td>
        <td>
          <div class="row-actions">
            <button
              class="icon-btn"
              title="Documentos"
              onclick="openDocsModal(${Number(r.id)}, ${escJS(nombre)}, ${escJS(folio)})">
              📄
            </button>

            <a
              class="icon-btn"
              title="Editar"
              href="${BASE_URL}ahorros.php?id=${encodeURIComponent(r.id)}">
              ✎
            </a>

            <button
              class="icon-btn danger"
              title="Cancelar"
              onclick="delRow(${Number(r.id)})">
              🗑
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}


/* =====================================================
   CANCELAR REGISTRO
===================================================== */
async function delRow(id) {
  if (!confirm('¿Deseas marcar este registro como cancelado?')) return;

  try {
    const res = await fetch(`${BASE_URL}app/controllers/ahorro/delete.php`, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ id })
    });

    const json = await res.json();

    if (!json.ok) {
      alert('No se pudo cancelar: ' + (json.error || 'Error'));
      return;
    }

    load();

  } catch (e) {
    console.error(e);
    alert('No se pudo conectar con el servidor.');
  }
}


/* =====================================================
   EVENTOS
===================================================== */
function bindEvents() {
  const q = document.getElementById('q');
  const estado = document.getElementById('estado');
  const porcentaje = document.getElementById('porcentaje');
  const prev = document.getElementById('prev');
  const next = document.getElementById('next');

  if (q) {
    q.addEventListener('input', () => {
      clearTimeout(window.__qT);

      window.__qT = setTimeout(() => {
        state.page = 1;
        load();
      }, 250);
    });
  }

  if (estado) {
    estado.addEventListener('change', () => {
      state.page = 1;
      load();
    });
  }

  if (porcentaje) {
    porcentaje.addEventListener('change', () => {
      state.page = 1;
      load();
    });
  }

  if (prev) {
    prev.addEventListener('click', () => {
      if (state.page > 1) {
        state.page--;
        load();
      }
    });
  }

  if (next) {
    next.addEventListener('click', () => {
      const maxPage = Math.max(1, Math.ceil(state.total / state.per));

      if (state.page < maxPage) {
        state.page++;
        load();
      }
    });
  }
}


/* =====================================================
   MODAL DOCUMENTOS
===================================================== */
let ahorroSeleccionado = null;

const MAX_MB = 2.5;
const MAX_BYTES = Math.floor(MAX_MB * 1024 * 1024);

function docsBase() {
  return BASE_URL + 'app/controllers/documentos/ahorro/';
}

function URL_DOCS_UPLOAD() {
  return docsBase() + 'docs_upload.php';
}

function URL_DOCS_PRESIGN() {
  return docsBase() + 'docs_presign.php';
}

function URL_DOCS_LIST() {
  return docsBase() + 'docs_list.php';
}

function swErr(msg) {
  if (typeof Swal === 'undefined') {
    alert(msg || 'Error');
    return;
  }

  return Swal.fire({
    icon: 'error',
    title: 'No se pudo subir',
    text: msg || 'Revisa consola / ruta del controlador',
    confirmButtonText: 'OK'
  });
}

function swOk(msg) {
  if (typeof Swal === 'undefined') {
    alert(msg || 'OK');
    return;
  }

  return Swal.fire({
    icon: 'success',
    title: 'Listo',
    text: msg || 'Documento subido correctamente',
    timer: 1600,
    showConfirmButton: false
  });
}

function swLoading(msg = 'Subiendo PDF…') {
  if (typeof Swal === 'undefined') return;

  return Swal.fire({
    title: msg,
    html: 'Por favor espera…',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => Swal.showLoading()
  });
}

let docsIndex = {};

function resetDocsUI() {
  docsIndex = {};

  ['INE', 'DOMICILIO', 'RFC', 'ESTADO_CUENTA', 'CONTRATO'].forEach(t => {
    setDocUI(t, false, null, null);
  });
}

function setDocUI(tipo, ok, docId = null, viewUrl = null) {
  const st = document.getElementById('st-' + tipo);
  const card = document.querySelector(`.doc-card2[data-tipo="${tipo}"]`);
  const viewBtn = card ? card.querySelector('.doc-btn.view') : null;

  if (st) {
    st.textContent = ok ? '✅ Subido' : '⏳ Pendiente';
    st.classList.toggle('ok', !!ok);
  }

  if (viewBtn) {
    viewBtn.disabled = !ok;
  }

  if (ok && docId) {
    docsIndex[tipo] = {
      id: docId,
      url: viewUrl || null
    };
  } else {
    delete docsIndex[tipo];
  }
}

window.openDocsModal = function(id, nombre = '—', folio = '') {
  ahorroSeleccionado = Number(id) || null;

  const info = document.getElementById('docsInfo');

  if (info) {
    info.innerHTML = `
      <div style="font-weight:900;color:rgba(234,240,255,.95);">
        ${escHTML(nombre)}
      </div>

      <div style="font-size:12px;opacity:.75;margin-top:2px;">
        ${folio ? `Folio: <b>${escHTML(folio)}</b> · ` : ''}
        ID: <b>${ahorroSeleccionado ?? '—'}</b>
      </div>
    `;
  }

  const bd = document.getElementById('docsBackdrop');
  const md = document.getElementById('docsModal');

  if (!bd || !md) return;

  bd.style.display = 'block';
  md.style.display = 'block';
  document.body.style.overflow = 'hidden';

  resetDocsUI();
  refreshDocsStatus();
};

window.closeDocsModal = function() {
  const bd = document.getElementById('docsBackdrop');
  const md = document.getElementById('docsModal');

  if (!bd || !md) return;

  bd.style.display = 'none';
  md.style.display = 'none';
  document.body.style.overflow = '';

  ahorroSeleccionado = null;
};

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    closeDocsModal();
  }
});

window.subirDoc = async function(tipo) {
  if (!ahorroSeleccionado) {
    return swErr('No hay registro seleccionado');
  }

  const input = document.createElement('input');
  input.type = 'file';
  input.accept = 'application/pdf';
  input.click();

  input.onchange = async () => {
    const file = input.files && input.files[0];
    if (!file) return;

    if (file.size > MAX_BYTES) {
      return Swal.fire({
        icon: 'warning',
        title: 'Archivo muy pesado',
        html: `Máximo permitido: <b>${MAX_MB} MB</b><br>Tu archivo pesa: <b>${(file.size / 1024 / 1024).toFixed(2)} MB</b>`,
        confirmButtonText: 'OK'
      });
    }

    const st = document.getElementById('st-' + tipo);

    if (st) {
      st.textContent = '⏳ Subiendo…';
      st.classList.remove('ok');
    }

    const fd = new FormData();
    fd.append('solicitud_id', String(ahorroSeleccionado));
    fd.append('tipo_documento', tipo);
    fd.append('archivo', file);

    try {
      swLoading('Subiendo PDF…');

      const res = await fetch(URL_DOCS_UPLOAD(), {
        method: 'POST',
        body: fd,
        credentials: 'same-origin'
      });

      const text = await res.text();

      let json = null;
      try {
        json = JSON.parse(text);
      } catch (e) {}

      if (!json || !json.ok) {
        console.error('Respuesta backend:', text);

        if (typeof Swal !== 'undefined') Swal.close();

        setDocUI(tipo, false, null, null);

        return swErr((json && json.error)
          ? json.error
          : 'Revisa consola / ruta del controlador');
      }

      setDocUI(tipo, true, json.id, json.ruta || json.ruta_archivo || null);

      await refreshDocsStatus();

      if (typeof Swal !== 'undefined') Swal.close();

      await swOk(`✅ Subido ${tipo} (v${json.version || 1})`);

    } catch (e) {
      console.error(e);

      if (typeof Swal !== 'undefined') Swal.close();

      setDocUI(tipo, false, null, null);

      return swErr('Error de red al subir');
    }
  };
};

window.verDoc = async function(tipo) {
  const info = docsIndex[tipo];

  if (!info?.id) {
    return swErr('Aún no hay documento subido.');
  }

  try {
    const url = new URL(URL_DOCS_PRESIGN(), location.origin);
    url.searchParams.set('id', String(info.id));

    swLoading('Generando enlace…');

    const res = await fetch(url.toString(), {
      credentials: 'same-origin',
      cache: 'no-store'
    });

    const text = await res.text();

    let json = null;
    try {
      json = JSON.parse(text);
    } catch (e) {}

    if (typeof Swal !== 'undefined') Swal.close();

    if (!json || !json.ok || !json.url) {
      console.error('presign resp:', text);

      return swErr((json && json.error)
        ? json.error
        : 'No se pudo generar enlace');
    }

    window.open(json.url, '_blank');

  } catch (e) {
    console.error(e);

    if (typeof Swal !== 'undefined') Swal.close();

    return swErr('Error al abrir el documento');
  }
};

async function refreshDocsStatus() {
  if (!ahorroSeleccionado) return;

  try {
    const url = new URL(URL_DOCS_LIST(), location.origin);
    url.searchParams.set('solicitud_id', String(ahorroSeleccionado));
    url.searchParams.set('latest', '1');

    const res = await fetch(url.toString(), {
      credentials: 'same-origin',
      cache: 'no-store'
    });

    const text = await res.text();

    let json = null;
    try {
      json = JSON.parse(text);
    } catch (e) {}

    if (!json || !json.ok) {
      console.warn('docs_list no ok:', text);
      return;
    }

    const rows = Array.isArray(json.rows) ? json.rows : [];

    ['INE', 'DOMICILIO', 'RFC', 'ESTADO_CUENTA', 'CONTRATO'].forEach(t => {
      setDocUI(t, false, null, null);
    });

    for (const r of rows) {
      const t = String(r.tipo_documento || '').toUpperCase();
      if (!t) continue;

      setDocUI(t, true, Number(r.id), String(r.ruta_archivo || ''));
    }

  } catch (e) {
    console.warn(e);
  }
}
</script>
</body>
</html>
