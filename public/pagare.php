<?php
$pathsRequireLogin = [
  __DIR__ . '/app/controllers/auth/require_login.php',
  __DIR__ . '/../app/controllers/auth/require_login.php',
];

$requireLoginPath = null;

foreach ($pathsRequireLogin as $path) {
  if (file_exists($path)) {
    $requireLoginPath = $path;
    break;
  }
}

if (!$requireLoginPath) {
  die('No se encontró require_login.php');
}

require_once $requireLoginPath;


$pathsConexion = [
  __DIR__ . '/app/db/conexion.php',
  __DIR__ . '/../app/db/conexion.php',
];

$conexionPath = null;

foreach ($pathsConexion as $path) {
  if (file_exists($path)) {
    $conexionPath = $path;
    break;
  }
}

if (!$conexionPath) {
  die('No se encontró conexion.php');
}

require_once $conexionPath;


$asesorSesion = $_SESSION['asesor'] ?? [];

$idAsesor = $asesorSesion['id_asesor'] 
  ?? $asesorSesion['id'] 
  ?? null;

$nombreAsesorConectado = 'error: asesor no identificado';

if ($idAsesor) {
  try {
    $stmt = $pdo->prepare("
      SELECT nombre
      FROM asesores 
      WHERE id_asesor = :id_asesor 
      LIMIT 1
    ");

    $stmt->execute([
      ':id_asesor' => $idAsesor
    ]);

    $asesorBD = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($asesorBD) {
      $nombreCompleto = trim($asesorBD['nombre'] ?? '');

      if ($nombreCompleto !== '') {
        $nombreAsesorConectado = $nombreCompleto;
      }
    }
  } catch (Exception $e) {
    $nombreAsesorConectado = 'error: asesor no identificado';
  }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
<title>Pagaré</title>
<style>
/* =========================================================
   1. RESET Y VARIABLES
========================================================= */

*{
  margin:0;
  padding:0;
  box-sizing:border-box;
}

:root{
  --primary:#0f3b2c;
  --primary-dark:#0a2a1f;
  --primary-light:#e8f3ef;
  --secondary:#c49a6c;
  --secondary-light:#fef5e8;
  --accent:#d97706;

  --gray-50:#fafaf9;
  --gray-100:#f5f5f4;
  --gray-200:#e7e5e4;
  --gray-300:#d6d3d1;
  --gray-400:#a8a29e;
  --gray-500:#78716c;
  --gray-600:#57534e;
  --gray-700:#44403c;
  --gray-800:#292524;
  --gray-900:#1c1917;

  --success:#15803d;
  --danger:#b91c1c;

  --card:#fbfdff;
  --card-border:#d9e3ef;

  --shadow-sm:0 1px 2px rgba(0,0,0,.05);
  --shadow-md:0 4px 10px rgba(0,0,0,.08);
  --shadow-lg:0 18px 30px rgba(15,23,42,.10);
  --shadow-soft:0 8px 22px rgba(15,23,42,.08);

  --border-radius:12px;
  --border-radius-sm:8px;
}


/* =========================================================
   2. CONFIGURACIÓN DE PÁGINA
========================================================= */

@page{
  size:A4;
  margin:12mm 10mm 14mm 10mm;
}

body{
  background:var(--gray-100);
  font-family:'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  line-height:1.5;
  color:var(--gray-800);
}


/* =========================================================
   3. HOJAS / CONTENEDOR PRINCIPAL
========================================================= */

.sheet{
  max-width:210mm;
  min-height:297mm;
  margin:24px auto;
  background:#fff;
  box-shadow:var(--shadow-lg);
  border-radius:var(--border-radius);
  position:relative;
  overflow:hidden;
}

.sheet:hover{
  box-shadow:0 20px 28px rgba(15,23,42,.14);
}

.page{
  padding:12mm 10mm;
  position:relative;
  min-height:277mm;
}

/* Fondo especial hoja 2 */
.sheet:nth-of-type(2){
  background:
    radial-gradient(circle at top right, rgba(196,154,108,.10), transparent 28%),
    linear-gradient(180deg, #ffffff 0%, #fcfdff 100%);
}


/* =========================================================
   4. ENCABEZADO / LOGO / DATOS SUPERIORES
========================================================= */

.header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:0;
  padding:0;
  border-bottom:none;
  position:relative;
  z-index:1;
  overflow:hidden;
  width:100%;
  gap:18px;
}

.logo{
  display:block;
  margin:0;
  object-fit:contain;
  width:auto;
  max-width:520px;
}

.logo--center{
  height:170px;
  margin-top:-18px;
  margin-bottom:-18px;
  margin-left:0;
  margin-right:auto;
}

.header-right{
  flex:1;
  display:flex;
  flex-direction:column;
  align-items:flex-end;
  justify-content:center;
  text-align:right;
  line-height:1.12;
  margin:0;
  padding:0;
}

.top-frase{
  font-weight:700;
  font-size:12px;
  color:#0d5c86;
  margin:0 0 3px 0;
}

.top-direccion,
.top-correo{
  font-size:11px;
  margin:0;
  color:#1f2d3d;
}

.topbar{
  margin:0 0 1mm;
  height:6px;
  background:
    linear-gradient(var(--primary-light), var(--primary-light)) top/100% 2px no-repeat,
    linear-gradient(var(--primary-light), var(--primary-light)) bottom/100% 2px no-repeat;
  border-radius:2px;
}

.sheet:nth-of-type(2) .topbar{
  background:
    linear-gradient(var(--primary), var(--primary)) top/100% 2px no-repeat,
    linear-gradient(var(--secondary), var(--secondary)) bottom/100% 2px no-repeat;
}

.folio{
  text-align:right;
  margin-bottom:12px;
  font-weight:600;
  color:var(--gray-600);
}

.folio .tag{
  background:var(--secondary-light);
  padding:4px 12px;
  border-radius:20px;
  font-size:12px;
  font-weight:700;
  color:var(--secondary);
  letter-spacing:.5px;
  display:inline-block;
}


/* =========================================================
   5. TÍTULO PRINCIPAL Y TEXTO LEGAL
========================================================= */

.title{
  text-align:center;
  font-size:32px;
  font-weight:800;
  letter-spacing:6px;
  color:var(--primary);
  margin:24px 0 28px;
  background:linear-gradient(135deg, var(--primary), var(--primary-dark));
  -webkit-background-clip:text;
  -webkit-text-fill-color:transparent;
  background-clip:text;
}

p,
.lead{
  font-size:13.5px;
  line-height:1.6;
  text-align:justify;
  color:var(--gray-700);
  margin-bottom:18px;
}

.b,
strong{
  color:var(--primary);
  font-weight:700;
}

.u,
.underline{
  text-decoration:underline;
  text-decoration-thickness:1px;
  text-underline-offset:2px;
  text-decoration-color:var(--secondary);
}

.mark,
.highlight{
  background:var(--secondary-light);
  padding:2px 8px;
  border-radius:20px;
  font-weight:700;
  color:var(--secondary);
}

.place{
  text-align:right;
  font-size:14px;
  font-weight:500;
  margin:24px 0 32px;
  padding:12px 0;
  border-top:1px dashed var(--gray-300);
  border-bottom:1px dashed var(--gray-300);
  color:var(--gray-600);
}


/* =========================================================
   6. CAMPOS GENERALES
========================================================= */

.field{
  display:block;
  margin:0 0 10px;
  padding:12px 14px;
  background:linear-gradient(180deg, #fff 0%, var(--card) 100%);
  border:1px solid var(--card-border);
  border-radius:12px;
  box-shadow:var(--shadow-soft);
  min-height:74px;
  transition:.2s ease;
}

.field:hover{
  transform:translateY(-1px);
  box-shadow:0 10px 22px rgba(15,23,42,.10);
}

.label{
  display:block;
  margin-bottom:6px;
  font-size:11px;
  line-height:1.2;
  font-weight:800;
  text-transform:uppercase;
  letter-spacing:.06em;
  color:var(--gray-500);
}

.value{
  display:block;
  font-size:14px;
  line-height:1.35;
  font-weight:700;
  color:var(--gray-800);
  word-break:break-word;
}

.value b,
.value span,
.value a{
  font-size:14px;
}

.hl{
  display:inline-block;
  background:var(--secondary-light);
  border:1px solid #edd8be;
  border-radius:8px;
  padding:3px 7px;
}


/* =========================================================
   7. TÍTULOS DE SECCIÓN
========================================================= */

.h3,
.beneficiario-section h4{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  margin:4mm 0 3.2mm;
  font-size:18px;
  font-weight:800;
  letter-spacing:.04em;
  color:var(--primary);
  text-transform:uppercase;
  text-align:center;
}

.h3::before,
.h3::after,
.beneficiario-section h4::before,
.beneficiario-section h4::after{
  content:"";
  width:34px;
  height:2px;
  border-radius:999px;
  background:linear-gradient(90deg, transparent, var(--secondary), transparent);
}


/* =========================================================
   8. OBLIGADO SOLIDARIO 2x2
========================================================= */

.obligado-grid{
  display:grid !important;
  grid-template-columns:1fr 1fr !important;
  gap:10px 12px !important;
  width:100% !important;
  margin-bottom:10px !important;
  clear:both !important;
}

.obligado-grid .field{
  width:100% !important;
  margin:0 !important;
  float:none !important;
  min-height:64px !important;
  padding:10px 12px !important;
}


/* =========================================================
   9. BENEFICIARIO 2x2
   Igual al obligado solidario
========================================================= */

.beneficiario-section{
  clear:both !important;
  width:100% !important;
  max-width:none !important;
  margin:4mm 0 0 0 !important;
  padding:0 !important;
}

.beneficiario-grid{
  display:grid !important;
  grid-template-columns:1fr 1fr !important;
  gap:10px 12px !important;
  width:100% !important;
  max-width:none !important;
  margin:0 0 10px 0 !important;
  padding:0 !important;
  clear:both !important;
}

.beneficiario-grid .field{
  width:100% !important;
  max-width:none !important;
  min-height:64px !important;
  padding:10px 12px !important;
  margin:0 !important;
  float:none !important;
  border-radius:10px !important;
}


/* =========================================================
   10. FIRMAS / HUELLAS
   Misma estructura visual en hoja 1 y hoja 2
========================================================= */

.sign-area{
  clear:both;
  width:100%;
  margin:18px 0 12px;
  padding-top:0;
}

.sign-grid,
.sign-grid-obligado{
  display:grid;
  grid-template-columns:55mm 55mm;
  justify-content:space-between;
  gap:0;
  width:100%;
  margin:0 auto 10px;
  align-items:start;
}

/* Oculta el div vacío del centro */
.sign-grid > div:empty,
.sign-grid-obligado > div:empty{
  display:none;
}

.thumb,
.thumb-obligado{
  width:55mm;
  height:38mm;
  min-height:38mm;
  max-height:38mm;

  border:2px dashed var(--gray-300);
  border-radius:var(--border-radius);
  background:repeating-linear-gradient(
    45deg,
    var(--gray-50),
    var(--gray-50) 10px,
    var(--gray-100) 10px,
    var(--gray-100) 20px
  );
  position:relative;
  transition:.2s ease;
  user-select:none;
}

.thumb:hover{
  border-color:var(--secondary);
  background:var(--secondary-light);
}

.chip{
  position:absolute;
  top:-12px;
  left:50%;
  transform:translateX(-50%);
  background:#fff;
  padding:2px 12px;
  font-size:10px;
  font-weight:700;
  color:var(--gray-500);
  border-radius:999px;
  border:1px solid var(--gray-300);
  white-space:nowrap;
  text-align:center;
  line-height:1.2;
}

.name-underline,
.name-underline-obligado{
  width:70%;
  margin:10px auto 6px;
  border-top:1.5px solid var(--gray-300);
}

.name,
.name-obligado{
  text-align:center;
  font-weight:700;
  font-size:12px;
  margin-top:4px;
}

.role,
.role-obligado{
  text-align:center;
  font-size:10px;
  color:var(--gray-500);
  text-transform:uppercase;
  letter-spacing:1px;
}


/* =========================================================
   11. COPIAS DE INE
========================================================= */

.copies{
  display:grid;
  grid-template-columns:1fr 1fr;
  column-gap:7mm;
  align-items:start;
  justify-items:center;
  margin-top:4mm;
  break-inside:avoid;
  page-break-inside:avoid;
}

.copy-title{
  text-align:center;
  font-weight:700;
  margin:0 0 2.6mm;
}

.copies .copy-card{
  width:83mm;
  height:53mm;
  box-sizing:border-box;
  border:1px solid var(--box, #cbd5e1);
  border-radius:8px;
  background:#fff;
  position:relative;
  overflow:hidden;
  padding:0;
}

.copy-viewport{
  position:relative;
  width:100%;
  height:100%;
  overflow:hidden;
  background:#f8fafc;
  user-select:none;
  touch-action:none;
  display:flex;
  align-items:center;
  justify-content:center;
}

.copy-img{
  display:block;
  max-width:none;
  max-height:none;
  width:auto;
  height:auto;
  transform-origin:center center;
  will-change:transform;
  pointer-events:none;
  image-rendering:auto;
}


/* =========================================================
   12. CONTROLES DE INE
========================================================= */

.ine-controls{
  margin-top:6px;
  display:grid;
  gap:6px;
}

.ine-controls .row{
  display:flex;
  align-items:center;
  gap:8px;
  flex-wrap:wrap;
}

.ine-controls .btn{
  border:1px solid #cbd5e1;
  background:#fff;
  padding:6px 10px;
  border-radius:8px;
  cursor:pointer;
  font-weight:600;
  color:#1f2937;
}

.ine-controls .btn:hover{
  background:#f1f5f9;
}

.ine-controls .zoom-range{
  width:220px;
}

.ine-controls .file{
  border:1px dashed #cbd5e1;
  padding:6px 10px;
  border-radius:8px;
  font-weight:600;
  display:inline-flex;
  align-items:center;
  gap:8px;
  background:#fff;
  cursor:pointer;
}

.ine-controls .file input{
  display:none;
}

.ine-controls .hint{
  font-size:.88rem;
  color:#64748b;
}

.export-bar{
  margin-top:10px;
  display:flex;
  gap:12px;
  justify-content:flex-end;
  flex-wrap:wrap;
}

.export-bar .btn.big{
  padding:10px 14px;
  border-radius:10px;
  font-size:15px;
}


/* =========================================================
   13. NOTA LEGAL
========================================================= */

.legal{
  clear:both;
  font-size:11px;
  line-height:1.3;
  color:var(--gray-500);
  text-align:center;
  margin-top:28px;
  padding:10px 12px;
  border:1px dashed #d8c2a5;
  border-radius:10px;
  background:#fffaf4;
  font-style:italic;
}


/* =========================================================
   14. BOTONES GENERALES
========================================================= */

.btn{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:8px;
  padding:10px 18px;
  border:none;
  border-radius:10px;
  font-family:inherit;
  font-size:14px;
  font-weight:700;
  cursor:pointer;
  transition:.2s ease;
  box-shadow:var(--shadow-sm);
}

.btn:hover{
  transform:translateY(-1px);
}

.btn.big{
  padding:11px 20px;
  font-size:14px;
}

.btn--primary{
  background:linear-gradient(135deg, var(--primary), var(--primary-dark));
  color:#fff;
}

.btn--primary:hover{
  filter:brightness(1.03);
}

.btn--ghost{
  background:transparent;
  border:1px solid #d1d5db;
  color:#374151;
}

.btn--ghost:hover{
  background:#f3f4f6;
}

body > .btn.no-print{
  margin:10px 6px 0 0;
}


/* =========================================================
   15. BOTONES FLOTANTES
========================================================= */

.ine-actions{
  position:fixed;
  right:24px;
  bottom:22px;
  z-index:9999;

  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;

  padding:10px;
  border-radius:999px;
  background:rgba(255,255,255,.88);
  border:1px solid rgba(148,163,184,.35);
  box-shadow:0 18px 45px rgba(15,23,42,.22);
  backdrop-filter:blur(14px);
  -webkit-backdrop-filter:blur(14px);
}

.ine-actions .btn{
  min-width:120px;
  height:42px;

  display:inline-flex;
  align-items:center;
  justify-content:center;
  gap:6px;

  border:none;
  border-radius:999px;
  padding:0 18px;

  font-size:.78rem;
  font-weight:800;
  color:#fff;
  cursor:pointer;
  text-decoration:none;

  box-shadow:0 10px 24px rgba(15,23,42,.18);
  transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
}

.ine-actions .btn:hover{
  transform:translateY(-2px);
  box-shadow:0 14px 30px rgba(15,23,42,.25);
  filter:brightness(1.04);
}

.ine-actions .btn:active{
  transform:translateY(0) scale(.98);
}

.btn-ine-save,
#btn-guardar-ine{
  background:linear-gradient(135deg,#1f8f5f,#16613f) !important;
}

.btn-ine-print,
#btn-print{
  background:linear-gradient(135deg,#0e6a98,#083e5b) !important;
}

.btn-ine-back,
#btn-back{
  background:linear-gradient(135deg,#64748b,#334155) !important;
}


/* =========================================================
   16. CORREOS Y MONTO EN LETRAS
========================================================= */

#benef_mailto,
#benef_mail,
#obligado_mail_link,
#obligado_mail{
  text-transform:none !important;
  font-variant:normal;
  word-break:break-all;
}

#benef_mailto,
#obligado_mail_link{
  color:var(--primary);
  text-decoration:none;
}

#benef_mailto:hover,
#obligado_mail_link:hover{
  color:var(--accent);
}

#montoLetras{
  font-weight:800 !important;
  color:#000 !important;
}


/* =========================================================
   17. FOOTER DE IMPRESIÓN
========================================================= */

.sheet .print-footer{
  position:absolute;
  left:0;
  right:0;
  bottom:6mm;
  text-align:center;
  color:var(--gray-400);
  font-size:9px;
}


/* =========================================================
   18. RESPONSIVE PANTALLA
========================================================= */

@media screen and (max-width:900px){

  .sheet{
    margin:12px auto;
    border-radius:10px;
  }

  .page{
    padding:8mm 6mm;
  }

  .title{
    font-size:24px;
    letter-spacing:3px;
  }

  .sign-grid,
  .sign-grid-obligado{
    grid-template-columns:1fr;
    gap:28px;
  }

  .copies{
    grid-template-columns:1fr;
    gap:20px;
  }

  .copy-viewport{
    height:46mm;
  }

  .ine-controls .zoom-range{
    width:140px;
  }
}

@media screen and (max-width:680px){

  .ine-actions{
    left:12px;
    right:12px;
    bottom:12px;
    border-radius:18px;
    flex-wrap:wrap;
  }

  .ine-actions .btn{
    flex:1 1 auto;
    min-width:120px;
  }

  .obligado-grid,
  .beneficiario-grid{
    grid-template-columns:1fr !important;
  }
}


/* =========================================================
   19. IMPRESIÓN GENERAL
========================================================= */

@media print{

  html,
  body{
    width:210mm;
    margin:0;
    padding:0;
    background:#fff;
    -webkit-print-color-adjust:exact;
    print-color-adjust:exact;
  }

  .no-print,
  .ine-actions,
  .ine-actions .btn{
    display:none !important;
  }

  .sheet{
    width:210mm;
    min-height:297mm;
    margin:0 !important;
    padding:0 !important;
    box-shadow:none !important;
    border-radius:0 !important;
    overflow:hidden !important;
    break-after:page;
    page-break-after:always;
  }

  .sheet:last-of-type{
    break-after:auto;
    page-break-after:auto;
  }

  .page{
    min-height:277mm;
    padding:4mm 7mm 8mm 7mm !important;
    overflow:hidden;
  }

  .print-footer{
    position:fixed;
    left:0;
    right:0;
    bottom:4mm;
    text-align:center;
    z-index:9999;
    color:var(--gray-400);
    font-size:9px;
  }

  .sign-area,
  .copies,
  .copy-card,
  .beneficiario-section,
  .field{
    break-inside:avoid;
    page-break-inside:avoid;
  }

  .data-card:hover,
  .copy-card:hover,
  .thumb:hover,
  .field:hover{
    transform:none !important;
    box-shadow:none !important;
  }


  /* =======================================================
     19.1 IMPRESIÓN: ENCABEZADO
  ======================================================= */

  .header{
    margin:0 !important;
    padding:0 !important;
    border-bottom:none !important;
    overflow:hidden !important;
    display:flex !important;
    justify-content:space-between !important;
    align-items:center !important;
    width:100% !important;
    gap:18px !important;
  }

  .logo{
    display:block !important;
    width:auto !important;
    max-width:560px !important;
    object-fit:contain !important;
    margin:0 !important;
  }

  .logo--center{
    height:190px !important;
    margin-top:-26px !important;
    margin-bottom:-34px !important;
    margin-left:0 !important;
    margin-right:auto !important;
  }

  .header-right{
    flex:1 !important;
    display:flex !important;
    flex-direction:column !important;
    align-items:flex-end !important;
    justify-content:center !important;
    text-align:right !important;
    line-height:1.12 !important;
    margin:0 !important;
    padding:0 !important;
  }

  .top-frase{
    font-size:12px !important;
    margin:0 0 3px 0 !important;
  }

  .top-direccion,
  .top-correo{
    font-size:11px !important;
    margin:0 !important;
  }

  .topbar{
    margin:-10px 0 1mm !important;
    height:6px !important;
  }

  .folio{
    margin-bottom:6px !important;
  }

  .title{
    margin:12px 0 16px !important;
    font-size:28px !important;
    letter-spacing:5px !important;
  }


  /* =======================================================
     19.2 IMPRESIÓN: HOJA 2
  ======================================================= */

  .sheet:nth-of-type(2) .page{
    padding:10mm 9mm 9mm 9mm !important;
  }

  .sheet:nth-of-type(2) .logo--center{
    height:190px !important;
    margin-top:-24px !important;
    margin-bottom:-34px !important;
  }

  .sheet:nth-of-type(2) .topbar{
    margin:-12px 0 1mm !important;
    height:5px !important;
  }

  .sheet:nth-of-type(2) .folio{
    margin-bottom:5px !important;
  }


  /* =======================================================
     19.3 IMPRESIÓN: OBLIGADO Y BENEFICIARIO
  ======================================================= */

  .sheet:nth-of-type(2) .h3,
  .sheet:nth-of-type(2) .beneficiario-section h4{
    margin:2mm 0 2mm !important;
    font-size:15px !important;
    line-height:1.1 !important;
  }

  .sheet:nth-of-type(2) .h3::before,
  .sheet:nth-of-type(2) .h3::after,
  .sheet:nth-of-type(2) .beneficiario-section h4::before,
  .sheet:nth-of-type(2) .beneficiario-section h4::after{
    width:24px !important;
  }

  .sheet:nth-of-type(2) .obligado-grid,
  .sheet:nth-of-type(2) .beneficiario-grid{
    display:grid !important;
    grid-template-columns:1fr 1fr !important;
    gap:6px 8px !important;
    width:100% !important;
    margin-bottom:6px !important;
  }

  .sheet:nth-of-type(2) .obligado-grid .field,
  .sheet:nth-of-type(2) .beneficiario-grid .field{
    width:100% !important;
    margin:0 !important;
    float:none !important;
    min-height:50px !important;
    padding:7px 9px !important;
  }

  .sheet:nth-of-type(2) .obligado-grid .label,
  .sheet:nth-of-type(2) .beneficiario-grid .label{
    font-size:9px !important;
    margin-bottom:3px !important;
  }

  .sheet:nth-of-type(2) .obligado-grid .value,
  .sheet:nth-of-type(2) .obligado-grid .value b,
  .sheet:nth-of-type(2) .obligado-grid .value span,
  .sheet:nth-of-type(2) .obligado-grid .value a,
  .sheet:nth-of-type(2) .beneficiario-grid .value,
  .sheet:nth-of-type(2) .beneficiario-grid .value b,
  .sheet:nth-of-type(2) .beneficiario-grid .value span,
  .sheet:nth-of-type(2) .beneficiario-grid .value a{
    font-size:11px !important;
    line-height:1.15 !important;
  }


  /* =======================================================
     19.4 IMPRESIÓN: FIRMAS / HUELLAS IGUALES
  ======================================================= */

  .sheet:nth-of-type(1) .sign-area,
  .sheet:nth-of-type(2) .sign-area{
    clear:both !important;
    width:100% !important;
    margin:8px 0 8px !important;
    padding-top:2mm !important;
  }

.sheet:nth-of-type(1) .sign-grid,
.sheet:nth-of-type(2) .sign-grid,
.sheet:nth-of-type(2) .sign-grid-obligado{
  display:grid !important;
  grid-template-columns:55mm 55mm !important;
  justify-content:space-between !important;
  gap:0 !important;
  width:100% !important;
  margin:0 auto 10px !important;
  align-items:start !important;
}

  .sheet:nth-of-type(1) .sign-grid > div:empty,
  .sheet:nth-of-type(2) .sign-grid > div:empty,
  .sheet:nth-of-type(2) .sign-grid-obligado > div:empty{
    display:none !important;
  }

.sheet:nth-of-type(1) .thumb,
.sheet:nth-of-type(2) .thumb,
.sheet:nth-of-type(2) .thumb-obligado{
  width:55mm !important;
  height:38mm !important;
  min-height:38mm !important;
  max-height:38mm !important;
}

  .sheet:nth-of-type(1) .name-underline,
  .sheet:nth-of-type(2) .name-underline,
  .sheet:nth-of-type(2) .name-underline-obligado{
    width:70% !important;
    margin:10px auto 6px !important;
  }

  .sheet:nth-of-type(1) .name,
  .sheet:nth-of-type(2) .name,
  .sheet:nth-of-type(2) .name-obligado{
    font-size:12px !important;
    margin-top:4px !important;
  }

  .sheet:nth-of-type(1) .role,
  .sheet:nth-of-type(2) .role,
  .sheet:nth-of-type(2) .role-obligado{
    font-size:10px !important;
  }


  /* =======================================================
     19.5 IMPRESIÓN: COPIAS INE
  ======================================================= */

  .sheet:nth-of-type(2) .copies{
    gap:10px !important;
    margin:8px 0 10px !important;
    align-items:start !important;
  }

  .sheet:nth-of-type(2) .copy-title{
    font-size:11px !important;
    margin-bottom:4px !important;
  }

  .sheet:nth-of-type(2) .copy-card{
    width:83mm !important;
    height:53mm !important;
    padding:0 !important;
    overflow:hidden !important;
  }

  .sheet:nth-of-type(2) .copy-viewport{
    width:100% !important;
    height:100% !important;
    min-height:0 !important;
    max-height:none !important;
    display:flex !important;
    align-items:center !important;
    justify-content:center !important;
    overflow:hidden !important;
    position:relative !important;
    background:#fff !important;
  }

  .sheet:nth-of-type(2) .copy-img{
    display:block !important;
    width:auto !important;
    height:auto !important;
    max-width:none !important;
    max-height:none !important;
    object-fit:initial !important;
    object-position:center center !important;
    margin:0 auto !important;
  }


  /* =======================================================
     19.6 IMPRESIÓN: NOTA LEGAL
  ======================================================= */

  .sheet:nth-of-type(1) .legal,
  .sheet:nth-of-type(2) .legal{
    margin-top:4mm !important;
    padding:6px 8px !important;
    font-size:9px !important;
    line-height:1.1 !important;
  }
}
</style>

</head>
<body>

<div class="ine-actions no-print">
  <button id="btn-guardar-ine" type="button" class="btn btn--primary btn-ine-save">
    💾 Guardar INE
  </button>

  <button id="btn-print" type="button" class="btn big btn-ine-print">
    🖨️ Imprimir
  </button>

  <button id="btn-back" type="button" class="btn btn-ine-back">
    ⬅️ Volver
  </button>
</div>
<div class="sheet">
  <div class="page">

    <!-- ===== Encabezado ===== -->
<div class="header header--one-logo">
  <div class="header-left">
    <img class="logo logo--center" src="/img/logo.png" alt="Logo CIP Financial Group">
  </div>

  <div class="header-right">
    <div class="top-frase">Seguridad para tu retiro, confianza para tu futuro.</div>
    <div class="top-direccion">📍 Jose Maria Pino Suarez 119, Santa Ana Tlapaltitlán, 52160 Toluca de Lerdo, Méx.</div>
    <div class="top-correo">✉️ contacto@cipmexico.com.mx</div>
  </div>
</div>
    <div class="topbar"></div>
    <div class="folio">FOLIO: <span id="folioTag" class="tag">[CFMXXXX]</span></div>
    <!-- Título -->
    <div class="title">P A G A R É</div>

    <!-- ===== Cuerpo ===== -->
    <p class="lead">
      <span id="suscriptor_nombre" class="b u">[Juvenal Martínez Lopez]</span> (en lo sucesivo, el
      <span class="u">“Suscriptor”</span>) por medio del presente hago constar que debo y pagaré
      incondicionalmente a la orden de <span class="b" id="asesor_beneficiario_pagare"><?= htmlspecialchars($nombreAsesorConectado, ENT_QUOTES, 'UTF-8') ?></span>
      (en lo sucesivo, el “Beneficiario”), la cantidad de
      <span class="mark">$[000,000.00]</span>
      <span id="montoLetras"><strong>(Cero pesos 00/100 M.N.)</strong></span>.
    </p>

    <p class="lead">
      Cualesquier pagos que deban realizarse por virtud del presente pagaré, deberán ser
      cubiertos por el Suscriptor al Beneficiario
      <span class="b u">[Fecha de Vencimiento]</span> (la “Fecha de Vencimiento”) conforme a la
      siguiente forma de pago: <span class="b">EN TRANSFERENCIA;</span> y/o en su defecto
      el pago deberá realizarse en el domicilio del Suscriptor. Desde la fecha de suscripción
      del presente pagaré y hasta la Fecha de Vencimiento se generarán intereses ordinarios
      mensualmente a una tasa del <span class="b">3% sobre el monto total</span> de la cantidad
      amparada por este pagaré.
    </p>

    <p class="lead">
      Cualesquiera pagos que el Suscriptor deba realizar a favor del Beneficiario se deberán
      efectuar en la moneda que se establezca en el presente pagaré, en fondos libremente
      disponibles y transferibles el mismo día de su pago. Para todo lo relativo al presente pagaré,
      el Suscriptor y el Beneficiario se someten a la competencia de los tribunales y leyes de
      Toluca, Estado de México, renunciando en forma expresa a cualquier otro fuero que pudiera
      corresponderles por razón de su domicilio presente o futuro, o por cualquier otra causa.
    </p>

    <!-- Lugar y fecha -->
    <div class="place">
  Toluca, Estado de México a, <span id="fecha_firma_mark" class="mark">—</span>
</div>

    <!-- ===== Firmas / Huellas ===== -->
    <div class="sign-area">
      <div class="sign-grid">
        <div class="thumb">
          <div class="chip">PULGAR<br>IZQUIERDO</div>
        </div>

        <div></div>

        <div class="thumb">
          <div class="chip">PULGAR<br>DERECHO</div>
        </div>
      </div>

      <div class="name-underline"></div>
      <div class="name">[Nombre del Cliente]</div>
      <div class="role">SUSCRIPTOR</div>
    </div>

    <!-- Nota legal -->
    <div class="legal">
      Nota importante suscriptor: En caso de incumplimiento de pago, la deuda quedará con obligación legal a la cónyuge / concubina y/o albacea.
    </div>

    <!-- Paginado -->


  </div>
</div>



<div class="sheet">
  <div class="page">

    <!-- Encabezado -->
    <!-- ===== Encabezado ===== -->
<div class="header header--one-logo">
  <img class="logo logo--center" src="/img/logo.png" alt="Logo CIP Financial Group">

  <div class="header-right">
    <div class="top-frase">Seguridad para tu retiro, confianza para tu futuro.</div>
    <div class="top-direccion">📍 Jose Maria Pino Suarez 119, Santa Ana Tlapaltitlán, 52160 Toluca de Lerdo, Méx.</div>
    <div class="top-correo">✉️ contacto@cipmexico.com.mx</div>
  </div>
</div>
    <div class="topbar"></div>
<div class="folio">
  FOLIO: <span id="folioTag" class="tag">[CFMXXXX]</span>
</div>

    <!-- Obligado Solidario -->
   <div class="h3">Obligado Solidario</div>

<div class="obligado-grid">
  <div class="field">
    <div class="label">Nombre:</div>
    <div class="value">
      <b class="u hl" id="obligado_nombre">[Nombre Obligado Solidario]</b>
    </div>
  </div>

  <div class="field">
    <div class="label">Teléfono celular:</div>
    <div class="value">
      <span class="hl" id="obligado_tel">[Teléfono a 10 dígitos]</span>
    </div>
  </div>

  <div class="field">
    <div class="label">Correo:</div>
    <div class="value">
      <a href="mailto:correo@direccion.com" id="obligado_mail_link">
        <b class="u" id="obligado_mail">correo@direccion.com</b>
      </a>
    </div>
  </div>

  <div class="field">
    <div class="label">Parentesco:</div>
    <div class="value">
      <span class="hl" id="obligado_parentesco">[Parentesco]</span>
    </div>
  </div>
</div>

<div class="sign-area sign-area-obligado">
  <div class="sign-grid sign-grid-obligado">
    <div class="thumb thumb-obligado">
      <div class="chip">PULGAR<br>IZQUIERDO</div>
    </div>

    <div></div>

    <div class="thumb thumb-obligado">
      <div class="chip">PULGAR<br>DERECHO</div>
    </div>
  </div>

  <div class="name-underline name-underline-obligado"></div>
  <div class="name name-obligado" id="osNombreFirma">[Nombre del Cliente]</div>
  <div class="role role-obligado">Obligado Solidario</div>
</div>



<!-- Cargar PDF de INE -->
<div class="export-bar no-print" style="justify-content:flex-start; gap:10px;">
  <label class="btn big" style="cursor:pointer;">
    Cargar PDF de INE
    <input id="inePdfInput" type="file" accept="application/pdf" style="display:none;">
  </label>
  <button type="button" id="btn-auto-crop" class="btn big">Auto-recortar a 83×53</button>
</div>



<!-- Copias (tarjetas renderizadas) -->
<div class="copies" id="copiesINE">

  <!-- ANVERSO -->
  <div>
    <div class="copy-title">Copia INE Anverso</div>
    <div class="copy-card">
      <div class="copy-viewport" aria-label="Viewport INE anverso">
        <img id="ineFront" class="copy-img" alt="INE anverso" draggable="false">
      </div>
    </div>

    <!-- Controles anverso -->
    <div class="ine-controls no-print" data-target="ineFront" aria-label="Controles INE anverso">
      <div class="row">
        <label class="file">
          Cargar imagen (JPG/PNG)
          <input type="file" accept="image/*" class="ine-file">
        </label>
        <button type="button" class="btn reset"  aria-label="Restablecer imagen">Reset</button>
      </div>

      <div class="row" role="group" aria-label="Zoom y rotación">
        <button type="button" class="btn zoom-out" aria-label="Alejar">−</button>
        <input type="range" min="0.25" max="6" step="0.01" class="zoom-range" aria-label="Control de zoom">
        <button type="button" class="btn zoom-in"  aria-label="Acercar">+</button>
        <button type="button" class="btn rotate-left"  aria-label="Rotar 90 grados a la izquierda">⟲ 90°</button>
        <button type="button" class="btn rotate-right" aria-label="Rotar 90 grados a la derecha">⟳ 90°</button>
        <button id="btn-swap-ine" type="button" class="btn btn-secondary">Invertir ↔</button>
      </div>

      <div class="row" role="group" aria-label="Ajustes finos de posición">
        <button type="button" class="btn nudge-up"    aria-label="Mover arriba">↑</button>
        <button type="button" class="btn nudge-left"  aria-label="Mover izquierda">←</button>
        <button type="button" class="btn nudge-right" aria-label="Mover derecha">→</button>
        <button type="button" class="btn nudge-down"  aria-label="Mover abajo">↓</button>
        <span class="hint">Tip: arrastra con el mouse y usa la rueda para zoom.</span>
      </div>
    </div>
  </div>

  <!-- REVERSO -->
  <div>
    <div class="copy-title">Copia INE Reverso</div>
    <div class="copy-card">
      <div class="copy-viewport" aria-label="Viewport INE reverso">
        <img id="ineBack" class="copy-img" alt="INE reverso" draggable="false">
      </div>
    </div>

    <!-- Controles reverso -->
    <div class="ine-controls no-print" data-target="ineBack" aria-label="Controles INE reverso">
      <div class="row">
        <label class="file">
          Cargar imagen (JPG/PNG)
          <input type="file" accept="image/*" class="ine-file">
        </label>
        <button type="button" class="btn reset"  aria-label="Restablecer imagen">Reset</button>
      </div>

      <div class="row" role="group" aria-label="Zoom y rotación">
        <button type="button" class="btn zoom-out" aria-label="Alejar">−</button>
        <input type="range" min="0.25" max="6" step="0.01" class="zoom-range" aria-label="Control de zoom">
        <button type="button" class="btn zoom-in"  aria-label="Acercar">+</button>
        <button type="button" class="btn rotate-left"  aria-label="Rotar 90 grados a la izquierda">⟲ 90°</button>
        <button type="button" class="btn rotate-right" aria-label="Rotar 90 grados a la derecha">⟳ 90°</button>
      </div>

      <div class="row" role="group" aria-label="Ajustes finos de posición">
        <button type="button" class="btn nudge-up"    aria-label="Mover arriba">↑</button>
        <button type="button" class="btn nudge-left"  aria-label="Mover izquierda">←</button>
        <button type="button" class="btn nudge-right" aria-label="Mover derecha">→</button>
        <button type="button" class="btn nudge-down"  aria-label="Mover abajo">↓</button>
        <span class="hint">Tip: arrastra con el mouse y usa la rueda para zoom.</span>
      </div>
    </div>
  </div>
</div>

<!-- Barra de carga/exportación -->
<div class="export-bar no-print" aria-label="Opciones de exportación">
  <button type="button" id="btn-export-png" class="btn big">Exportar PNGs</button>
  <button type="button" id="btn-export-pdf" class="btn big">Exportar PDF A4</button>
</div>



    <!-- Beneficiario -->
<!-- Beneficiario -->
<div class="beneficiario-section">
  <h4>BENEFICIARIO</h4>

  <div class="beneficiario-grid">
    <div class="field">
      <div class="label">Nombre:</div>
      <div class="value">
        <b id="benef_nombre" class="u hl">[Nombre Beneficiario]</b>
      </div>
    </div>

    <div class="field">
      <div class="label">Teléfono celular:</div>
      <div class="value">
        <span id="benef_tel" class="hl">[Teléfono a 10 dígitos]</span>
      </div>
    </div>

    <div class="field">
      <div class="label">Correo:</div>
      <div class="value">
        <a id="benef_mailto" href="mailto:correo@direccion.com">
          <b id="benef_mail" class="u">correo@direccion.com</b>
        </a>
      </div>
    </div>

    <div class="field">
      <div class="label">Parentesco:</div>
      <div class="value">
        <span id="benef_parentesco" class="hl">[Parentesco]</span>
      </div>
    </div>
  </div>
</div>


    <!-- Pie -->
   <div class="legal legal-obligado">
      Nota importante suscriptor: En caso de incumplimiento de pago, la deuda quedará con obligación legal a la cónyuge / concubina y/o albacea.
    </div>

  </div>
</div>



<script>
(() => {
  'use strict';

  /* =========================
     CONFIG
     ========================= */
/* =========================
   CONFIG
   ========================= */

const BASE_URL = (() => {
  const { origin, pathname } = window.location;

  // Local XAMPP: /hp/public/
  if (pathname.includes('/hp/public/')) {
    return `${origin}/hp/public`;
  }

  // Local XAMPP: /sempiternal/public/
  if (pathname.includes('/sempiternal/public/')) {
    return `${origin}/sempiternal/public`;
  }

  // Render / Railway
  return origin;
})();

const CONFIG = {
  FIRMAS_API: `${BASE_URL}/app/controllers/contratos/firmas.php`,
  SOLICITUD_API: `${BASE_URL}/app/controllers/obtener_datos/get_solicitud_por_id.php`,
  META_CONCREDITO: `${BASE_URL}/app/controllers/concredito/leer_meta.php`,
  PAGARE_API: `${BASE_URL}/app/controllers/pagare/fecha.php`,
  REFERENCIAS_API: `${BASE_URL}/app/controllers/obtener_datos/get_referencias.php`,
  CODEUDOR_API: `${BASE_URL}/app/controllers/obtener_datos/get_codeudor.php`,

  INE_UPLOAD: `${BASE_URL}/app/controllers/pagare/upload_pagares.php`,
  INE_GET: `${BASE_URL}/app/controllers/pagare/get_pagares.php`,

  MIN_SCALE: 0.25,
  MAX_SCALE: 6,
  ZOOM_STEP_BTN: 0.10,
  ZOOM_STEP_WHEEL: 0.08,
  NUDGE: 8,
  VIEW_IDS: ['ineFront', 'ineBack'],
  TARGET_RATIO: 83 / 53,
  SOBEL_THRESHOLD: 45,
  PAD_PIXELS: 40,
  EXTRA_MARGIN: 0.15
};

  /* =========================
     HELPERS GENERALES
     ========================= */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  function sidFromUrl() {
    try {
      const s = Number(new URL(location.href).searchParams.get('solicitud_id') || 0);
      if (s) return s;
    } catch {}
    try {
      const s = Number(localStorage.getItem('ultima_solicitud_id') || 0);
      if (s) return s;
    } catch {}
    return null;
  }

  function fmtMoney(n) {
    try {
      const num = typeof n === 'number' ? n : Number(String(n).replace(/[^\d.-]/g, ''));
      return Number.isFinite(num)
        ? num.toLocaleString('es-MX', { style: 'currency', currency: 'MXN', minimumFractionDigits: 2 })
        : String(n);
    } catch {
      return String(n);
    }
  }

  function parseYMDLocal(ymd) {
    if (!ymd) return null;
    const [y, m, d] = String(ymd).slice(0, 10).split('-').map(v => parseInt(v, 10));
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
  }

  function fechaLargaLocal(dOrStr) {
    const d = dOrStr instanceof Date ? dOrStr : parseYMDLocal(dOrStr);
    if (!d || isNaN(+d)) return '';
    return d.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function addYearsSameDayLocal(baseYMD, years = 1) {
    const base = baseYMD instanceof Date ? baseYMD : parseYMDLocal(baseYMD);
    if (!base) return null;
    const y = base.getFullYear() + years;
    const m = base.getMonth();
    const d = base.getDate();
    const out = new Date(y, m, d);
    return (out.getMonth() !== m) ? new Date(y, m + 1, 0) : out;
  }

  function formatearFechaLargaMX(isoYYYYMMDD) {
    if (!isoYYYYMMDD) return '—';
    const [y, m, d] = isoYYYYMMDD.split('-').map(Number);
    const dt = new Date(y, m - 1, d);
    return dt.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' });
  }

  function todayISO() {
    const d = new Date();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  }

  function pickBestPhone(cel, tel) {
    const norm = v => (v || '').toString().replace(/\D+/g, '');
    const c = norm(cel);
    const t = norm(tel);
    return (c || t) ? (c || t) : '----------';
  }

  function put(ph, value) {
    if (value == null) return;
    const needle = String(ph);
    const repl = String(value);
    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let node;
    while ((node = walker.nextNode())) {
      const txt = node.nodeValue;
      if (txt && txt.includes(needle)) node.nodeValue = txt.split(needle).join(repl);
    }
  }

  function setFolioEverywhere(folioStr) {
    if (!folioStr) return;
    $$('#folioTag, .folioTag, [data-bind="folio"]').forEach(el => {
      el.textContent = folioStr;
    });

    const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, null);
    let node;
    while ((node = walker.nextNode())) {
      const t = node.nodeValue;
      if (t && t.includes('[CFMXXXX]')) {
        node.nodeValue = t.replace(/\[CFMXXXX\]/g, folioStr);
      }
    }
  }

  /* =========================
     NÚMERO A LETRAS
     ========================= */
  function numeroALetrasEntero(n) {
    n = Math.floor(n);
    if (n === 0) return 'cero';

    const unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
    const especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    const decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    const centenas = ['', 'cien', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    const centenasFn = (num) => {
      if (num === 0) return '';
      if (num > 99) {
        if (num === 100) return 'cien';
        const c = Math.floor(num / 100);
        const resto = num % 100;
        const pref = (c === 1) ? 'ciento' : centenas[c];
        return pref + (resto ? ' ' + centenasFn(resto) : '');
      }
      if (num > 19) {
        const d = Math.floor(num / 10);
        const u = num % 10;
        if (d === 2 && u) return 'veinti' + unidades[u];
        return decenas[d] + (u ? ' y ' + unidades[u] : '');
      }
      if (num >= 10) return especiales[num - 10];
      return unidades[num];
    };

    let resultado = '';

    const millones = Math.floor(n / 1000000);
    n = n % 1000000;
    if (millones) {
      resultado += (millones === 1) ? 'un millón' : `${numeroALetrasEntero(millones)} millones`;
    }

    const miles = Math.floor(n / 1000);
    n = n % 1000;
    if (miles) {
      const milesTxt = (miles === 1) ? 'mil' : `${numeroALetrasEntero(miles)} mil`;
      resultado += (resultado ? ' ' : '') + milesTxt;
    }

    if (n) resultado += (resultado ? ' ' : '') + centenasFn(n);

    return resultado;
  }

  function aPesosEnLetras(n) {
    const val = Math.abs(Number(n) || 0);
    const entero = Math.floor(val);
    const cent = Math.round((val - entero) * 100);
    return {
      letras: numeroALetrasEntero(entero),
      centavos: String(cent).padStart(2, '0')
    };
  }

  function primeraMayuscula(s) {
    s = String(s || '').trim();
    if (!s) return s;
    return s.charAt(0).toUpperCase() + s.slice(1);
  }

  /* =========================
     FETCHERS
     ========================= */
  async function fetchSolicitud(sid) {
    let url = `${CONFIG.SOLICITUD_API}?solicitud_id=${encodeURIComponent(sid)}&_=${Date.now()}`;
    let r = await fetch(url, { cache: 'no-store' });
    let j = await r.json().catch(() => null);

    if (!r.ok || !j) {
      url = `${CONFIG.SOLICITUD_API}?id=${encodeURIComponent(sid)}&_=${Date.now()}`;
      r = await fetch(url, { cache: 'no-store' });
      j = await r.json().catch(() => null);
    }

    if (!r.ok || !j) throw new Error('No se pudo leer la solicitud');
    return j;
  }

  function mapSolicitud(raw) {
    const s = raw?.solicitud || {};
    const c = raw?.cliente || {};

    const givenParts = [
      c.nombre, c.nombres, c.primer_nombre, c.segundo_nombre,
      c.nombre1, c.nombre2, c.nombre_1, c.nombre_2, c.nombre3
    ].filter(v => v && String(v).trim());

    const apPat = c.apellido_paterno || c.ap_paterno || c.paterno || '';
    const apMat = c.apellido_materno || c.ap_materno || c.materno || '';

    let nombre = '';
    if (givenParts.length) nombre = [...givenParts, apPat, apMat].filter(Boolean).join(' ');
    else nombre = (c.nombre_completo && c.nombre_completo.trim()) || [apPat, apMat].filter(Boolean).join(' ');

    return {
      suscriptor_nombre: (nombre || '').trim(),
      monto: (s.monto != null) ? s.monto : null,
      fecha_contrato: s.fecha_contrato || s.fecha_firma || s.fecha_registro || null,
      folio: s.folio_cip || s.folio || s.folio_solicitud || s.folio_caratula || null
    };
  }

  async function fetchFirmas(sid) {
    const r = await fetch(`${CONFIG.FIRMAS_API}?solicitud_id=${sid}&_=${Date.now()}`, { cache: 'no-store' });
    const j = await r.json().catch(() => ({}));
    if (!r.ok || !j?.ok) throw new Error(j?.error || 'No se pudieron obtener firmas');
    return j.firmas || [];
  }

  function principalDeFirmas(firmas) {
    const U = s => (s || '').toString().trim().toUpperCase();
    return firmas.find(f =>
      U(f.documento_norm || f.documento) === 'CONTRATO' &&
      U(f.firmante_norm || f.firmante) === 'PRESTATARIO' &&
      Number(f.page || 1) === 1
    ) || {};
  }

  async function fetchMetaConcredito(sid) {
    try {
      const r = await fetch(`${CONFIG.META_CONCREDITO}?solicitud_id=${sid}&_=${Date.now()}`, { cache: 'no-store' });
      const j = await r.json().catch(() => null);
      const meta = j?.meta || j || {};
      return {
        fecha_base: (meta.fecha_base || meta.created_at || meta.updated_at || '').slice(0, 10) || null,
        monto_total_pagar: Number(meta.monto_total_pagar ?? meta.monto_total ?? meta.monto_total_pago ?? 0) || null,
        monto_linea_credito: Number(meta.monto_linea_credito ?? 0) || null
      };
    } catch {
      return { fecha_base: null, monto_total_pagar: null, monto_linea_credito: null };
    }
  }

  async function cargarFechaVencimientoDesdeMeta(solicitudId) {
    const url = `${CONFIG.META_CONCREDITO}?solicitud_id=${encodeURIComponent(solicitudId)}&_=${Date.now()}`;
    const r = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
    const j = await r.json().catch(() => null);
    const meta = j?.meta || j || {};
    const iso = (
      meta.fecha_limite_pago ||
      meta.fechaMasUnAnio ||
      meta.fecha_mas_un_anio ||
      ''
    ).toString().slice(0, 10) || null;

    return {
      fecha_venc_iso: iso,
      fecha_venc_larga: iso ? formatearFechaLargaMX(iso) : '—'
    };
  }

  async function ensurePagareDate(sid) {
    try {
      const r = await fetch(`${CONFIG.PAGARE_API}?solicitud_id=${encodeURIComponent(sid)}&_=${Date.now()}`, { cache: 'no-store' });
      const j = await r.json().catch(() => null);
      const isoBD = (r.ok && j && (j.fecha || j.fecha_firma)) ? String(j.fecha || j.fecha_firma).slice(0, 10) : null;
      if (isoBD) {
        try { localStorage.setItem(`pagare_fecha_firma_${sid}`, isoBD); } catch {}
        return isoBD;
      }
    } catch {}

    let iso = null;
    try { iso = localStorage.getItem(`pagare_fecha_firma_${sid}`); } catch {}
    if (!iso) iso = todayISO();

    const fd = new FormData();
    fd.append('solicitud_id', String(sid));
    fd.append('fecha', iso);

    try {
      const r2 = await fetch(CONFIG.PAGARE_API, { method: 'POST', body: fd });
      const j2 = await r2.json().catch(() => null);
      const isoSrv = (r2.ok && j2 && j2.ok && j2.fecha) ? String(j2.fecha).slice(0, 10) : iso;
      try { localStorage.setItem(`pagare_fecha_firma_${sid}`, isoSrv); } catch {}
      return isoSrv;
    } catch {
      return iso;
    }
  }

  async function fetchReferencias(sid) {
    try {
      let url = `${CONFIG.REFERENCIAS_API}?solicitud_id=${encodeURIComponent(sid)}&_=${Date.now()}`;
      let r = await fetch(url, { cache: 'no-store' });
      let j = await r.json().catch(() => null);

      if (!r.ok || !j || j.success === false || !j.referencias) {
        url = `${CONFIG.REFERENCIAS_API}?folio=${encodeURIComponent(sid)}&_=${Date.now()}`;
        r = await fetch(url, { cache: 'no-store' });
        j = await r.json().catch(() => null);
      }

      if (!r.ok || !j) return [];
      if (Array.isArray(j)) return j;
      if (Array.isArray(j?.referencias)) return j.referencias;
      return [];
    } catch {
      return [];
    }
  }

  async function fetchReferenciaDeBeneficiario(sid, nombreBuscado) {
    const referencias = await fetchReferencias(sid);
    if (!Array.isArray(referencias) || referencias.length === 0) return null;

    const norm = s => (s || '').toString().trim().toUpperCase();
    const target = norm(nombreBuscado);

    const canon = r => ({
      nombre: r.nombre_completo || r.nombre || r.titular || '',
      parentesco: r.parentesco || r.relacion || '',
      celular: r.celular || r.cel || r.telefono_celular || '',
      telefono: r.telefono || r.tel || '',
      email: r.email || r.correo || r.mail || ''
    });

    let match = referencias.map(canon).find(rr => norm(rr.nombre) === target);
    if (!match) match = referencias.map(canon).find(rr => {
      const n = norm(rr.nombre);
      return n.startsWith(target) || target.startsWith(n);
    });
    if (!match) match = referencias.map(canon).find(rr => norm(rr.nombre).includes(target));

    return match || null;
  }

  async function fetchCodeudor(sid) {
    try {
      const url = `${CONFIG.CODEUDOR_API}?solicitud_id=${encodeURIComponent(sid)}&_=${Date.now()}`;
      const r = await fetch(url, { cache: 'no-store' });
      const j = await r.json().catch(() => null);
      if (!r.ok || !j || j.success === false || !j.datos) return null;

      const d = j.datos || {};
      const nombre = (d.nombre_completo && String(d.nombre_completo).trim()) ||
        [
          d.nombre, d.nombres, d.primer_nombre, d.segundo_nombre,
          d.apellido_paterno, d.apellido_materno, d.apellido1, d.apellido2
        ].map(v => (v || '').toString().trim()).filter(Boolean).join(' ').replace(/\s+/g, ' ').trim();

      const cel = (d.celular || d.telefono_celular || d.cel || '').toString().trim();
      const tel = (d.telefono || d.tel || '').toString().trim();
      const email = (d.email || d.correo || d.mail || '').toString().trim();
      const parentesco = (d.parentesco || d.relacion || '').toString().trim();

      if (!nombre) return null;
      return { nombre, cel, tel, email, parentesco };
    } catch {
      return null;
    }
  }

  function fillBeneficiarioSection({ nombre, telefono, email, parentesco }) {
    const setText = (id, v) => {
      const el = document.getElementById(id);
      if (el) el.textContent = v ?? '';
    };

    setText('benef_nombre', nombre || '');
    setText('benef_tel', telefono || '----------');
    setText('benef_parentesco', parentesco || '');

    const raw = (email || 'correo@direccion.com').trim();
    const visible = raw.toLowerCase();

    const a = document.getElementById('benef_mailto');
    const b = document.getElementById('benef_mail');

    if (a) {
      a.href = 'mailto:' + raw;
      a.textContent = visible;
    }
    if (b) {
      b.textContent = visible;
    }
  }

  /* =========================
     MÓDULO INE
     ========================= */
  const INE = {
    state: Object.fromEntries(CONFIG.VIEW_IDS.map(id => [id, {
      el: null,
      vp: null,
      scale: 1,
      tx: 0,
      ty: 0,
      angle: 0,
      dragging: false,
      lastX: 0,
      lastY: 0
    }])),

    findControlsFor(id) {
      return document.querySelector(`.ine-controls[data-target="${id}"]`);
    },

    applyTransform(id) {
      const s = this.state[id];
      if (!s?.el) return;
      s.el.style.transform = `translate(${s.tx}px, ${s.ty}px) rotate(${s.angle}deg) scale(${s.scale})`;
      const r = this.findControlsFor(id)?.querySelector('.zoom-range');
      if (r && +r.value !== s.scale) r.value = s.scale.toFixed(3);
    },

    fitToViewport(id) {
      const s = this.state[id];
      const img = s.el;
      const vp = s.vp;
      if (!img || !vp || !img.naturalWidth || !img.naturalHeight) return;

      const angle = ((s.angle % 180) + 180) % 180;
      const swap = angle >= 45 && angle <= 135;
      const imgW = swap ? img.naturalHeight : img.naturalWidth;
      const imgH = swap ? img.naturalWidth : img.naturalHeight;
      const vw = vp.clientWidth;
      const vh = vp.clientHeight;

      s.scale = clamp(Math.min(vw / imgW, vh / imgH), CONFIG.MIN_SCALE, CONFIG.MAX_SCALE);
      s.tx = 0;
      s.ty = 0;
      this.applyTransform(id);
    },

    resetImage(id) {
      Object.assign(this.state[id], { scale: 1, tx: 0, ty: 0, angle: 0 });
      this.applyTransform(id);
    },

    zoomAt(id, factor, center) {
      const s = this.state[id];
      const prev = s.scale;
      const next = clamp(prev * factor, CONFIG.MIN_SCALE, CONFIG.MAX_SCALE);
      if (next === prev) return;

      if (center) {
        const rect = s.vp.getBoundingClientRect();
        const cx = center.clientX - rect.left - s.vp.clientWidth / 2;
        const cy = center.clientY - rect.top - s.vp.clientHeight / 2;
        const k = next / prev;
        s.tx = (s.tx - cx) * k + cx;
        s.ty = (s.ty - cy) * k + cy;
      }

      s.scale = next;
      this.applyTransform(id);
    },

    onPointerDown(id, e) {
      const s = this.state[id];
      s.dragging = true;
      s.lastX = e.clientX;
      s.lastY = e.clientY;
      try { s.vp.setPointerCapture(e.pointerId); } catch {}
    },

    onPointerMove(id, e) {
      const s = this.state[id];
      if (!s.dragging) return;
      s.tx += e.clientX - s.lastX;
      s.ty += e.clientY - s.lastY;
      s.lastX = e.clientX;
      s.lastY = e.clientY;
      this.applyTransform(id);
    },

    onPointerUp(id, e) {
      const s = this.state[id];
      s.dragging = false;
      try { s.vp.releasePointerCapture(e.pointerId); } catch {}
    },

    rotate(id, dir) {
      this.state[id].angle = (this.state[id].angle + (dir > 0 ? 90 : -90)) % 360;
      this.fitToViewport(id);
    },

    loadFileTo(id, file) {
      if (!file) return;
      const s = this.state[id];
      const url = URL.createObjectURL(file);
      s.el.onload = () => {
        this.resetImage(id);
        this.fitToViewport(id);
        URL.revokeObjectURL(url);
      };
      s.el.src = url;
    },

    renderViewportToCanvas(id) {
      const s = this.state[id];
      const img = s.el;
      const vp = s.vp;
      if (!img || !img.complete || !img.naturalWidth) return null;

      const w = vp.clientWidth;
      const h = vp.clientHeight;
      const dpr = window.devicePixelRatio || 1;

      const cv = document.createElement('canvas');
      cv.width = Math.round(w * dpr);
      cv.height = Math.round(h * dpr);

      const ctx = cv.getContext('2d');
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.translate(w / 2, h / 2);
      ctx.translate(s.tx, s.ty);
      ctx.rotate(s.angle * Math.PI / 180);
      ctx.scale(s.scale, s.scale);
      ctx.drawImage(img, -img.naturalWidth / 2, -img.naturalHeight / 2);

      return cv;
    },

    downloadBlob(name, blob) {
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = name;
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    },

    exportPNGs() {
      CONFIG.VIEW_IDS.forEach(id => {
        const cv = this.renderViewportToCanvas(id);
        if (cv) {
          cv.toBlob(b => b && this.downloadBlob(`INE_${id}.png`, b), 'image/png', 1.0);
        }
      });
    },

    exportPDF() {
      if (!window.jspdf?.jsPDF) {
        alert('Falta jsPDF para exportar PDF.');
        return;
      }

      const { jsPDF } = window.jspdf;
      const pdf = new jsPDF({ unit: 'mm', format: 'a4', compress: true });
      const canvases = CONFIG.VIEW_IDS.map(id => this.renderViewportToCanvas(id)).filter(Boolean);

      if (!canvases.length) {
        alert('No hay imágenes para exportar.');
        return;
      }

      const ML = 14, TOP = 18, GAP = 12, W = 83, H = 53;
      canvases.forEach((cv, i) => {
        pdf.addImage(cv.toDataURL('image/png', 1.0), 'PNG', i === 0 ? ML : ML + W + GAP, TOP, W, H, undefined, 'FAST');
      });

      pdf.save('INE_copias.pdf');
    },

    swapSides() {
      const leftId = 'ineFront';
      const rightId = 'ineBack';
      const leftImg = document.getElementById(leftId);
      const rightImg = document.getElementById(rightId);
      if (!leftImg || !rightImg) return;

      const tmpSrc = leftImg.src;
      leftImg.src = rightImg.src;
      rightImg.src = tmpSrc;

      const tmp = { ...this.state[leftId] };
      this.state[leftId] = { ...this.state[rightId], el: leftImg, vp: leftImg.closest('.copy-viewport') };
      this.state[rightId] = { ...tmp, el: rightImg, vp: rightImg.closest('.copy-viewport') };

      this.applyTransform(leftId);
      this.applyTransform(rightId);
      this.fitToViewport(leftId);
      this.fitToViewport(rightId);
    },

    centerCropToRatio(cv, ratio = CONFIG.TARGET_RATIO) {
      const w = cv.width, h = cv.height, r = w / h;
      let cw, ch, sx, sy;
      if (r > ratio) {
        ch = h;
        cw = Math.round(h * ratio);
        sx = Math.round((w - cw) / 2);
        sy = 0;
      } else {
        cw = w;
        ch = Math.round(w / ratio);
        sx = 0;
        sy = Math.round((h - ch) / 2);
      }
      const out = document.createElement('canvas');
      out.width = cw;
      out.height = ch;
      out.getContext('2d').drawImage(cv, sx, sy, cw, ch, 0, 0, cw, ch);
      return out;
    },

    setINEFromCanvas(id, cv, tryDetect = true) {
      const out = tryDetect ? this.centerCropToRatio(cv) : cv;
      const dataURL = out.toDataURL('image/png', 1.0);
      const img = document.getElementById(id);
      if (!img) return;

      const onImgLoad = () => {
        if (this.state[id]) {
          this.state[id].scale = 1;
          this.state[id].tx = 0;
          this.state[id].ty = 0;
        }
        this.fitToViewport(id);
        img.removeEventListener('load', onImgLoad);
      };

      img.addEventListener('load', onImgLoad);
      img.src = dataURL;
    },

    async renderPdfPage(pdf, n, scale = 2.5) {
      const page = await pdf.getPage(n);
      const v = page.getViewport({ scale });
      const cv = document.createElement('canvas');
      cv.width = Math.ceil(v.width);
      cv.height = Math.ceil(v.height);
      await page.render({
        canvasContext: cv.getContext('2d', { alpha: false }),
        viewport: v
      }).promise;
      return cv;
    },

    async handlePdfFile(file) {
      const buf = await file.arrayBuffer();
      const pdf = await pdfjsLib.getDocument({ data: buf }).promise;

      if (pdf.numPages >= 1) {
        const p1 = await this.renderPdfPage(pdf, 1, 2.5);
        this.setINEFromCanvas('ineFront', p1, false);
      }
      if (pdf.numPages >= 2) {
        const p2 = await this.renderPdfPage(pdf, 2, 2.5);
        this.setINEFromCanvas('ineBack', p2, false);
      } else if (pdf.numPages === 1) {
        const p1 = await this.renderPdfPage(pdf, 1, 2.5);
        this.setINEFromCanvas('ineBack', p1, false);
      }
    },

    getINECanvas(lado) {
      const selectorsImg = [
        `#${lado === 'front' ? 'ineFront' : 'ineBack'}`,
        `.ine-${lado} img`,
        `.ine_${lado} img`
      ];

      for (const sel of selectorsImg) {
        const img = document.querySelector(sel);
        if (img instanceof HTMLImageElement && img.complete && (img.naturalWidth || 0) > 0) {
          const c = document.createElement('canvas');
          c.width = img.naturalWidth;
          c.height = img.naturalHeight;
          const ctx = c.getContext('2d');
          ctx.drawImage(img, 0, 0, c.width, c.height);
          return c;
        }
      }

      return null;
    },

    async subirINEImagen(solicitudId, lado, canvas) {
      const blob = await new Promise(res => canvas.toBlob(b => res(b), 'image/jpeg', 0.92));
      if (!blob) throw new Error('No se pudo generar blob');
      if (blob.size > 2 * 1024 * 1024) throw new Error('La imagen supera 2MB');

      const fd = new FormData();
      fd.append('solicitud_id', String(solicitudId));
      fd.append('lado', lado);
      fd.append('img', blob, `ine_${lado}.jpg`);

      const r = await fetch(CONFIG.INE_UPLOAD, { method: 'POST', body: fd });
      const j = await r.json().catch(() => null);
      if (!r.ok || !j?.ok) throw new Error(j?.msg || 'Falla al subir INE');
      return j;
    },

    async guardarINEsEnServidor() {
      const sid = sidFromUrl();
      if (!sid) {
        alert('Falta ?solicitud_id');
        return;
      }

      const cvFront = this.getINECanvas('front');
      const cvBack = this.getINECanvas('back');

      if (!cvFront && !cvBack) {
        alert('No hay imágenes de INE visibles para guardar.');
        return;
      }

      try {
        if (cvFront) await this.subirINEImagen(sid, 'front', cvFront);
        if (cvBack) await this.subirINEImagen(sid, 'back', cvBack);

        this.cargarINEsDesdeServidor();
        alert('INE guardada correctamente.');
      } catch (err) {
        console.error(err);
        alert('Error guardando INE: ' + (err?.message || err));
      }
    },

    cargarINEsDesdeServidor() {
      const sid = sidFromUrl();
      if (!sid) return;

      const frontImg = document.getElementById('ineFront');
      const backImg = document.getElementById('ineBack');
      const ts = Date.now();

      if (frontImg) frontImg.src = `${CONFIG.INE_GET}?solicitud_id=${encodeURIComponent(sid)}&lado=front&_=${ts}`;
      if (backImg) backImg.src = `${CONFIG.INE_GET}?solicitud_id=${encodeURIComponent(sid)}&lado=back&_=${ts}`;
    },

    bindOne(id) {
      const s = this.state[id];
      s.el = document.getElementById(id);
      s.vp = s.el?.closest('.copy-viewport');
      if (!s.el || !s.vp) return;

      s.vp.addEventListener('wheel', ev => {
        ev.preventDefault();
        this.zoomAt(id, Math.sign(ev.deltaY) > 0 ? (1 - CONFIG.ZOOM_STEP_WHEEL) : (1 + CONFIG.ZOOM_STEP_WHEEL), ev);
      }, { passive: false });

      s.vp.addEventListener('pointerdown', ev => this.onPointerDown(id, ev));
      s.vp.addEventListener('pointermove', ev => this.onPointerMove(id, ev));
      s.vp.addEventListener('pointerup', ev => this.onPointerUp(id, ev));
      s.vp.addEventListener('pointerleave', ev => this.onPointerUp(id, ev));

      const c = this.findControlsFor(id);
      if (!c) return;

      c.querySelector('.ine-file')?.addEventListener('change', e => this.loadFileTo(id, e.target.files?.[0]));
      c.querySelector('.reset')?.addEventListener('click', () => this.resetImage(id));
      c.querySelector('.zoom-in')?.addEventListener('click', () => this.zoomAt(id, 1 + CONFIG.ZOOM_STEP_BTN));
      c.querySelector('.zoom-out')?.addEventListener('click', () => this.zoomAt(id, 1 - CONFIG.ZOOM_STEP_BTN));
      c.querySelector('.rotate-left')?.addEventListener('click', () => this.rotate(id, -1));
      c.querySelector('.rotate-right')?.addEventListener('click', () => this.rotate(id, +1));
      c.querySelector('.zoom-range')?.addEventListener('input', e => {
        this.state[id].scale = clamp(+e.target.value || 1, CONFIG.MIN_SCALE, CONFIG.MAX_SCALE);
        this.applyTransform(id);
      });
      c.querySelector('.nudge-up')?.addEventListener('click', () => {
        this.state[id].ty -= CONFIG.NUDGE;
        this.applyTransform(id);
      });
      c.querySelector('.nudge-down')?.addEventListener('click', () => {
        this.state[id].ty += CONFIG.NUDGE;
        this.applyTransform(id);
      });
      c.querySelector('.nudge-left')?.addEventListener('click', () => {
        this.state[id].tx -= CONFIG.NUDGE;
        this.applyTransform(id);
      });
      c.querySelector('.nudge-right')?.addEventListener('click', () => {
        this.state[id].tx += CONFIG.NUDGE;
        this.applyTransform(id);
      });

      this.applyTransform(id);
    },

    bindEvents() {
      CONFIG.VIEW_IDS.forEach(id => this.bindOne(id));

      document.getElementById('btn-export-png')?.addEventListener('click', () => this.exportPNGs());
      document.getElementById('btn-export-pdf')?.addEventListener('click', () => this.exportPDF());
      document.getElementById('btn-swap-ine')?.addEventListener('click', () => this.swapSides());

      document.getElementById('btn-auto-crop')?.addEventListener('click', () => {
        CONFIG.VIEW_IDS.forEach(id => {
          const s = this.state[id];
          if (!s?.el || !s.el.naturalWidth) return;

          const cv = document.createElement('canvas');
          cv.width = s.el.naturalWidth;
          cv.height = s.el.naturalHeight;
          cv.getContext('2d').drawImage(s.el, 0, 0);
          const det = this.centerCropToRatio(cv, CONFIG.TARGET_RATIO);
          this.setINEFromCanvas(id, det, false);
        });
      });

      const pdfInput = document.getElementById('inePdfInput');
      if (pdfInput) {
        pdfInput.addEventListener('change', async e => {
          const f = e.target.files?.[0];
          if (!f) return;
          if (f.type !== 'application/pdf') {
            alert('Selecciona un archivo PDF.');
            return;
          }

          const prev = pdfInput.disabled;
          pdfInput.disabled = true;
          try {
            await this.handlePdfFile(f);
          } catch (err) {
            console.error('Error procesando PDF:', err);
            alert('No se pudo procesar el PDF.');
          } finally {
            pdfInput.disabled = prev;
          }
        });
      }

      window.addEventListener('resize', () => CONFIG.VIEW_IDS.forEach(id => this.fitToViewport(id)));
      document.getElementById('btn-guardar-ine')?.addEventListener('click', () => this.guardarINEsEnServidor());
    }
  };

  /* =========================
     DATOS DEL PAGARÉ
     ========================= */
  async function cargarDatosPagare() {
    const sid = sidFromUrl();
    if (!sid) {
      console.warn('[pagaré] Falta ?solicitud_id');
      return;
    }

    const [solRes, firmasRes, metaRes, codeudorRes] = await Promise.allSettled([
      fetchSolicitud(sid),
      fetchFirmas(sid),
      fetchMetaConcredito(sid),
      fetchCodeudor(sid)
    ]);

    let sol = {};
    if (solRes.status === 'fulfilled') {
      try { sol = mapSolicitud(solRes.value); } catch {}
    }

    setFolioEverywhere(sol.folio || '');

    const firmas = (firmasRes.status === 'fulfilled') ? firmasRes.value : [];
    const principal = principalDeFirmas(firmas);

    const meta = (metaRes.status === 'fulfilled')
      ? (metaRes.value || {})
      : { fecha_base: null, monto_total_pagar: null, monto_linea_credito: null };

    const fecha_firma_iso = await ensurePagareDate(sid);
    const placeEl = document.getElementById('fecha_firma_mark');
    if (placeEl) placeEl.textContent = fechaLargaLocal(fecha_firma_iso);

    let fecha_venc_larga = '—';
    try {
      const vencMeta = await cargarFechaVencimientoDesdeMeta(sid);
      if (vencMeta?.fecha_venc_iso) {
        fecha_venc_larga = vencMeta.fecha_venc_larga;
      } else {
        const vencDate = addYearsSameDayLocal(meta.fecha_base || fecha_firma_iso, 1);
        fecha_venc_larga = fechaLargaLocal(vencDate);
      }
    } catch {
      const vencDate = addYearsSameDayLocal(meta.fecha_base || fecha_firma_iso, 1);
      fecha_venc_larga = fechaLargaLocal(vencDate);
    }

    let suscriptor_nombre =
      sol.suscriptor_nombre ||
      principal.suscriptor_nombre ||
      principal.nombre_prestatario ||
      principal.nombre ||
      localStorage.getItem('pagare.suscriptor_nombre') ||
      '';

    const firmasNombre = principal.nombre_prestatario || principal.suscriptor_nombre || '';
    if (suscriptor_nombre && firmasNombre) {
      const wSol = suscriptor_nombre.trim().split(/\s+/).length;
      const wFir = firmasNombre.trim().split(/\s+/).length;
      if (wSol <= 2 && wFir > wSol) suscriptor_nombre = firmasNombre;
    }

    const montoPagare =
      (meta.monto_total_pagar ?? null) ??
      (sol.monto ?? null) ??
      Number(localStorage.getItem('contrato_monto') || 0);

    const data = {
      suscriptor_nombre,
      monto_fmt: fmtMoney(montoPagare),
      fecha_venc_larga,
      obligado: { nombre: '', cel: '', tel: '', email: '', parentesco: '' }
    };

    try {
      if (data.suscriptor_nombre) localStorage.setItem('pagare.suscriptor_nombre', data.suscriptor_nombre);
    } catch {}

    let codeudor = null;
    if (codeudorRes.status === 'fulfilled') codeudor = codeudorRes.value;

    if (codeudor) {
      data.obligado = { ...data.obligado, ...codeudor };
    } else {
      data.obligado = {
        nombre: principal.beneficiario_nombre || '',
        cel: principal.beneficiario_celular || '',
        tel: principal.beneficiario_telefono || '',
        email: principal.beneficiario_email || '',
        parentesco: principal.beneficiario_parentesco || ''
      };

      if (data.obligado.nombre) {
        const ref = await fetchReferenciaDeBeneficiario(sid, data.obligado.nombre);
        if (ref) {
          if (!data.obligado.parentesco && ref.parentesco) data.obligado.parentesco = ref.parentesco;
          if ((!data.obligado.cel && !data.obligado.tel) && (ref.celular || ref.telefono)) {
            data.obligado.cel = ref.celular || '';
            data.obligado.tel = ref.telefono || '';
          }
          if (!data.obligado.email && ref.email) data.obligado.email = ref.email;
        }
      }
    }

    const telObligado = pickBestPhone(data.obligado.cel, data.obligado.tel);

    let beneficiario = {
      nombre: principal.beneficiario_nombre || '',
      cel: principal.beneficiario_celular || '',
      tel: principal.beneficiario_telefono || '',
      email: principal.beneficiario_email || '',
      parentesco: principal.beneficiario_parentesco || ''
    };

    if (beneficiario.nombre) {
      const refB = await fetchReferenciaDeBeneficiario(sid, beneficiario.nombre);
      if (refB) {
        if (!beneficiario.parentesco && refB.parentesco) beneficiario.parentesco = refB.parentesco;
        if ((!beneficiario.cel && !beneficiario.tel) && (refB.celular || refB.telefono)) {
          beneficiario.cel = refB.celular || '';
          beneficiario.tel = refB.telefono || '';
        }
        if (!beneficiario.email && refB.email) beneficiario.email = refB.email;
      }
    }

    if (codeudor) {
      if (!beneficiario.nombre) beneficiario.nombre = codeudor.nombre || beneficiario.nombre;
      if (!beneficiario.parentesco) beneficiario.parentesco = codeudor.parentesco || beneficiario.parentesco;
      if (!beneficiario.email) beneficiario.email = codeudor.email || '';
      if (!beneficiario.cel && !beneficiario.tel) {
        beneficiario.cel = codeudor.cel || '';
        beneficiario.tel = codeudor.tel || '';
      }
    }

    const telBenef = pickBestPhone(beneficiario.cel, beneficiario.tel);

    put('[Juvenal Martínez Lopez]', data.suscriptor_nombre);
    put('[Nombre del Cliente]', data.suscriptor_nombre);
    put('$[000,000.00]', data.monto_fmt);
    put('[Fecha de Vencimiento]', fecha_venc_larga);
    put('[MN]', 'MN');

    const montoNum = Number(montoPagare || 0);
    const { letras, centavos } = aPesosEnLetras(montoNum);
    const montoLetrasEl = document.getElementById('montoLetras');
    if (montoLetrasEl) {
      montoLetrasEl.textContent = `(${primeraMayuscula(letras)} pesos ${centavos}/100 M.N.) `;
    }

    const elNombre = document.getElementById('obligado_nombre');
    const elTel = document.getElementById('obligado_tel');
    const elMailLink = document.getElementById('obligado_mail_link');
    const elMailText = document.getElementById('obligado_mail');
    const elParentesco = document.getElementById('obligado_parentesco');

    if (elNombre) elNombre.textContent = data.obligado.nombre || '';
    if (elTel) elTel.textContent = telObligado;
    if (elParentesco) elParentesco.textContent = data.obligado.parentesco || '';

    const emailObligado = (data.obligado.email || '').trim() || 'correo@direccion.com';
    if (elMailLink) elMailLink.href = `mailto:${emailObligado}`;
    if (elMailText) elMailText.textContent = emailObligado.toLowerCase();

    const osNombre = document.getElementById('osNombreFirma');
    if (osNombre) osNombre.textContent = data.obligado.nombre || data.suscriptor_nombre || osNombre.textContent;

    fillBeneficiarioSection({
      nombre: beneficiario.nombre || '',
      telefono: telBenef,
      email: beneficiario.email || '',
      parentesco: beneficiario.parentesco || ''
    });
  }

  /* =========================
     EVENTOS GENERALES
     ========================= */
  function bindGeneralEvents() {
    document.getElementById('btn-print')?.addEventListener('click', () => window.print());

document.getElementById('btn-back')?.addEventListener('click', () => {
  window.location.href = `${BASE_URL}/index.php`;
});
  }

  /* =========================
     INIT
     ========================= */
  async function init() {
    if (!window.pdfjsLib || typeof pdfjsLib.getDocument !== 'function') {
      console.error('pdfjsLib no está disponible.');
    }

    INE.bindEvents();
    INE.cargarINEsDesdeServidor();
    bindGeneralEvents();
    await cargarDatosPagare();
  }

  document.addEventListener('DOMContentLoaded', init);
})();
</script>

<script>
window.addEventListener('beforeprint', () => {
  try {
    INE.fitToViewport('ineFront');
    INE.fitToViewport('ineBack');
  } catch(e) {
    console.warn('No se pudo reajustar INE antes de imprimir', e);
  }
});

window.addEventListener('afterprint', () => {
  try {
    INE.fitToViewport('ineFront');
    INE.fitToViewport('ineBack');
  } catch(e) {}
});
</script>
</body>
</html>
