<?php
// /public/index.php
declare(strict_types=1);

// 1) Proteger la página (redirecciona a login si no hay sesión)
require_once __DIR__ . '/app/controllers/auth/require_login.php';

// Nombre del asesor para mostrarlo
$asesor   = $_SESSION['asesor'] ?? [];
$userName = htmlspecialchars((string)($asesor['nombre'] ?? $_SESSION['user_name'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <title>Dashboard de Solicitudes</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- Chart.js para la gráfica -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
/* ================== PALETAS DE COLORES ================== */

/* Fallback: si no hay data-theme, usamos dark por defecto */
:root{
  --bg-page:#050816;
  --bg-surface:#0b1120;
  --bg-card:#020617;
  --border-subtle:rgba(148,163,184,.35);
  --text-main:#e5e7eb;
  --text-muted:#9ca3af;

  --blue:#2563eb;
  --blue-soft:rgba(37,99,235,.18);
  --green:#22c55e;
  --green-2:#16a34a;
  --danger:#ef4444;
  --danger-2:#b91c1c;

  --shadow-soft:0 18px 45px rgba(15,23,42,.65);
  --radius:14px;
}

/* Tema oscuro */
:root[data-theme="dark"]{
  --bg-page:#050816;
  --bg-surface:#0b1120;
  --bg-card:#020617;
  --border-subtle:rgba(148,163,184,.35);
  --text-main:#e5e7eb;
  --text-muted:#9ca3af;

  --blue:#2563eb;
  --blue-soft:rgba(37,99,235,.18);
  --green:#22c55e;
  --green-2:#16a34a;
  --danger:#ef4444;
  --danger-2:#b91c1c;

  --shadow-soft:0 18px 45px rgba(15,23,42,.65);
  --radius:14px;
}

/* Tema claro */
:root[data-theme="light"]{
  --bg-page:#f3f4f6;
  --bg-surface:#ffffff;
  --bg-card:#ffffff;
  --border-subtle:rgba(148,163,184,.45);
  --text-main:#111827;
  --text-muted:#6b7280;

  --blue:#2563eb;
  --blue-soft:rgba(37,99,235,.10);
  --green:#16a34a;
  --green-2:#15803d;
  --danger:#dc2626;
  --danger-2:#b91c1c;

  --shadow-soft:0 10px 25px rgba(15,23,42,.12);
  --radius:14px;
}

/* ================== RESETEO BÁSICO ================== */
*{
  box-sizing:border-box;
  margin:0;
  padding:0;
}

body{
  font-family: system-ui, -apple-system, BlinkMacSystemFont,"Segoe UI",sans-serif;
  min-height:100vh;
  padding:24px 18px 32px;
  background:var(--bg-page);
  color:var(--text-main);
}

/* centrar layout principal */
body > h1,
body > .top-bar,
body > .dashboard-grid,
body > .dashboard-grid--bottom,
body > table{
  max-width:1120px;
  margin-left:auto;
  margin-right:auto;
}

h1{
  margin:4px 0 16px;
  text-align:left;
  font-size:1.6rem;
  letter-spacing:.04em;
  display:flex;
  align-items:center;
  gap:8px;
}
h1 span.icon{
  font-size:1.5rem;
}

/* ================== BARRA SUPERIOR ================== */
.top-bar{
  display:flex;
  gap:10px;
  justify-content:flex-end;
  align-items:center;
  margin:6px 0 20px;
}
.top-bar-left{
  margin-right:auto;
  font-size:.82rem;
  color:var(--text-muted);
}

/* ================== BOTONES ================== */
.btn{
  padding:9px 16px;
  border-radius:999px;
  border:1px solid transparent;
  font-weight:600;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:6px;
  font-size:.86rem;
  transition:
    background .15s ease,
    border-color .15s ease,
    box-shadow .15s ease,
    transform .1s ease;
  box-shadow:0 10px 30px rgba(15,23,42,.7);
}
.btn:active{
  transform:translateY(1px);
  box-shadow:none;
}
.btn-primary{
  background:linear-gradient(135deg,var(--green),var(--green-2));
  color:#ecfdf5;
  border-color:rgba(34,197,94,.4);
}
.btn-primary:hover{
  filter:brightness(1.05);
}

/* Outline neutro, que funcione en claro y oscuro */
.btn-outline{
  background:var(--bg-surface);
  color:var(--blue);
  border-color:var(--border-subtle);
}
.btn-outline:hover{
  filter:brightness(0.97);
}

/* Cerrar sesión en rojo */
.btn-logout{
  background:rgba(239,68,68,.08);
  color:var(--danger);
  border-color:rgba(248,113,113,.6);
}
.btn-logout:hover{
  background:rgba(239,68,68,.16);
}

.btn-secondary{
  background:rgba(37,99,235,.12);
  color:#bfdbfe;
  border-color:rgba(37,99,235,.55);
}
.btn-secondary:hover{
  background:rgba(37,99,235,.22);
}

/* ================== TARJETAS (KPIs) ================== */
.dashboard-grid{
  max-width:1120px;
  margin:0 auto 16px;
  display:grid;
  grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
  gap:14px;
}
.metric-card{
  background:var(--bg-surface);
  border-radius:var(--radius);
  padding:14px 16px 16px;
  border:1px solid var(--border-subtle);
  box-shadow:var(--shadow-soft);
  position:relative;
}
.metric-label{
  font-size:.78rem;
  text-transform:uppercase;
  letter-spacing:.08em;
  color:var(--text-muted);
  margin-bottom:4px;
}
.metric-value{
  font-size:1.6rem;
  font-weight:700;
}
.metric-sub{
  margin-top:4px;
  font-size:.8rem;
  color:var(--text-muted);
}

/* ================== GRAFICA + ÚLTIMOS REGISTROS ================== */
.dashboard-grid--bottom{
  max-width:1120px;
  margin:0 auto 22px;
  display:grid;
  grid-template-columns:minmax(0,2.2fr) minmax(0,1.1fr);
  gap:16px;
  align-items:stretch;
}
.chart-card,
.recent-card{
  background:var(--bg-surface);
  border-radius:var(--radius);
  border:1px solid var(--border-subtle);
  box-shadow:var(--shadow-soft);
  padding:14px 16px 16px;
}
.chart-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:6px;
}
.chart-title{
  font-size:.95rem;
  font-weight:600;
}
.chart-sub{
  font-size:.75rem;
  color:var(--text-muted);
}
.chart-card canvas{
  width:100%;
  height:220px;
}
.chart-empty{
  margin-top:30px;
  text-align:center;
  font-size:.8rem;
  color:var(--text-muted);
}

.recent-title{
  font-size:.9rem;
  font-weight:600;
  margin-bottom:2px;
}
.recent-sub{
  font-size:.75rem;
  color:var(--text-muted);
  margin-bottom:10px;
}
.recent-list{
  list-style:none;
  margin:0;
  padding:0;
}
.recent-item{
  padding:8px 0;
  border-bottom:1px solid rgba(30,64,175,.4);
  font-size:.8rem;
}
.recent-item:last-child{
  border-bottom:none;
}
.recent-main{
  display:flex;
  flex-direction:column;
}
.recent-folio{
  font-weight:600;
  color:#bfdbfe;
}
.recent-name{
  color:var(--text-main);
}
.recent-meta{
  font-size:.74rem;
  color:var(--text-muted);
  margin-top:2px;
}
.recent-empty{
  text-align:center;
  font-size:.8rem;
  color:var(--text-muted);
  padding:12px 0;
}

/* ================== TABLA ================== */
table{
  width:100%;
  border-collapse:collapse;
  background:var(--bg-surface);
  border-radius:var(--radius);
  overflow:hidden;
  box-shadow:var(--shadow-soft);
  font-size:.88rem;
}
thead{
  background:linear-gradient(90deg,#1d4ed8,#0ea5e9);
}
th,td{
  padding:12px 16px;
  border-bottom:1px solid rgba(30,64,175,.45);
}
th{
  text-align:left;
  font-size:.78rem;
  text-transform:uppercase;
  letter-spacing:.09em;
  color:#e0ecff;
}

/* Filas según tema */
:root[data-theme="dark"] tbody tr:nth-child(odd){
  background:rgba(15,23,42,.9);
}
:root[data-theme="dark"] tbody tr:nth-child(even){
  background:rgba(15,23,42,.96);
}
:root[data-theme="dark"] tbody tr:hover{
  background:rgba(37,99,235,.22);
}

:root[data-theme="light"] tbody tr:nth-child(odd){
  background:#ffffff;
}
:root[data-theme="light"] tbody tr:nth-child(even){
  background:#f3f4f6;
}
:root[data-theme="light"] tbody tr:hover{
  background:#e5f0ff;
}

.no-result{
  text-align:center;
  padding:28px 18px 30px;
  font-style:italic;
  color:var(--text-muted);
  font-size:.86rem;
}
.no-result span{
  display:block;
  margin-top:4px;
  font-size:.78rem;
  opacity:.9;
}

/* ================== ACCIONES EN FILA ================== */
.acciones{
  white-space:nowrap;
  display:flex;
  justify-content:flex-end;
  gap:6px;
}
.acciones .btn-sm{
  padding:6px 10px;
  font-size:.78rem;
  border-radius:999px;
  border:none;
  cursor:pointer;
  display:inline-flex;
  align-items:center;
  gap:4px;
  box-shadow:0 8px 20px rgba(15,23,42,.7);
  color:#f9fafb;
}
.btn-blue{
  background:var(--blue);
}
.btn-blue:hover{
  filter:brightness(1.05);
}
.btn-red{
  background:var(--danger-2);
}
.btn-red:hover{
  filter:brightness(0.9);
}
.btn-doc{
  background:#7c3aed;
}
.btn-doc:hover{
  background:#6d28d9;
}

/* ================== MODALES (DOCUMENTOS / PREVIEW / HERRAMIENTAS) ================== */
.modal{
  position:fixed;
  inset:0;
  background:rgba(15,23,42,.78);
  display:none;
  align-items:center;
  justify-content:center;
  padding:20px;
  z-index:50;
}
.modal.show{
  display:flex;
}
.dialog{
  width:100%;
  max-width:980px;
  background:var(--bg-surface);
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 24px 80px rgba(0,0,0,.85);
  border:1px solid var(--border-subtle);
}
.dialog header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  background:linear-gradient(90deg,#1d4ed8,#0ea5e9);
  color:#eff6ff;
  padding:14px 18px;
}
.dialog header h3{
  margin:0;
  font-size:.98rem;
  display:flex;
  align-items:center;
  gap:8px;
}
.dialog header .close{
  background:transparent;
  color:inherit;
  border:none;
  cursor:pointer;
  font-size:1.2rem;
  opacity:.9;
}
.dialog header .close:hover{
  opacity:1;
}
.dialog .content{
  padding:18px 18px 10px;
}
.foot{
  padding:10px 18px 18px;
  display:flex;
  justify-content:flex-end;
  gap:8px;
}
.dialog-lg{
  max-width:1100px;
}

/* Tarjetas de documentos / herramientas */
.doc-grid,
.docs-grid,
#toolsGrid{
  display:grid;
  gap:12px;
  grid-template-columns:repeat(auto-fill,minmax(230px,1fr));
}
.doc-card{
  background:var(--bg-card);
  border:1px solid var(--border-subtle);
  border-radius:14px;
  padding:12px 12px 11px;
  display:flex;
  gap:10px;
  align-items:flex-start;
  box-shadow:var(--shadow-soft);
}
.doc-icon{
  width:40px;
  height:40px;
  border-radius:12px;
  display:grid;
  place-items:center;
  font-size:20px;
  color:#eff6ff;
  background:rgba(37,99,235,.6);
  box-shadow:0 12px 26px rgba(37,99,235,.5);
  flex:0 0 40px;
}
.doc-body{
  flex:1;
  min-width:0;
}
.doc-title{
  margin:0;
  font-size:.9rem;
  font-weight:600;
}
.doc-meta{
  color:var(--text-muted);
  font-size:.75rem;
  margin:4px 0 8px;
}
.doc-actions{
  display:flex;
  gap:6px;
  flex-wrap:wrap;
}


.badge.completed .dot{
  background:#22c55e;
}

.badge.signed .dot{
  background:#22c55e;
}

.badge.missing .dot{
  background:#ef4444;
}

.doc-note{
  margin-top:6px;
  font-size:.72rem;
  color:var(--text-muted);
  line-height:1.25;
}

.doc-note.warning{
  color:#facc15;
}

.doc-note.ok{
  color:#86efac;
}

.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  border-radius:999px;
  padding:3px 8px;
  font-size:.72rem;
  font-weight:600;
  background:rgba(59,130,246,.1);
  color:#bfdbfe;
}
.badge .dot{
  width:8px;
  height:8px;
  border-radius:50%;
  background:#22c55e;
}
.badge.pending .dot{
  background:#eab308;
}
.badge.missing .dot{
  background:#ef4444;
}

.btn-ghost{
  background:transparent;
  border:1px solid rgba(148,163,184,.45);
  color:var(--text-main);
  padding:6px 9px;
  border-radius:999px;
  font-size:.78rem;
  cursor:pointer;
}
:root[data-theme="dark"] .btn-ghost:hover{
  background:rgba(15,23,42,.9);
}
:root[data-theme="light"] .btn-ghost:hover{
  background:#e5e7eb;
}

.hidden-input{
  display:none;
}

/* ================== RESPONSIVE ================== */
@media (max-width:900px){
  .dashboard-grid--bottom{
    grid-template-columns:1fr;
  }
}
@media (max-width:768px){
  body{
    padding:18px 10px 24px;
  }
  .top-bar{
    flex-wrap:wrap;
    justify-content:flex-start;
  }
  th,td{
    padding:10px 10px;
  }
}

/* Mensaje de gráfica vacía */
.empty-chart-msg{
  margin-top:32px;
  text-align:center;
  font-size:.82rem;
  color:var(--text-muted);
  display:none;
}

/* ================== NAV HORIZONTAL (ÚNICO) ================== */
.top-nav{
  max-width:1120px;
  margin:10px auto 18px;
  padding:10px 12px;
  border-radius:18px;
  border:1px solid var(--border-subtle);
  background:
    radial-gradient(1200px 220px at 20% -10%, rgba(37,99,235,.25), transparent 60%),
    radial-gradient(900px 220px at 80% 0%, rgba(34,197,94,.18), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,.08), rgba(255,255,255,.02));
  box-shadow:var(--shadow-soft);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  display:flex;
  align-items:center;
  gap:10px;
}

/* sección saludo */
.top-nav .nav-left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:220px;
}
.nav-badge{
  width:40px;height:40px;
  border-radius:14px;
  display:grid;place-items:center;
  background:rgba(37,99,235,.22);
  border:1px solid rgba(37,99,235,.35);
  box-shadow:0 12px 26px rgba(37,99,235,.28);
  flex:0 0 40px;
}
.nav-badge span{ font-size:18px; }
.nav-hello{
  display:flex;
  flex-direction:column;
  line-height:1.1;
  min-width:0;
}
.nav-hello small{
  color:var(--text-muted);
  font-size:.72rem;
}
.nav-hello strong{
  font-size:.92rem;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* área botones */
.top-nav .nav-actions{
  margin-left:auto;
  display:flex;
  align-items:center;
  gap:8px;
  flex-wrap:nowrap;
}

/* agrupador para darle sensación de “dock” */
.nav-actions{
  padding:6px;
  border-radius:999px;
  border:1px solid rgba(148,163,184,.25);
  background:rgba(2,6,23,.22);
}
:root[data-theme="light"] .nav-actions{
  background:rgba(255,255,255,.55);
}

/* botones dentro del nav: micro-afinación sin romper tu .btn */
.top-nav .btn{
  box-shadow:none;                 /* más “limpio” dentro del nav */
  border-color:rgba(148,163,184,.35);
}
.top-nav .btn-outline{
  background:transparent;
}
.top-nav .btn-outline:hover{
  background:rgba(37,99,235,.12);
}
:root[data-theme="light"] .top-nav .btn-outline:hover{
  background:rgba(37,99,235,.08);
}

/* botón de tema como “toggle pill” */
#btnTema{
  position:relative;
  overflow:hidden;
}
#btnTema::after{
  content:'';
  position:absolute;
  inset:-2px;
  background:linear-gradient(90deg, rgba(37,99,235,.22), rgba(34,197,94,.18));
  opacity:0;
  transition:opacity .18s ease;
}
#btnTema:hover::after{ opacity:1; }
#btnTema span{ position:relative; z-index:1; }

/* Responsive: en móvil se vuelve scroll horizontal elegante */
@media (max-width:900px){
  .top-nav{
    gap:10px;
    align-items:stretch;
  }
  .top-nav .nav-left{
    min-width:200px;
  }
  .top-nav .nav-actions{
    overflow:auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
    padding:6px;
  }
  .top-nav .nav-actions::-webkit-scrollbar{ height:8px; }
  .top-nav .nav-actions::-webkit-scrollbar-thumb{
    background:rgba(148,163,184,.35);
    border-radius:999px;
  }
}

/* En pantallas muy pequeñas, compacta el saludo */
@media (max-width:520px){
  .nav-badge{ display:none; }
  .top-nav{ padding:10px; }
  .top-nav .nav-left{ min-width:0; }
}

/* ================== SIDEBAR / LAYOUT (NUEVO) ================== */
.app-shell{
  max-width:1400px;
  margin:0 auto;
  display:grid;
  grid-template-columns: 280px minmax(0, 1fr);
  gap:16px;
  align-items:start;
}

/* Área principal: hacemos que tu contenido siga centrado */
.main-area{
  min-width:0;
}

/* Ajuste importante:
   tu CSS centraba con "body > ..." pero ahora ya no son hijos directos del body,
   así que aquí volvemos a centrarlos dentro de .main-area */
.main-area > h1,
.main-area > .top-bar,
.main-area > .dashboard-grid,
.main-area > .dashboard-grid--bottom,
.main-area > table{
  max-width:1120px;
  margin-left:auto;
  margin-right:auto;
}

/* Sidebar look similar a top-nav (glass + gradientes) */
.side-nav{
  position:sticky;
  top:18px;
  height:calc(58vh - 36px);
  border-radius:18px;
  border:1px solid var(--border-subtle);
  background:
    radial-gradient(900px 220px at 25% -10%, rgba(37,99,235,.22), transparent 60%),
    radial-gradient(700px 220px at 75% 0%, rgba(34,197,94,.14), transparent 55%),
    linear-gradient(180deg, rgba(255,255,255,.07), rgba(255,255,255,.02));
  box-shadow:var(--shadow-soft);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  overflow:hidden;
  display:flex;
  flex-direction:column;
}

/* Header sidebar */
.side-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:12px 12px 10px;
  border-bottom:1px solid rgba(148,163,184,.22);
}
.side-brand{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}
.side-logo{
  width:40px;height:40px;
  border-radius:14px;
  display:grid;place-items:center;
  background:rgba(37,99,235,.22);
  border:1px solid rgba(37,99,235,.35);
  box-shadow:0 12px 26px rgba(37,99,235,.28);
  flex:0 0 40px;
}
.side-title{
  display:flex;
  flex-direction:column;
  line-height:1.1;
  min-width:0;
}
.side-title strong{
  font-size:.92rem;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.side-title small{
  color:var(--text-muted);
  font-size:.72rem;
}

.side-close{
  display:none; /* se activa en móvil */
  border:1px solid rgba(148,163,184,.35);
  background:transparent;
  color:var(--text-main);
  width:36px;height:36px;
  border-radius:12px;
  cursor:pointer;
}
:root[data-theme="dark"] .side-close:hover{ background:rgba(15,23,42,.65); }
:root[data-theme="light"] .side-close:hover{ background:#e5e7eb; }

/* Links */
.side-links{
  padding:10px;
  display:flex;
  flex-direction:column;
  gap:8px;
  overflow:auto;
}
.side-sep{
  height:1px;
  background:rgba(148,163,184,.22);
  margin:6px 0;
}

.side-link{
  width:100%;
  display:flex;
  align-items:center;
  gap:10px;
  padding:10px 12px;
  border-radius:14px;
  border:1px solid rgba(148,163,184,.22);
  background:rgba(2,6,23,.18);
  color:var(--text-main);
  cursor:pointer;
  text-align:left;
  transition: transform .12s ease, background .15s ease, border-color .15s ease;
}
:root[data-theme="light"] .side-link{ background:rgba(255,255,255,.55); }

.side-link:hover{
  background:rgba(37,99,235,.12);
  border-color:rgba(37,99,235,.30);
  transform:translateY(-1px);
}
.side-link .ico{ width:22px; display:grid; place-items:center; }
.side-link .txt{ font-weight:700; font-size:.86rem; }

.side-link.is-active{
  background:rgba(37,99,235,.16);
  border-color:rgba(37,99,235,.38);
  box-shadow:0 14px 35px rgba(37,99,235,.18);
}

.side-link.danger{
  background:rgba(239,68,68,.08);
  border-color:rgba(248,113,113,.35);
  color:var(--danger);
}
.side-link.danger:hover{
  background:rgba(239,68,68,.14);
}

/* Footer sidebar */
.side-foot{
  margin-top:auto;
  padding:12px;
  border-top:1px solid rgba(148,163,184,.22);
  display:flex;
  flex-direction:column;
  gap:10px;
}
.w-100{ width:100%; }
.side-muted{
  color:var(--text-muted);
  font-size:.74rem;
}

/* Backdrop (solo móvil) */
.side-backdrop{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(15,23,42,.70);
  z-index:60;
}

/* Botón hamburguesa dentro del top-nav (solo móvil) */
.hamburger{
  display:none;
  border:1px solid rgba(148,163,184,.35);
  background:transparent;
  color:var(--text-main);
  width:40px;height:40px;
  border-radius:14px;
  cursor:pointer;
}
:root[data-theme="dark"] .hamburger:hover{ background:rgba(15,23,42,.65); }
:root[data-theme="light"] .hamburger:hover{ background:#e5e7eb; }

/* ================== RESPONSIVE: Sidebar como drawer ================== */
@media (max-width:1100px){
  .app-shell{
    grid-template-columns: 1fr;
  }
  .hamburger{ display:inline-grid; place-items:center; }

  .side-nav{
    position:fixed;
    top:14px;
    left:14px;
    height:calc(100vh - 28px);
    width:min(320px, calc(100vw - 28px));
    z-index:70;
    transform:translateX(-110%);
    transition: transform .18s ease;
  }
  .side-nav.open{
    transform:translateX(0);
  }
  .side-close{ display:inline-grid; place-items:center; }
  .side-backdrop.show{ display:block; }
}
  </style>

  <style>
.is-disabled{
  opacity: .55;
  filter: grayscale(.15);
  cursor: not-allowed !important;
  pointer-events: auto;
  box-shadow: none !important;
  transform: none !important;
}

.is-disabled:hover{
  transform: none !important;
  box-shadow: none !important;
}

.is-disabled.btn-blue,
.is-disabled.btn-doc,
.is-disabled.btn-red{
  background: #475569 !important;
  border-color: #475569 !important;
  color: #cbd5e1 !important;
}

.estado-cancelado{
  margin-top: 4px;
  font-size: .78rem;
  font-weight: 600;
  color: #fca5a5;
}

.swal-dark{
  border: 1px solid rgba(148,163,184,.22);
  border-radius: 18px;
  box-shadow: 0 20px 50px rgba(0,0,0,.45);
}

.swal-title{
  font-weight: 700;
}

.swal-text{
  color: #cbd5e1 !important;
}
</style>
</head>
<body>

  <div class="app-shell">

    <!-- SIDEBAR -->
    <aside class="side-nav" id="sideNav" aria-label="Menú lateral">
      <div class="side-head">
        <div class="side-brand">
          <div class="side-logo">📄</div>
          <div class="side-title">
            <strong>Solicitudes</strong>
            <small>Menú</small>
          </div>
        </div>

        <button class="side-close" onclick="toggleSidebar(false)" aria-label="Cerrar menú">✕</button>
      </div>

      <nav class="side-links">
        <button class="side-link is-active" type="button" onclick="window.location.href=BASE_URL + 'index.php'">
          <span class="ico">🏠</span>
          <span class="txt">Dashboard</span>
        </button>



        <button class="side-link" type="button" onclick="openTools()">
          <span class="ico">🧰</span>
          <span class="txt">Herramientas</span>
        </button>

        <button class="side-link" type="button" onclick="crearProspecto()">
          <span class="ico">📝</span>
          <span class="txt">Crear financiamiento.</span>
        </button>

        <button class="side-link" type="button" onclick="openPlantillas()">
          <span class="ico">⬇️</span>
          <span class="txt">Plantillas</span>
        </button>


        <button class="side-link" type="button" onclick="irAlPanel()">
          <span class="ico">⬅️</span>
          <span class="txt">Regresar al panel</span>
        </button>

        <button class="side-link" id="btnTema2" type="button" onclick="toggleTheme()">
          <span class="txt">🌙 Modo oscuro</span>
        </button>
        <div class="side-sep"></div>

        <button class="side-link danger" type="button" onclick="cerrarSesion()">
          <span class="ico">⎋</span>
          <span class="txt">Cerrar sesión</span>
        </button>
      </nav>

      <div class="side-foot">

      </div>
    </aside>

    <!-- BACKDROP (móvil) -->
    <div class="side-backdrop" id="sideBackdrop" onclick="toggleSidebar(false)"></div>

    <!-- MAIN -->
    <main class="main-area" id="mainArea">

      <h1><span class="icon">📋</span> Solicitudes Iniciadas</h1>

      <div class="top-nav">
        <div class="nav-left">

          <!-- Hamburguesa (solo móvil) -->
          <button class="hamburger" type="button" onclick="toggleSidebar(true)" aria-label="Abrir menú">☰</button>

          <div class="nav-badge"><span>📄</span></div>
          <div class="nav-hello">
            <small>Hola,</small>
            <strong><?php echo $userName; ?></strong>
          </div>
        </div>
      </div>

      <!-- KPIs -->
      <section class="dashboard-grid">
        <article class="metric-card">
          <div class="metric-label">Total</div>
          <div class="metric-value" id="kpi-total">0</div>
          <div class="metric-sub">Solicitudes registradas en el sistema</div>
        </article>
        <article class="metric-card">
          <div class="metric-label">Últimos 30 días</div>
          <div class="metric-value" id="kpi-30d">0</div>
          <div class="metric-sub">Solicitudes creadas en el último mes</div>
        </article>
      </section>

      <!-- Gráfica + últimos registros -->
      <section class="dashboard-grid--bottom">
        <article class="chart-card">
          <div class="chart-header">
            <div>
              <div class="chart-title">Solicitudes por mes</div>
              <div class="chart-sub">Últimos 6 meses (según fecha)</div>
            </div>
          </div>
          <canvas id="chartSolicitudes"></canvas>
          <div id="chartEmptyMessage" class="empty-chart-msg">
            Aún no hay datos suficientes para mostrar la gráfica.
          </div>
        </article>

        <article class="recent-card">
          <div class="recent-title">Últimos registros</div>
          <div class="recent-sub">Los 5 folios más recientes</div>
          <ul id="recentList" class="recent-list">
            <!-- Se llena por JS -->
          </ul>
        </article>
      </section>

      <!-- Tabla de solicitudes -->
      <table>
        <thead>
          <tr>
            <th>Folio</th>
            <th>Nombre completo</th>
            <th class="acciones">Acciones</th>
          </tr>
        </thead>
        <tbody id="tabla-solicitudes">
          <!-- Filas generadas por JS -->
        </tbody>
      </table>

    </main>
  </div>

  <!-- Modal Documentos -->
  <div class="modal" id="modalDocs" aria-hidden="true">
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="ttlDocs">
      <header>
        <h3 id="ttlDocs">📄 Documentos — Folio <span id="folioActual">–</span></h3>
        <button class="close" onclick="closeDocs()">×</button>
      </header>
      <div class="content">
        <div id="docsGrid" class="docs-grid"></div>
      </div>
      <div class="foot">
        <button class="btn btn-outline" onclick="closeDocs()">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- Modal Vista Previa PDF -->
  <div class="modal" id="modalPreview" aria-hidden="true">
    <div class="dialog dialog-lg" role="dialog" aria-modal="true" aria-labelledby="ttlPreview">
      <header>
        <h3 id="ttlPreview">👁️ Vista previa</h3>
        <button class="close" onclick="closePreview()">×</button>
      </header>
      <div class="content">
        <iframe id="pdfFrame" style="width:100%; height:70vh; border:0;" title="Vista previa PDF"></iframe>
      </div>
      <div class="foot">
        <a id="previewDownload" class="btn btn-outline" href="#" target="_blank" rel="noopener">⬇️ Abrir/Descargar</a>
        <button class="btn btn-primary" onclick="closePreview()">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- Modal Herramientas -->
  <div class="modal" id="modalTools" aria-hidden="true">
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="ttlTools">
      <header>
        <h3 id="ttlTools">🧰 Herramientas</h3>
        <button class="close" onclick="closeTools()">×</button>
      </header>
      <div class="content">
        <div id="toolsGrid" class="docs-grid"></div>
      </div>
    </div>
  </div>
<!-- Modal Plantillas -->
<div class="modal" id="modalPlantillas" aria-hidden="true">
  <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="ttlPlantillas">
    <header>
      <h3 id="ttlPlantillas">⬇️ Plantillas</h3>
      <button class="close" onclick="closePlantillas()">×</button>
    </header>
    <div class="content">
      <div id="plantillasGrid" class="docs-grid"></div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
/* ================== SIDEBAR TOGGLE (NUEVO) ================== */
function toggleSidebar(open){
  const side = document.getElementById('sideNav');
  const bd   = document.getElementById('sideBackdrop');
  if(!side || !bd) return;

  const willOpen = (open === true);
  side.classList.toggle('open', willOpen);
  bd.classList.toggle('show', willOpen);
  document.body.style.overflow = willOpen ? 'hidden' : '';
}

window.addEventListener('resize', () => {
  if (window.innerWidth > 1100) toggleSidebar(false);
});

/* ================== CONFIG BASE_URL (local + producción) ================== */
const BASE_URL = (() => {
  const { origin, pathname } = window.location;

  // Local XAMPP
  if (pathname.includes('/hp/public/')) {
    return `${origin}/hp/public/`;
  }

  // Producción Render/Railway
  return `${origin}/`;
})();

// URLs internas dinámicas
const INDEX_URL = `${BASE_URL}index.php`;

const CONTRATO_ABS = `${BASE_URL}contrato/contrato.php`;
const CONTRATO_ORDINARIO_URL = `${BASE_URL}contrato/contrato2.php`;

const URL_ME = `${BASE_URL}app/controllers/auth/me.php`;
const URL_LOGOUT = `${BASE_URL}app/controllers/auth/logout.php`;

const CARATULA_ORDINARIO_URL = `${BASE_URL}Caratulas/caratula1.html`;
const CARATULA_TOROS2_URL = `${BASE_URL}caratula.html`;
const TABLA_ORDINARIA_URL = `${BASE_URL}tablas/tabla.html`;

/* ================== CATÁLOGOS DOCUMENTOS ================== */
const DOCS = [
  { key:'pagare',               label:'Pagaré',                        icon:'📝' },
  { key:'contrato',             label:'Contrato',                      icon:'📄' },
  { key:'aviso_privacidad',     label:'Aviso de privacidad',           icon:'🔐' },
  { key:'tabla_amortizacion',   label:'Tabla de amortización',         icon:'📊' },
  { key:'poliza_seguro',        label:'Póliza de seguro',              icon:'🛡️' },
  { key:'ine',                  label:'INE',                           icon:'🪪' },
  { key:'verif_domicilio',      label:'Verificación de domicilio',     icon:'🏠' },
  { key:'verif_referencias',    label:'Verificación de referencias',   icon:'👥' },
  { key:'solicitud_manual',     label:'Solicitud de crédito (manual)', icon:'🗂️' },
  { key:'caratula',             label:'Carátula',                      icon:'📘' },
  { key:'retroactivo_40',       label:'Retroactivo 40',                icon:'⏪' },
  { key:'autorizacion_credito', label:'Autorización de crédito',       icon:'✅' },
];

const TIPO_MAP = {
  pagare:'pagare',
  contrato:'contrato',
  aviso_privacidad:'aviso_privacidad',
  tabla_amortizacion:'tabla_amortizacion',
  poliza_seguro:'poliza_seguro',
  ine:'ine',
  verif_domicilio:'verif_domicilio',
  verif_referencias:'verif_referencias',
  solicitud_manual:'solicitud_manual',
  caratula:'caratula',
  retroactivo_40:'retroactivo_40',
  autorizacion_credito:'autorizacion_credito',
};

const BASE_DOC_KEYS = [
  'pagare',
  'contrato',
  'aviso_privacidad',
  'tabla_amortizacion',
  'poliza_seguro',
  'ine',
  'verif_domicilio',
  'verif_referencias',
  'solicitud_manual',
  'caratula',
  'autorizacion_credito'
];

const EXTRA_DOCS_BY_MODALIDAD = {
  P10: [],
  P10_ORD: [],
  P40: ['retroactivo_40'],
  P40_ORD: ['retroactivo_40']
};

/* ================== ESTADO GLOBAL ================== */
let currentSolicitud = null;
let currentModalidad = null;
let currentTipoCredito = '';
let chartSolicitudesInstance = null;

/* ===== OPTIMIZACIÓN / CACHÉ ===== */
let SOLICITUDES_CACHE = [];
let SOLICITUDES_MAP = new Map();
let DOCS_CACHE = new Map();
let MODALIDAD_CACHE = new Map();

/* ================== UTIL ================== */
async function safeJSON(res){
  const txt = await res.text();

  try {
    return JSON.parse(txt);
  } catch(e) {
    console.error('Respuesta no JSON:\n', txt);
    throw new Error('Respuesta no válida del servidor');
  }
}

function byKey(key){
  return DOCS.find(d => d.key === key);
}

/* 
  Estados posibles:
  - ok: true              => PDF firmado/subido
  - completo: true        => Documento generado/llenado
  - ok false y completo false => Falta
*/
function buildStatusMapFromDocs(docs, completados = {}){
  const ultimoPorTipo = {};

  docs.forEach(d => {
    const key = Object.keys(TIPO_MAP).find(k => TIPO_MAP[k] === d.tipo_documento) || d.tipo_documento;
    if (!key) return;

    const prev = ultimoPorTipo[key];

    if (!prev || new Date(d.fecha_subida || 0) >= new Date(prev.fecha_subida || 0)) {
      ultimoPorTipo[key] = d;
    }
  });

  const status = {};

  DOCS.forEach(doc => {
    const reg = ultimoPorTipo[doc.key];

    const tienePDF = !!reg;
    const estaCompleto = !!completados[doc.key] || tienePDF;

    status[doc.key] = {
      ok: tienePDF,
      completo: estaCompleto,
      url: reg?.download_url || null,
      storage_key: reg?.storage_key || null
    };
  });

  return status;
}

/* 
  Este endpoint lo vas a crear después:
  /app/controllers/documentos/estado_generados.php

  Si todavía no existe, NO rompe el sistema.
  Simplemente devuelve {}.
*/
async function obtenerDocumentosCompletados(solicitud_id){
  try{
    const res = await fetch(
      `${BASE_URL}app/controllers/documentos/estado_generados.php?solicitud_id=${encodeURIComponent(solicitud_id)}&t=${Date.now()}`,
      {
        cache: 'no-store',
        credentials: 'same-origin'
      }
    );

    if (!res.ok) {
      console.warn('estado_generados.php no disponible todavía.');
      return {};
    }

    const json = await safeJSON(res);

    if (!json.success) return {};

    return json.completados || {};
  }catch(e){
    console.warn('No se pudo obtener el estado de documentos generados:', e);
    return {};
  }
}

function allowedDocKeys(){
  const extras = EXTRA_DOCS_BY_MODALIDAD[currentModalidad] || [];
  return [...BASE_DOC_KEYS, ...extras];
}

function esOrdinario(mod){
  return ['P10_ORD', 'P40_ORD'].includes(String(mod || '').toUpperCase());
}

/* ================== PARSEO DE FECHAS ================== */
function parseAnyDate(raw){
  if (!raw) return null;

  let s = String(raw).trim();
  if (!s) return null;

  if (/^\d+$/.test(s)) {
    let n = Number(s);
    if (n < 1e12) n *= 1000;

    const d = new Date(n);
    return isNaN(d.getTime()) ? null : d;
  }

  let m = s.match(/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?$/);

  if (m){
    const dd = Number(m[1]);
    const mm = Number(m[2]);
    const yy = Number(m[3]);
    const hh = Number(m[4] || 0);
    const mi = Number(m[5] || 0);
    const ss = Number(m[6] || 0);

    const d = new Date(yy, mm - 1, dd, hh, mi, ss);
    return isNaN(d.getTime()) ? null : d;
  }

  m = s.match(/^(\d{4})[\/\-](\d{2})[\/\-](\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?$/);

  if (m){
    const yy = Number(m[1]);
    const mm = Number(m[2]);
    const dd = Number(m[3]);
    const hh = Number(m[4] || 0);
    const mi = Number(m[5] || 0);
    const ss = Number(m[6] || 0);

    const d = new Date(yy, mm - 1, dd, hh, mi, ss);
    return isNaN(d.getTime()) ? null : d;
  }

  const d = new Date(s.replace(' ', 'T'));
  return isNaN(d.getTime()) ? null : d;
}

function getRowDate(r){
  const raw =
    r.fecha_registro ||
    r.fecha_solicitud ||
    r.fecha ||
    r.created_at ||
    r.fecha_creacion ||
    null;

  return parseAnyDate(raw);
}

/* ================== DASHBOARD ================== */
function actualizarDashboard(rows){
  const total = rows.length;
  const kpiTotal = document.getElementById('kpi-total');

  if (kpiTotal) kpiTotal.textContent = total;

  const ahora = new Date();

  const start = new Date(ahora);
  start.setHours(0,0,0,0);
  start.setDate(start.getDate() - 29);

  const end = new Date(ahora);
  end.setHours(23,59,59,999);

  let cuenta30 = 0;
  const conFecha = [];

  rows.forEach(r => {
    const d = getRowDate(r);
    if (!d) return;

    conFecha.push({ row:r, date:d });

    if (d >= start && d <= end) cuenta30++;
  });

  const el30 = document.getElementById('kpi-30d');
  if (el30) el30.textContent = cuenta30;

  construirGraficaMensual(rows);
  construirRecientes(conFecha.length ? conFecha : rows.map(r => ({row:r, date:null})));
}

function construirRecientes(lista){
  const ul = document.getElementById('recentList');
  if (!ul) return;

  ul.innerHTML = '';

  if (!lista.length){
    ul.innerHTML = '<li class="recent-empty">Sin registros todavía.</li>';
    return;
  }

  const ordenados = [...lista].sort((a,b)=>{
    if (a.date && b.date) return b.date - a.date;
    if (a.date && !b.date) return -1;
    if (!a.date && b.date) return 1;
    return 0;
  }).slice(0,5);

  ordenados.forEach(({row,date}) => {
    const folio  = row.folio ?? row.solicitud_id ?? row.id;
    const nombre = [row.nombres, row.apellido_paterno, row.apellido_materno].filter(Boolean).join(' ');

    const li = document.createElement('li');
    li.className = 'recent-item';

    const fechaStr = date
      ? date.toLocaleDateString('es-MX',{day:'2-digit',month:'short'})
      : '';

    li.innerHTML = `
      <div class="recent-main">
        <span class="recent-folio">#${folio}</span>
        <span class="recent-name">${nombre || 'Sin nombre'}</span>
      </div>
      <div class="recent-meta">${fechaStr}</div>
    `;

    ul.appendChild(li);
  });
}

/* ================== ALERTAS ================== */
function mostrarInfo(titulo, texto){
  if (typeof Swal !== 'undefined' && Swal?.fire) {
    return Swal.fire({
      icon: 'info',
      title: titulo,
      text: texto,
      confirmButtonText: 'Entendido',
      background: '#0b1220',
      color: '#e5e7eb',
      confirmButtonColor: '#2563eb',
      customClass: {
        popup: 'swal-dark',
        title: 'swal-title',
        htmlContainer: 'swal-text'
      }
    });
  }

  alert(texto);
  return Promise.resolve();
}

function mostrarError(texto){
  if (typeof Swal !== 'undefined' && Swal?.fire) {
    return Swal.fire({
      icon: 'error',
      title: 'Error',
      text: texto,
      confirmButtonText: 'Aceptar',
      background: '#0b1220',
      color: '#e5e7eb',
      confirmButtonColor: '#dc2626',
      customClass: {
        popup: 'swal-dark',
        title: 'swal-title',
        htmlContainer: 'swal-text'
      }
    });
  }

  alert('❌ ' + texto);
  return Promise.resolve();
}

function alertSolicitudCancelada(){
  return mostrarInfo(
    'Solicitud cancelada',
    'Esta solicitud está cancelada y ya no permite más acciones.'
  );
}

async function obtenerSolicitudPorFolio(folio){
  const fila = SOLICITUDES_MAP.get(String(folio));

  if (!fila) return null;

  const rawEstado = (
    fila.estado_validacion ??
    fila.validacion ??
    fila.estado ??
    ''
  ).toString().trim().toLowerCase();

  return {
    ...fila,
    esCancelado: ['cancelado', 'cancelada'].includes(rawEstado)
  };
}

/* ================== CARGA TABLA PRINCIPAL ================== */
fetch(`${BASE_URL}get_solicitudes.php`, {
  credentials:'same-origin'
})
.then(res => res.json())
.then(j => {
  const rows = Array.isArray(j) ? j : (Array.isArray(j?.data) ? j.data : []);

  SOLICITUDES_CACHE = rows;
  SOLICITUDES_MAP = new Map(
    rows.map(s => [
      String(s.folio ?? s.solicitud_id ?? s.id),
      s
    ])
  );

  const tbody = document.getElementById('tabla-solicitudes');

  if (!tbody) return;

  tbody.innerHTML = '';

  if (!rows.length) {
    tbody.innerHTML = `
      <tr>
        <td colspan="3" class="no-result">
          No hay solicitudes iniciadas aún.
          <span>Usa "Crear Prospecto" para registrar la primera.</span>
        </td>
      </tr>
    `;

    actualizarDashboard([]);
    return;
  }

  rows.forEach(s => {
    const folio  = s.folio ?? s.solicitud_id ?? s.id;
    const nombre = [s.nombres, s.apellido_paterno, s.apellido_materno].filter(Boolean).join(' ');
    const rawEstado = (s.estado_validacion ?? s.validacion ?? s.estado ?? '').toString().trim().toLowerCase();
    const esCancelado = ['cancelado', 'cancelada'].includes(rawEstado);

    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td>${folio}</td>
      <td>
        ${nombre}
        ${esCancelado ? '<div class="estado-cancelado">Estado: Cancelado</div>' : ''}
      </td>
      <td class="acciones">
        ${
          esCancelado
            ? `
              <button class="btn-sm btn-blue is-disabled" type="button" data-cancelado="1">Continuar</button>
              <button class="btn-sm btn-blue is-disabled" type="button" data-cancelado="1">Formato</button>
              <button class="btn-sm btn-doc is-disabled" type="button" data-cancelado="1">📄 Documentos</button>
              <button class="btn-sm btn-red is-disabled" type="button" data-cancelado="1">Cancelado</button>
            `
            : `
              <button class="btn-sm btn-blue" onclick="continuarSolicitud(${folio})">Continuar</button>
              <button class="btn-sm btn-blue" onclick="generarFormato(${folio})">Formato</button>
              <button class="btn-sm btn-doc" onclick="openDocs(${folio})">📄 Documentos</button>
              <button class="btn-sm btn-red" onclick="cancelarSolicitud(${folio})">Cancelado</button>
            `
        }
      </td>
    `;

    tbody.appendChild(tr);
  });

  actualizarDashboard(rows);
})
.catch(err => {
  console.error('get_solicitudes error:', err);

  const tbody = document.getElementById('tabla-solicitudes');

  if (tbody) {
    tbody.innerHTML = `
      <tr>
        <td colspan="3" class="no-result">Error cargando datos.</td>
      </tr>
    `;
  }

  actualizarDashboard([]);
});

/* ================== ACCIONES BÁSICAS ================== */
function irAlPanel(){
  window.location.href = `${BASE_URL}home.php`;
}

async function continuarSolicitud(folio){
  const fila = await obtenerSolicitudPorFolio(folio);
  if (fila?.esCancelado) return alertSolicitudCancelada();

  window.location.href = `${BASE_URL}form.php?folio=${folio}`;
}

function crearProspecto(){
  try {
    sessionStorage.removeItem('solicitud_id');
  } catch {}

  window.location.href = `${BASE_URL}formulario.php`;
}

async function generarFormato(folio){
  const fila = await obtenerSolicitudPorFolio(folio);
  if (fila?.esCancelado) return alertSolicitudCancelada();

  fetch(`${BASE_URL}app/controllers/generar_folio.php`, {
    method:'POST',
    body: new URLSearchParams({ solicitud_id: folio })
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok' || data.status === 'ya_generado') {
      sessionStorage.setItem('solicitud_id', String(folio));

      return fetch(
        `${BASE_URL}app/controllers/obtener_datos/get_solicitud_completa.php?solicitud_id=${encodeURIComponent(folio)}`
      );
    }

    throw new Error('No se pudo generar el formato. Verifica que todos los pasos estén completos.');
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      sessionStorage.setItem('solicitud_completa', JSON.stringify(data.datos));
      window.location.href = `${BASE_URL}formato.php?folio=${encodeURIComponent(folio)}`;
    } else {
      throw new Error('No se pudieron obtener los datos completos de la solicitud.');
    }
  })
  .catch(err => {
    console.error(err);
    mostrarError(err.message || 'Error generando formato');
  });
}

function cancelarSolicitud(folio){
  if (!folio) {
    mostrarError('Falta el ID de solicitud.');
    return;
  }

  if (typeof Swal !== 'undefined' && Swal?.fire) {
    Swal.fire({
      icon: 'warning',
      title: 'Cancelar solicitud',
      text: '¿Deseas marcar esta solicitud como cancelada?',
      showCancelButton: true,
      confirmButtonText: 'Sí, cancelar',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        enviarCancelacionSolicitud(folio);
      }
    });

    return;
  }

  if (!confirm('¿Marcar esta solicitud como cancelada?')) return;

  enviarCancelacionSolicitud(folio);
}

function enviarCancelacionSolicitud(folio){
  if (typeof Swal !== 'undefined' && Swal?.fire) {
    Swal.fire({
      title: 'Cancelando solicitud…',
      text: 'Por favor espera',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });
  }

  fetch(`${BASE_URL}app/controllers/eliminar/eliminar_solicitud.php`, {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({
      solicitud_id: folio,
      estado: 'Cancelado'
    })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      if (typeof Swal !== 'undefined' && Swal?.fire) {
        Swal.fire({
          icon: 'success',
          title: 'Solicitud cancelada',
          text: 'La solicitud fue marcada como cancelada correctamente.',
          timer: 1800,
          showConfirmButton: false
        }).then(() => location.reload());
      } else {
        alert('✅ Solicitud marcada como cancelada.');
        location.reload();
      }
    } else {
      throw new Error(d.message || 'No se pudo cancelar la solicitud.');
    }
  })
  .catch(err => {
    console.error('Cancelar:', err);
    mostrarError(err.message || 'Ocurrió un error al cancelar.');
  });
}

/* ================== MARCAR REAPERTURA DE MODAL DOCUMENTOS ================== */
function marcarReaperturaDocs(folio) {
  try {
    sessionStorage.setItem('reabrir_docs', '1');
    sessionStorage.setItem('reabrir_docs_folio', String(folio));
  } catch (e) {}
}

/* ================== CONTRATO / CARÁTULA / PAGARÉ / AMORTIZACIÓN ================== */
function abrirContrato(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  const mod = String(currentModalidad || sessionStorage.getItem('contrato_modalidad') || '').toUpperCase();

  const target = esOrdinario(mod)
    ? CONTRATO_ORDINARIO_URL
    : CONTRATO_ABS;

  const url = `${target}?solicitud_id=${encodeURIComponent(String(folio))}`;

  window.location.href = url;
}

function abrirCaratula(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  const mod = String(currentModalidad || sessionStorage.getItem('contrato_modalidad') || '').toUpperCase();

  const target = esOrdinario(mod)
    ? CARATULA_ORDINARIO_URL
    : CARATULA_TOROS2_URL;

  window.location.href = `${target}?solicitud_id=${encodeURIComponent(String(folio))}`;
}

function abrirPagare(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}pagare.php?solicitud_id=${encodeURIComponent(folio)}`;
}

function abrirAmortizacion(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  const mod = String(currentModalidad || sessionStorage.getItem('contrato_modalidad') || '').toUpperCase();

  const target = esOrdinario(mod)
    ? TABLA_ORDINARIA_URL
    : `${BASE_URL}tabla_mortizacion.html`;

  window.location.href = `${target}?solicitud_id=${encodeURIComponent(String(folio))}`;
}

function abrirPolizaSeguro(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}seguro/plantilla_solicitud.html?solicitud_id=${encodeURIComponent(String(folio))}`;
}

function abrirVisitaDomicilio(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}visita.html?id=${encodeURIComponent(folio)}`;
}

function abrirVerifReferencias(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}referencia.html?solicitud_id=${encodeURIComponent(String(folio))}`;
}

function abrirAutorizacionCredito(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}autorizacion_credito.html?solicitud_id=${encodeURIComponent(String(folio))}`;
}

function abrirAvisoPrivacidad(folio){
  if (!folio) return alert('Falta el folio');

  try {
    sessionStorage.setItem('solicitud_id', String(folio));
  } catch {}

  marcarReaperturaDocs(folio);

  window.location.href = `${BASE_URL}aviso_privacidad.html?solicitud_id=${encodeURIComponent(String(folio))}`;
}

/* ================== DOCUMENTOS ================== */
async function openDocs(solicitud_id){
  const fila = await obtenerSolicitudPorFolio(solicitud_id);
  if (fila?.esCancelado) return alertSolicitudCancelada();

  currentSolicitud = solicitud_id;

  const folioEl = document.getElementById('folioActual');
  if (folioEl) folioEl.textContent = solicitud_id;

  const modal = document.getElementById('modalDocs');
  const grid = document.getElementById('docsGrid');

  if (modal) modal.classList.add('show');

  if (grid) {
    grid.innerHTML = `
      <div class="recent-empty" style="grid-column:1 / -1; padding:18px;">
        Cargando documentos…
      </div>
    `;
  }

  const cacheKey = String(solicitud_id);

  if (DOCS_CACHE.has(cacheKey)) {
    const cached = DOCS_CACHE.get(cacheKey);

    currentModalidad = cached.modalidad || '';
    currentTipoCredito = currentModalidad;

    try {
      sessionStorage.setItem('contrato_modalidad', currentModalidad);
    } catch {}

    renderDocsGrid(cached.status);
    return;
  }

  try {
    const modalidadPromise = MODALIDAD_CACHE.has(cacheKey)
      ? Promise.resolve(MODALIDAD_CACHE.get(cacheKey))
      : fetch(
          `${BASE_URL}app/controllers/obtener_datos/get_solicitud_general.php?folio=${encodeURIComponent(solicitud_id)}&t=${Date.now()}`,
          {
            cache:'no-store',
            credentials:'same-origin'
          }
        ).then(safeJSON).then(j => {
          const modalidad = String(j?.datos?.contrato_modalidad || '').toUpperCase();
          MODALIDAD_CACHE.set(cacheKey, modalidad);
          return modalidad;
        }).catch(e => {
          console.warn('No se pudo obtener modalidad:', e);
          return '';
        });

    const listarPromise = fetch(
      `${BASE_URL}app/controllers/documentos/listar.php?solicitud_id=${encodeURIComponent(solicitud_id)}&t=${Date.now()}`,
      {
        cache:'no-store',
        credentials:'same-origin'
      }
    ).then(safeJSON).catch(e => {
      console.warn('No se pudo listar documentos:', e);
      return { success:false, documentos:[] };
    });

    const completadosPromise = obtenerDocumentosCompletados(solicitud_id);

    const [modalidad, listJSON, completados] = await Promise.all([
      modalidadPromise,
      listarPromise,
      completadosPromise
    ]);

    currentModalidad = modalidad;
    currentTipoCredito = modalidad;

    try {
      sessionStorage.setItem('contrato_modalidad', currentModalidad);
    } catch {}

    const status = listJSON.success
      ? buildStatusMapFromDocs(listJSON.documentos || [], completados)
      : buildStatusMapFromDocs([], completados);

    DOCS_CACHE.set(cacheKey, {
      modalidad,
      status
    });

    renderDocsGrid(status);

  } catch (err) {
    console.error('openDocs:', err);

    const status = buildStatusMapFromDocs([], {});
    renderDocsGrid(status);
  }
}

function closeDocs(){
  currentSolicitud = null;
  document.getElementById('modalDocs').classList.remove('show');
}

function renderDocsGrid(statusMap){
  const grid = document.getElementById('docsGrid');
  if (!grid) return;

  grid.innerHTML = '';

  const keysToShow = allowedDocKeys();

  keysToShow.forEach(key => {
    const doc = byKey(key);
    if (!doc) return;

    const st = statusMap[key] || {};

    const tienePDF = !!st.ok;
    const estaCompleto = !!st.completo;

    let badgeClass = 'missing';
    let badgeText  = 'Falta';
    let notaEstado = '';

    if (tienePDF) {
      badgeClass = 'signed';
      badgeText = 'PDF firmado';
      notaEstado = `<div class="doc-note ok">Documento firmado cargado.</div>`;
    } else if (estaCompleto) {
      badgeClass = 'completed';
      badgeText = 'Completo';
      notaEstado = `<div class="doc-note warning">Falta subir solo el PDF firmado.</div>`;
    }

    let extraBtn = '';

    if (key === 'contrato') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirContrato(currentSolicitud)">
          📄 ${estaCompleto ? 'Ver contrato' : 'Generar contrato'}
        </button>
      `;
    } else if (key === 'retroactivo_40') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="window.location.href='${BASE_URL}retroactivo.html?solicitud_id='+encodeURIComponent(currentSolicitud)">
          ⚙️ ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'caratula') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirCaratula(currentSolicitud)">
          📘 ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'tabla_amortizacion') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirAmortizacion(currentSolicitud)">
          📊 ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'pagare') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirPagare(currentSolicitud)">
          📝 ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'verif_domicilio') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirVisitaDomicilio(currentSolicitud)">
          🏠 ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'verif_referencias') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirVerifReferencias(currentSolicitud)">
          👥 ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'autorizacion_credito') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirAutorizacionCredito(currentSolicitud)">
          ✅ ${estaCompleto ? 'Ver' : 'Generar'}
        </button>
      `;
    } else if (key === 'poliza_seguro') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirPolizaSeguro(currentSolicitud)">
          🛡️ ${estaCompleto ? 'Ver / Generar' : 'Generar'}
        </button>
      `;
    } else if (key === 'aviso_privacidad') {
      extraBtn = `
        <button class="btn-ghost" type="button" onclick="abrirAvisoPrivacidad(currentSolicitud)">
          🔐 ${estaCompleto ? 'Ver / Generar' : 'Generar'}
        </button>
      `;
    }

    const verBtns = tienePDF && st.url ? `
      <a class="btn-ghost" href="${st.url}" target="_blank" rel="noopener">🔗 Ver PDF</a>
      <button class="btn-ghost" type="button" onclick="openPreview('${st.url}','${doc.label.replace(/'/g,"\\'")}')">
        👁️ Previsualizar
      </button>
    ` : '';

    const card = document.createElement('div');
    card.className = 'doc-card';

    card.innerHTML = `
      <div class="doc-icon" title="${doc.label}">${doc.icon}</div>

      <div class="doc-body">
        <h4 class="doc-title">${doc.label}</h4>

        <div class="doc-meta">
          <span class="badge ${badgeClass}">
            <span class="dot"></span>${badgeText}
          </span>
          ${notaEstado}
        </div>

        <div class="doc-actions">
          <label class="btn-ghost">
            📤 Subir PDF
            <input class="hidden-input" type="file" accept="application/pdf" onchange="handleUpload(this, '${doc.key}')">
          </label>

          ${verBtns}
          ${extraBtn}
        </div>
      </div>
    `;

    grid.appendChild(card);
  });
}

async function handleUpload(input, doc_key){
  try{
    const file = input.files && input.files[0];

    if (!file) return;

    if (!currentSolicitud){
      alert('No se encontró el folio actual.');
      return;
    }

    if (file.type !== 'application/pdf' && !/\.pdf$/i.test(file.name)){
      alert('Solo se permiten archivos PDF.');
      input.value = '';
      return;
    }

    const fd = new FormData();
    fd.append('solicitud_id', currentSolicitud);
    fd.append('tipo_documento', doc_key);
    fd.append('archivo', file);

    const upRes = await fetch(`${BASE_URL}app/controllers/documentos/upload.php`, {
      method:'POST',
      body:fd,
      credentials:'same-origin'
    });

    const upJSON = await safeJSON(upRes);

if (!upJSON.success) {
  throw new Error(upJSON.message || 'No se pudo subir el archivo.');
}

DOCS_CACHE.delete(String(currentSolicitud));

const listRes = await fetch(
  `${BASE_URL}app/controllers/documentos/listar.php?solicitud_id=${encodeURIComponent(currentSolicitud)}&t=${Date.now()}`,
      {
        cache:'no-store',
        credentials:'same-origin'
      }
    );

    const listJSON = await safeJSON(listRes);

    if (!listJSON.success) {
      throw new Error(listJSON.message || 'No se pudo refrescar estado.');
    }

    const completados = await obtenerDocumentosCompletados(currentSolicitud);
    const status = buildStatusMapFromDocs(listJSON.documentos || [], completados);

    renderDocsGrid(status);

    alert('✅ Archivo subido correctamente.');

  }catch(err){
    console.error('Upload:', err);
    alert('❌ ' + err.message);
  }finally{
    if (input) input.value = '';
  }
}

/* ================== PREVIEW PDF ================== */
function openPreview(url, titulo='Documento'){
  if(!url){
    alert('No hay URL del documento.');
    return;
  }

  document.getElementById('pdfFrame').src = url;
  document.getElementById('previewDownload').href = url;
  document.getElementById('ttlPreview').textContent = `👁️ Vista previa — ${titulo}`;
  document.getElementById('modalPreview').classList.add('show');
}

function closePreview(){
  const ifr = document.getElementById('pdfFrame');

  if (ifr) ifr.src = 'about:blank';

  document.getElementById('modalPreview').classList.remove('show');
}

/* ================== DESCARGAR PLANTILLA ================== */
const isLocal = location.pathname.includes('/hp/public');
const PREFIX  = isLocal ? '/hp/public' : '';
const URL_PDF_MARCADO = `${PREFIX}/app/controllers/documentos/descargar_pdf_marcado.php`;

function descargarPlantilla() {
  const url = `${URL_PDF_MARCADO}?t=${Date.now()}`;
  window.open(url, '_blank', 'noopener');
}

/* ================== SESIÓN: LOGOUT ================== */
async function cerrarSesion(){
  try{
    await fetch(URL_LOGOUT, {
      method:'POST',
      credentials:'include',
      headers: {'Content-Type':'application/x-www-form-urlencoded'},
      body:''
    });
  }catch(_){}

  window.location.href = `${BASE_URL}login.html`;
}

/* ================== HERRAMIENTAS ================== */
const HERRAMIENTAS_LINKS = {
  credito: `${BASE_URL}caratula.html`,
  retroactivo: `${BASE_URL}retroactivo.html`,
  salarioPromedio: `${BASE_URL}subir.php`,
  ahorro_fijo: `${BASE_URL}ahorro_fijo.html`,
  ahorro_semanal: `${BASE_URL}ahorro.html`,
  calcular_pension: `${BASE_URL}sp.html`,
  caratula_ordinaria: `${BASE_URL}Caratulas/caratula1.html`,
};

const TOOLS = [
  { key: 'credito', label: 'Crédito (Carátula)', icon: '📘', href: () => HERRAMIENTAS_LINKS.credito },
  { key: 'caratula_ordinaria', label: 'Carátula ordinaria', icon: '📙', href: () => HERRAMIENTAS_LINKS.caratula_ordinaria },
  { key: 'retroactivo', label: 'Retroactivo', icon: '⏪', href: () => HERRAMIENTAS_LINKS.retroactivo },
  { key: 'salarioPromedio', label: 'Salario promedio (PDF)', icon: '📄', href: () => HERRAMIENTAS_LINKS.salarioPromedio },
  { key: 'ahorro_fijo', label: 'Ahorro fijo', icon: '💰', href: () => HERRAMIENTAS_LINKS.ahorro_fijo },
  { key: 'ahorro_semanal', label: 'Ahorro semanal', icon: '📅', href: () => HERRAMIENTAS_LINKS.ahorro_semanal },
  { key: 'calcular_pension', label: 'Calcular pensión', icon: '🧓', href: () => HERRAMIENTAS_LINKS.calcular_pension },
];

function openTools(){
  renderToolsGrid();
  document.getElementById('modalTools').classList.add('show');
}

function closeTools(){
  document.getElementById('modalTools').classList.remove('show');
}

function renderToolsGrid(){
  const grid = document.getElementById('toolsGrid');
  if (!grid) return;

  grid.innerHTML = '';

  TOOLS.forEach(t => {
    const card = document.createElement('div');
    card.className = 'doc-card';

    card.innerHTML = `
      <div class="doc-icon" title="${t.label}">${t.icon}</div>
      <div class="doc-body">
        <h4 class="doc-title">${t.label}</h4>
        <div class="doc-meta">
          <span class="badge"><span class="dot"></span>Simulador</span>
        </div>
        <div class="doc-actions">
          <button class="btn-ghost" type="button" onclick="openToolHere('${t.key}')">Abrir</button>
        </div>
      </div>
    `;

    grid.appendChild(card);
  });
}

function openToolHere(key){
  const item = TOOLS.find(x => x.key === key);
  if (!item) return;

  const url = item.href();

  closeTools();

  window.location.href = url;
}

/* ================== GRÁFICA ================== */
function construirGraficaMensual(rows) {
  const canvas   = document.getElementById('chartSolicitudes');
  const emptyMsg = document.getElementById('chartEmptyMessage');

  if (!canvas) return;

  if (typeof Chart === 'undefined') {
    if (emptyMsg) emptyMsg.style.display = 'block';
    canvas.style.display = 'none';
    return;
  }

  const ahora = new Date();
  const meses = [];

  for (let i = 5; i >= 0; i--) {
    const d = new Date(ahora.getFullYear(), ahora.getMonth() - i, 1);
    const key = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0');
    const label = d.toLocaleDateString('es-MX', { month: 'short' });

    meses.push({
      key,
      label,
      count: 0
    });
  }

  rows.forEach(s => {
    const f = getRowDate(s);
    if (!f) return;

    const key = f.getFullYear() + '-' + String(f.getMonth() + 1).padStart(2, '0');
    const mes = meses.find(m => m.key === key);

    if (mes) mes.count++;
  });

  const tieneDatos = meses.some(m => m.count > 0);

  if (!tieneDatos) {
    if (emptyMsg) emptyMsg.style.display = 'block';
    canvas.style.display = 'none';

    if (chartSolicitudesInstance) {
      chartSolicitudesInstance.destroy();
      chartSolicitudesInstance = null;
    }

    return;
  }

  if (emptyMsg) emptyMsg.style.display = 'none';
  canvas.style.display = 'block';

  if (!canvas.height) canvas.height = 220;

  if (chartSolicitudesInstance) {
    chartSolicitudesInstance.destroy();
  }

  const ctx = canvas.getContext('2d');

  chartSolicitudesInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: meses.map(m => m.label.toUpperCase()),
      datasets: [{
        label: 'Solicitudes',
        data: meses.map(m => m.count),
        tension: 0.35,
        borderWidth: 2,
        pointRadius: 4,
        pointHoverRadius: 5
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          labels: {
            color: '#e5e7eb',
            font: { size: 11 }
          }
        },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.raw} solicitudes`
          }
        }
      },
      scales: {
        x: {
          ticks: { color: '#ffffff' },
          grid:  { color: 'rgba(148,163,184,.2)' }
        },
        y: {
          beginAtZero: true,
          ticks: {
            color: '#9ca3af',
            precision: 0
          },
          grid: {
            color: 'rgba(148,163,184,.18)'
          }
        }
      }
    }
  });
}

/* ================== THEME TOGGLE ================== */
const THEME_KEY = 'cip_theme';

function applyTheme(theme) {
  const root = document.documentElement;

  if (theme !== 'light' && theme !== 'dark') theme = 'dark';

  root.setAttribute('data-theme', theme);

  try {
    localStorage.setItem(THEME_KEY, theme);
  } catch (e) {}

  const btn = document.getElementById('btnTema');

  if (btn) {
    btn.textContent = (theme === 'dark')
      ? '☀️ Modo claro'
      : '🌙 Modo oscuro';
  }

  const btn2 = document.getElementById('btnTema2');

  if (btn2) {
    btn2.innerHTML = `<span>${(theme === 'dark') ? '☀️ Modo claro' : '🌙 Modo oscuro'}</span>`;
  }
}

function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  const next = current === 'dark' ? 'light' : 'dark';

  applyTheme(next);
}

/* ================== ON LOAD ================== */
document.addEventListener('DOMContentLoaded', () => {
  let saved = 'dark';

  try {
    saved = localStorage.getItem(THEME_KEY) || 'dark';
  } catch (e) {}

  applyTheme(saved);

  try {
    const debeReabrir = sessionStorage.getItem('reabrir_docs');
    const folio = sessionStorage.getItem('reabrir_docs_folio');

    if (debeReabrir === '1' && folio) {
      sessionStorage.removeItem('reabrir_docs');
      sessionStorage.removeItem('reabrir_docs_folio');

      setTimeout(() => {
        openDocs(folio);
      }, 300);
    }
  } catch (e) {}
});
</script>

<script>
/* ================== PLANTILLAS ================== */
const URL_PLANTILLAS = `${PREFIX}/app/controllers/documentos/descargar_pdf_marcado.php`;

const PLANTILLAS = [
  { key:'solicitud',        label:'Solicitud',                              icon:'🗂️' },
  { key:'visita_domicilio', label:'Formato de visita a domicilio',          icon:'🏠' },
  { key:'referencias',      label:'Confirmación telefónica de referencias', icon:'📞' },
  { key:'seguro',           label:'Seguro',                                 icon:'🛡️' },
  { key:'lista',            label:'Lista de verificación',                  icon:'📋' },
  { key:'requisitos',       label:'Requisitos para financimiento',          icon:'📄' },
];

function openPlantillas(){
  renderPlantillasGrid();
  document.getElementById('modalPlantillas')?.classList.add('show');
}

function closePlantillas(){
  document.getElementById('modalPlantillas')?.classList.remove('show');
}

function renderPlantillasGrid(){
  const grid = document.getElementById('plantillasGrid');

  if (!grid) return;

  grid.innerHTML = '';

  PLANTILLAS.forEach(p => {
    const card = document.createElement('div');
    card.className = 'doc-card';

    card.innerHTML = `
      <div class="doc-icon" title="${p.label}">${p.icon}</div>

      <div class="doc-body">
        <h4 class="doc-title">${p.label}</h4>

        <div class="doc-meta">
          <span class="badge"><span class="dot"></span>Plantilla</span>
        </div>

        <div class="doc-actions">
          <button class="btn-ghost" type="button" onclick="abrirPlantilla('${p.key}')">👁️ Abrir</button>
          <button class="btn-ghost" type="button" onclick="descargarPlantillaKey('${p.key}')">⬇️ Descargar</button>
        </div>
      </div>
    `;

    grid.appendChild(card);
  });
}

function buildPlantillaUrl(tipo, modo){
  const t = encodeURIComponent(String(tipo || 'solicitud'));
  const m = encodeURIComponent(String(modo || 'descargar'));

  return `${URL_PLANTILLAS}?tipo=${t}&t=${Date.now()}&modo=${m}`;
}

function abrirPlantilla(key){
  const url = buildPlantillaUrl(key, 'ver');
  window.open(url, '_blank', 'noopener');
}

function descargarPlantillaKey(key){
  const url = buildPlantillaUrl(key, 'descargar');

  const a = document.createElement('a');
  a.href = url;
  a.target = '_blank';
  a.rel = 'noopener';

  document.body.appendChild(a);
  a.click();
  a.remove();
}
</script>

<script>
document.addEventListener('click', function(e){
  const btn = e.target.closest('[data-cancelado="1"]');

  if (!btn) return;

  e.preventDefault();
  e.stopPropagation();

  alertSolicitudCancelada();
});
</script>


</body>

</html>
