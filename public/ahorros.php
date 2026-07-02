<?php
declare(strict_types=1);

// ✅ sesión SIEMPRE antes de usar $_SESSION
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// ===============================
// 1) TOMAR ID DEL ASESOR (robusto)
// ===============================
$asesorSess = $_SESSION['asesor'] ?? [];

$asesorId = (int)(
  $asesorSess['id_asesor']
  ?? $asesorSess['id']
  ?? $asesorSess['asesor_id']
  ?? $_SESSION['asesor_id']
  ?? $_SESSION['user_id']
  ?? 0
);

// ===============================
// 2) SI NO HAY ARREGLO COMPLETO,
//    TRAER DATOS DE BD
// ===============================
$asesorNombre = '';
$asesorCorreo = '';
$asesorTel    = '';
$asesorRfc    = '';
$asesorDom    = '';

if (is_array($asesorSess) && !empty($asesorSess)) {
  // intenta desde sesión
  $asesorNombre = trim((string)($asesorSess['nombre_completo'] ?? $asesorSess['nombre'] ?? ''));
  $asesorCorreo = trim((string)($asesorSess['email'] ?? $asesorSess['correo'] ?? ''));
  $asesorTel    = trim((string)($asesorSess['telefono'] ?? $asesorSess['tel'] ?? $asesorSess['celular'] ?? ''));
  $asesorRfc    = strtoupper(trim((string)($asesorSess['rfc'] ?? '')));
  $asesorDom    = trim((string)($asesorSess['direccion'] ?? $asesorSess['domicilio'] ?? $asesorSess['direccion_fiscal'] ?? ''));
}

// 🔥 si faltan datos clave, consulta BD
// 🔥 si falta cualquiera de estos campos, consulta BD
$necesitaBD = ($asesorId > 0) && (
  $asesorNombre === '' ||
  $asesorCorreo === '' ||
  $asesorTel    === '' ||
  $asesorRfc    === '' ||
  $asesorDom    === ''
);


if ($necesitaBD) {
  require_once __DIR__ . '/app/controllers/db/conexion.php'; // ✅ AJUSTA RUTA si tu conexion.php está en otro lado
  // si tu conexión está aquí: /sempiternal/public/app/controllers/db/conexion.php está bien.
  // Si es: /sempiternal/public/app/db/conexion.php entonces cambia la ruta.

  // ✅ en tu producción tu PK parece ser id_asesor (según tu captura)
  $st = $pdo->prepare("
    SELECT
      id_asesor,
      nombre,
      email,
      telefono,
      rfc,
      direccion
    FROM asesores
    WHERE id_asesor = :id
    LIMIT 1
  ");
  $st->execute([':id' => $asesorId]);
  $a = $st->fetch(PDO::FETCH_ASSOC);

  if ($a) {
    $asesorNombre = trim((string)($a['nombre'] ?? ''));
    $asesorCorreo = trim((string)($a['email'] ?? ''));
    $asesorTel    = trim((string)($a['telefono'] ?? ''));
    $asesorRfc    = strtoupper(trim((string)($a['rfc'] ?? '')));
    $asesorDom    = trim((string)($a['direccion'] ?? ''));
  }
}

// ===============================
// 3) EXPORTAR A JS
// ===============================
?>
<script>
window.ASESOR = <?= json_encode([
  'id'       => $asesorId,
  'nombre'   => $asesorNombre,
  'correo'   => $asesorCorreo,
  'telefono' => $asesorTel,
  'rfc'      => $asesorRfc,
  'domicilio'=> $asesorDom,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

console.log('🧠 ASESOR (PHP->JS):', window.ASESOR);
console.log('🧾 SESSION asesor:', <?= json_encode($_SESSION['asesor'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>);
console.log('🧾 SESSION ids:', {
  asesor_id: <?= json_encode($_SESSION['asesor_id'] ?? null) ?>,
  user_id: <?= json_encode($_SESSION['user_id'] ?? null) ?>
});
</script>




<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Contrato de Préstamo de Dinero o Mutuo</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
* {
  box-sizing: border-box;
}

html,
body {
  margin: 0;
  padding: 0;
}

body {
  background: #f4f4f4;
  font-family: "Aptos", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
}

/* =========================
   VISTA EN PANTALLA
========================= */

.page-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
  padding: 20px 0;
}

.page {
  width: 216mm;
  height: 279mm;
  background: #ffffff;
  padding: 7mm 30mm 9mm 30mm;
  position: relative;
  overflow: hidden;
  box-shadow: 0 0 10px rgba(0,0,0,0.15);
  font-size: 8.5pt;
  line-height: 1.12;
  margin: 0 auto 14px auto;
  page-break-after: always;
  break-after: page;
}

.page:last-child {
  page-break-after: auto;
  break-after: auto;
}

.page-inner-border {
  position: absolute;
  top: 6mm;
  bottom: 6mm;
  left: 10mm;
  right: 10mm;
  border: 4px solid #cfa72f;
  pointer-events: none;
  z-index: 0;
}

.content {
  position: relative;
  z-index: 1;
  width: 100%;
  margin: 0 auto;
}

/* =========================
   LOGO
========================= */

.logo {
  text-align: center;
  height: 18mm;
  margin: 2mm auto -5mm auto;
  overflow: hidden;
}

.logo img {
  display: block;
  height: 29mm;
  width: auto;
  max-width: 62mm;
  margin: -6mm auto -6mm auto;
  object-fit: contain;
}

.logo + h1 {
  margin-top: -1mm !important;
}

/* =========================
   TEXTOS
========================= */

.page .content h1 {
  font-size: 9.8pt;
  text-align: center;
  text-transform: uppercase;
  margin: 0 0 2.5mm 0;
  font-weight: bold;
  line-height: 1.1;
}

h2,
.section-title {
  font-size: 9pt;
  text-align: center;
  text-transform: uppercase;
  margin: 3mm 0 1.5mm 0;
  font-weight: bold;
  line-height: 1.1;
}

p {
  font-size: 8.5pt;
  text-align: justify;
  margin: 0 0 1.8mm 0;
  line-height: 1.12;
}

ul,
ol {
  margin: 0 0 2mm 10mm;
  padding: 0;
}

li {
  font-size: 8.5pt;
  margin-bottom: 1.3mm;
  text-align: justify;
  line-height: 1.12;
}

/* =========================
   FOLIO Y PÁGINA
========================= */

.folio {
  position: absolute;
  top: 8mm;
  right: 30mm;
  font-size: 8.3pt;
  font-weight: bold;
  z-index: 2;
}

.page-number {
  position: absolute;
  bottom: 7mm;
  right: 30mm;
  font-size: 7pt;
  z-index: 2;
}

/* =========================
   FIRMAS
========================= */

.firmas-fila {
  display: flex;
  justify-content: space-between;
  margin-top: 8mm;
}

.firma-col {
  width: 48%;
  text-align: center;
  font-size: 8.5pt;
}

.firma-titulo {
  font-weight: bold;
  margin-bottom: 4mm;
}

.firma-espacio {
  height: 12mm;
}

.firma-texto {
  margin-top: 0;
}

.firma-nombre {
  font-weight: bold;
  text-transform: uppercase;
}

/* =========================
   BOTONES FLOTANTES
========================= */

.acciones {
  position: fixed;
  bottom: 18px;
  right: 18px;
  display: flex;
  gap: 6px;
  padding: 6px 10px;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 999px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.25);
  border: 1px solid #cfa72f;
  z-index: 2000;
}

.btn-accion {
  border: none;
  padding: 6px 14px;
  font-size: 9pt;
  font-family: "Times New Roman", serif;
  cursor: pointer;
  border-radius: 999px;
  background: linear-gradient(135deg, #cfa72f, #f0d472);
  color: #333;
  font-weight: bold;
  display: flex;
  align-items: center;
  gap: 4px;
}

.btn-accion:hover {
  filter: brightness(1.05);
  box-shadow: 0 1px 4px rgba(0,0,0,0.25);
}

.btn-accion:active {
  transform: scale(0.97);
}

/* =========================
   FORMULARIO
========================= */

.card {
  background: #ffffff;
  border: 1px solid #cfa72f;
  border-radius: 6px;
  padding: 15px 20px;
  max-width: 700px;
  margin-bottom: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.card-form {
  max-width: 900px;
  margin: 30px auto;
}

.card-form h1 {
  font-size: 20px;
  margin-bottom: 10px;
}

.form-row {
  display: flex;
  gap: 10px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.form-group {
  flex: 1;
  min-width: 150px;
}

label {
  display: block;
  font-size: 13px;
  margin-bottom: 3px;
}

input,
select {
  width: 100%;
  padding: 5px 6px;
  font-size: 13px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-family: inherit;
}

button {
  padding: 7px 16px;
  font-size: 13px;
  border-radius: 999px;
  border: 1px solid #cfa72f;
  background: linear-gradient(135deg, #cfa72f, #f0d472);
  cursor: pointer;
  font-weight: bold;
}

button:hover {
  filter: brightness(1.05);
}

.resumen-item {
  margin-bottom: 4px;
  font-size: 14px;
}

.resumen-item span {
  font-weight: bold;
}

/* =========================
   CALENDARIO
========================= */

.page-calendario {
  padding: 6mm 18mm 8mm 18mm;
}

.page-calendario .page-inner-border {
  left: 10mm;
  right: 10mm;
  top: 6mm;
  bottom: 6mm;
}

.page-calendario .content {
  width: 100%;
  margin: 0 auto;
  text-align: center;
}

.page-calendario .logo {
  height: 18mm;
  margin: 2mm auto -7mm auto;
  overflow: hidden;
}

.page-calendario .logo img {
  height: 27mm;
  max-width: 62mm;
  margin: -6mm auto -6mm auto;
}

.page-calendario .section-title {
  margin-top: 0;
  margin-bottom: 2mm;
}

.cal-wrap {
  width: 100%;
  max-width: 160mm;
  margin: 0 auto;
  padding: 0;
  text-align: center;
}

.cal-title {
  text-align: center;
  font-size: 11pt;
  font-weight: 800;
  color: #1a237e;
  letter-spacing: .4px;
  margin: 0 0 1.5mm 0;
  padding-bottom: 1mm;
  border-bottom: 2px solid #d4af37;
}

.cal-head {
  text-align: center;
  font-size: 8pt;
  color: #334155;
  margin: 0 0 2mm 0;
}

.tabla-52-wrap {
  width: 150mm;
  max-width: 150mm;
  margin: 0 auto;
  border: none;
  border-radius: 8px;
  overflow: hidden;
  background: #fff;
  box-sizing: border-box;
  position: relative;
  box-shadow: 0 8px 24px rgba(0,0,0,0.08);
}

.tabla-52-wrap::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg,#d4af37,#f0d472,#d4af37);
}

.tabla-52 {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 6.5pt;
  line-height: 1;
  table-layout: fixed;
  font-family: "Segoe UI", system-ui, sans-serif;
}

.tabla-52 thead {
  background: linear-gradient(135deg,#1a237e 0%,#283593 100%);
}

.tabla-52 thead th {
  color: #fff;
  font-weight: 700;
  font-size: 6.2pt;
  padding: 3px 4px;
  text-align: center;
  border: none;
  text-transform: uppercase;
  letter-spacing: .4px;
}

.tabla-52 tbody td {
  font-size: 6.3pt;
  padding: 1px 4px;
  color: #1f2937;
  border-bottom: 1px solid rgba(0,0,0,0.06);
  vertical-align: middle;
  white-space: nowrap;
  line-height: 1;
}

.tabla-52 tbody td:not(:last-child) {
  border-right: 1px solid rgba(0,0,0,0.06);
}

.tabla-52 tbody tr:nth-child(even) {
  background: #f8fafc;
}

.tabla-52 tbody tr:nth-child(odd) {
  background: #ffffff;
}

.td-sem {
  width: 12%;
  text-align: center;
  font-weight: 800;
  color: #1a237e;
}

.td-fecha {
  width: 30%;
  text-align: center;
  color: #475569;
}

.td-monto {
  width: 28%;
  text-align: right;
  color: #059669;
  font-weight: 600;
  padding-right: 10px;
}

.td-rend {
  width: 30%;
  text-align: right;
  color: #d97706;
  font-weight: 800;
  padding-right: 10px;
  position: relative;
}

.td-rend::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(180deg,#f0d472,transparent);
  opacity: .55;
}

.tabla-52 tbody tr:last-child,
.tabla-52 tbody tr.row-acumulado {
  background: linear-gradient(90deg,#fef3c7,#fef9c3) !important;
  font-weight: 800;
}

.tabla-52 tbody tr:last-child td,
.tabla-52 tbody tr.row-acumulado td {
  border-bottom: 2px solid #d4af37;
  color: #92400e;
  padding: 2px 5px;
}

.td-acum {
  text-transform: uppercase;
  letter-spacing: .6px;
  text-align: center !important;
}

.cal-foot {
  text-align: center;
  font-size: 7.5pt;
  color: #64748b;
  margin-top: 2mm;
  padding-top: 1.5mm;
  border-top: 1px solid rgba(0,0,0,0.10);
  font-style: italic;
}

/* =========================
   TABLA RESUMEN
========================= */

.tabla-resumen {
  width: 115mm;
  max-width: 115mm;
  margin: 2mm auto 0 auto;
  border-collapse: separate;
  border-spacing: 0;
  font-family: "Segoe UI", system-ui, sans-serif;
  font-size: 6.2pt;
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
}

.tabla-resumen tr {
  background: #ffffff;
}

.tabla-resumen tr:nth-child(even) {
  background: #f8fafc;
}

.tabla-resumen td {
  padding: 2px 6px;
  border-bottom: 1px solid rgba(0,0,0,0.08);
  color: #1f2937;
  line-height: 1;
}

.tabla-resumen td:first-child {
  text-transform: uppercase;
  letter-spacing: .3px;
  font-weight: 700;
  font-size: 6.2pt;
  color: #334155;
}

.tabla-resumen td:last-child {
  text-align: right;
  font-variant-numeric: tabular-nums;
  font-weight: 700;
  font-size: 6.8pt;
  color: #0f172a;
}

.tabla-resumen tr:last-child {
  background: linear-gradient(90deg,#fef3c7,#fef9c3);
}

.tabla-resumen tr:last-child td {
  border-bottom: none;
  font-size: 6pt;
  font-weight: 900;
  color: #92400e;
}

.tabla-resumen tr:last-child td:first-child {
  position: relative;
}

.tabla-resumen tr:last-child td:first-child::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: linear-gradient(180deg,#d4af37,#f0d472);
}

/* =========================
   PDF MODE HTML2PDF
========================= */

body.pdf-mode {
  margin: 0 !important;
  padding: 0 !important;
  background: #ffffff !important;
}

body.pdf-mode .acciones,
body.pdf-mode .card-form {
  display: none !important;
}

body.pdf-mode #contrato,
body.pdf-mode .page-wrapper {
  margin: 0 !important;
  padding: 0 !important;
  width: auto !important;
  display: block !important;
}

body.pdf-mode .page {
  width: 216mm !important;
  height: 279mm !important;
  min-height: 279mm !important;
  max-height: 279mm !important;
  margin: 0 !important;
  padding: 7mm 30mm 9mm 30mm !important;
  box-shadow: none !important;
  overflow: hidden !important;
  page-break-after: always !important;
  break-after: page !important;
}

body.pdf-mode .page:last-child {
  page-break-after: auto !important;
  break-after: auto !important;
}

body.pdf-mode .page-inner-border {
  display: block !important;
  top: 6mm !important;
  bottom: 6mm !important;
  left: 10mm !important;
  right: 10mm !important;
}

body.pdf-mode .page::before {
  content: none !important;
  display: none !important;
}

/* =========================
   IMPRESIÓN
========================= */

@media print {

  @page {
    size: Letter portrait;
    margin: 0;
  }

  html,
  body {
    margin: 0 !important;
    padding: 0 !important;
    background: #ffffff !important;
    width: auto !important;
    height: auto !important;
    overflow: visible !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  .acciones,
  .card-form,
  button {
    display: none !important;
  }

  #contrato,
  #contrato .page-wrapper {
    margin: 0 !important;
    padding: 0 !important;
    width: auto !important;
    max-width: none !important;
    display: block !important;
  }

  #contrato .page {
    width: 216mm !important;
    height: 275mm !important;
    min-height: 275mm !important;
    max-height: 275mm !important;

    margin: 0 auto !important;
    padding: 7mm 30mm 9mm 30mm !important;

    position: relative !important;
    overflow: hidden !important;
    background: #ffffff !important;
    box-shadow: none !important;

    page-break-after: always !important;
    break-after: page !important;
  }

  #contrato .page:last-child {
    page-break-after: auto !important;
    break-after: auto !important;
  }

  #contrato .page-inner-border {
    position: absolute !important;
    top: 6mm !important;
    bottom: 6mm !important;
  left: 6mm !important;   /* menos espacio a la izquierda */
  right: 6mm !important;   /* más espacio a la derecha para el margen de la impresora */
    width: auto !important;
    height: auto !important;
    border: 4px solid #cfa72f !important;
    pointer-events: none !important;
    z-index: 0 !important;
  }

  #contrato .page .content {
    position: relative !important;
    z-index: 1 !important;
    width: 100% !important;
    margin: 0 auto !important;
  }

  #contrato .folio {
    top: 8mm !important;
    right: 30mm !important;
  }

  #contrato .page-number {
    right: 30mm !important;
    bottom: 7mm !important;
  }

  #contrato .page::before {
    content: none !important;
    display: none !important;
  }
}
</style>



</head>

<body>

  <!-- BOTONES FLOTANTES -->
  <div class="acciones">
    <button class="btn-accion" onclick="imprimirContrato()">Imprimir</button>
    <button class="btn-accion" onclick="descargarPDF()">Descargar PDF</button>
    <button id="btn-editar"class="btn-accion"type="button"style="display:none;"onclick="activarEdicion()">Editar datos</button>
    <button type="button" class="btn-accion" onclick="volverListado()">Volver</button>
  </div>


<!-- FORMULARIO -->
<div class="card card-form">
  <h1 style="text-align:center; margin-top:0; margin-bottom:10px;">
    Captura de datos del Ahorrador
  </h1>

<form id="form-prestamo">
<input type="hidden" id="ahorro_id" value=""> 

    <div class="form-row">
      <div class="form-group">
        <label for="nombre">Nombre(s)</label>
        <input type="text" id="nombre" required>
      </div>

      <div class="form-group">
        <label for="ap_paterno">Apellido paterno</label>
        <input type="text" id="ap_paterno" required>
      </div>

      <div class="form-group">
        <label for="ap_materno">Apellido materno</label>
        <input type="text" id="ap_materno">
      </div>

      <div class="form-group">
        <label for="rfc">RFC</label>
        <input type="text" id="rfc" maxlength="13">
      </div>
    </div>

<div class="form-row">
  <div class="form-group">
    <label for="correo">Correo electrónico</label>
    <input
      type="email"
      id="correo"
      placeholder="ejemplo@correo.com"
      autocomplete="email"
    >
  </div>

  <div class="form-group">
    <label for="telefono">Teléfono</label>
    <input
      type="tel"
      id="telefono"
      placeholder="7221234567"
      inputmode="numeric"
      maxlength="15"
    >
  </div>
</div>


    <div class="form-row">
      <div class="form-group">
        <label for="cp">Código postal</label>
        <input type="text" id="cp">
      </div>

      <div class="form-group" style="flex:2">
        <label for="direccion">Dirección</label>
        <input type="text" id="direccion" placeholder="Calle, número, colonia, ciudad, estado">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="fecha_inicio_ahorro">Fecha de inicio de ahorro</label>
        <input type="date" id="fecha_inicio_ahorro" required>
      </div>

      <div class="form-group">
        <label for="monto_semanal">Monto semanal</label>
        <select id="monto_semanal" required>
          <option value="">Selecciona…</option>
          <option value="200">200</option>
          <option value="500">500</option>
          <option value="1000">1000</option>
        </select>
      </div>
    </div>

    <!-- BENEFICIARIO -->
<div class="form-row">
  <div class="form-group">
    <label for="beneficiario_nombre">Nombre del beneficiario</label>
    <input type="text" id="beneficiario_nombre" placeholder="Nombre completo">
  </div>

  <div class="form-group">
    <label for="beneficiario_parentesco">Parentesco</label>
    <input type="text" id="beneficiario_parentesco" placeholder="Ej. Esposa, Hijo, Madre">
  </div>
</div>

<div class="form-row">
  <div class="form-group">
    <label for="beneficiario_curp">CURP del beneficiario</label>
    <input type="text" id="beneficiario_curp" maxlength="18" placeholder="18 caracteres">
  </div>

  <div class="form-group">
    <label for="beneficiario_telefono">Teléfono del beneficiario</label>
    <input type="tel" id="beneficiario_telefono" maxlength="15" placeholder="7221234567" inputmode="numeric">
  </div>
</div>


    <button type="submit">Guardar y rellenar</button>
  </form>
</div>


  <!-- TODO EL CONTRATO (lo que va al PDF) -->
  <div id="contrato">
    <div class="page-wrapper">

    <!-- PRIMERA HOJA -->
    <div class="page page-1">
      <div class="page-inner-border"></div>

      <!-- FOLIO DEL CONTRATO -->
      <div class="folio">Folio: <span class="folio-contrato">—</span></div>


      <div class="content">

        <div class="logo">
          <img src="img/logo.png" alt="Logo CIP Financial México">
        </div>

        <h1>CONTRATO DE AHORRO FIJO</h1>

        <p>
          El presente contrato de ahorro fijo es celebrado en: C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán,
          Toluca, México. C.P. 50160, en fecha <span class="fecha-hoy">9 de enero de 2026</span> (en adelante, el "Contrato").
        </p>

        <p style="text-align:center; margin:2mm 0;">&mdash; ENTRE &mdash;</p>

        <p>
          <b class="nombre-ahorrador">-</b>, con número de RFC:
          <b class="rfc-ahorrador">-</b>, y domicilio fiscal en
          <b class="dir-ahorrador">-</b>,
          actuando en su propio nombre y derecho (el “Ahorrador”).
        </p>

        <p style="text-align:center; margin:2mm 0;">&mdash; Y &mdash;</p>

        <p>
          <b>Domicilio fiscal en
          C. José Ma Pino Suarez 119, Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160,
          actuando en su propio nombre y derecho (la “Institución”).
        </p>

<p>
  <b class="nombre-representante"><?= h($asesorNombre) ?></b>,
  con número de RFC:
  <b class="rfc-representante"><?= h($asesorRfc) ?></b>,
  y domicilio fiscal en
  <b class="domicilio-representante"><?= h($asesorDom) ?></b>,
  actuando en su propio nombre y derecho (el “Asesor”).
</p>



        <p>
          Estos serán considerados individualmente como la “Parte” y conjuntamente como las “Partes”.
        </p>

        <div class="section-title">DECLARACIONES</div>

        <p>
          Que las Partes están interesadas en que el Ahorrador realice un depósito de manera periódica a favor de la Institución,
          todo ello de conformidad con los términos y condiciones incluidos en este Contrato.
        </p>

        <p>
          Que, en virtud de lo anterior, las Partes, reconociéndose mutua y recíproca capacidad legal necesaria y suficiente para contratar
          y obligarse, habiendo entendido el alcance, contenido y consecuencias de las declaraciones emitidas en el presente, y tras haber
          llegado libre y voluntariamente a un acuerdo mutuo justo para ambas, deciden suscribir este Contrato el cual se regirá en lo sucesivo
          de conformidad con lo indicado en las siguientes:
        </p>

        <div class="section-title">CLÁUSULAS</div>
        <div class="section-title">OBJETO DEL CONTRATO</div>

        <p>
          En virtud del presente Contrato, el Ahorrador se compromete a realizar depósitos semanales de
          <strong id="monto_numero">$ 1,000.00</strong><strong> (<span id="monto_letras">Mil pesos 00/100 M.N.</span>)</strong>, a la cuenta designada por la Institución, durante un periodo de
          <strong>52 semanas</strong>. El Ahorrador acepta que, al cumplirse este plazo, se generará un interés sobre el total acumulado,
          conforme a las condiciones estipuladas en este Contrato. Tras la firma de este Contrato, el mismo constituirá carta de pago formal,
          eficaz y vinculante entre las Partes. (En adelante, el “Ahorro”).
        </p>

        <p>
          La entrega del Ahorro a la Institución tendrá lugar en: C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán,
          Toluca, México. C.P. 50160, y a partir del <b class="fecha-hoy">—</b>, a la siguiente cuenta bancaria: <b>INBURSA</b><br>
          Clabe Interbancaria: <b>036 441 50073880489 2</b>
        </p>

        <div class="section-title">FINALIDAD DEL AHORRO</div>

        <p>
          En lo relativo a la finalidad del Ahorro, las Partes acuerdan que el mismo será utilizado para fines comerciales,
          incluyendo de manera enunciativa pero no limitativa, para el siguiente fin:
        </p>

        <ul>
          <li><b>Sufragar los proyectos de Modalidad 10/40, de los futuros pensionados del IMSS.</b></li>
        </ul>

        <p>
          La Institución se compromete a utilizar el Ahorro única y exclusivamente para los fines indicados, sin que pueda destinarlo a otro
          fin diferente salvo que cuente con el consentimiento por escrito del Ahorrador.
        </p>

        <div class="section-title">CONDICIONES DEL RENDIMIENTO</div>

        <p>
          a) Si los pagos son realizados de manera ininterrumpida durante las primeras 18 semanas, el Ahorrador recibirá un rendimiento del
          <span><b id="porcentaje_rendimiento">16%</b></span> efectivo sobre el total de los depósitos acumulados hasta el final de las 52 semanas.
        </p>
        <br>
        <p>
          b) Si el Ahorrador interrumpe sus pagos antes de completar las 18 semanas, el interés que se devengará sobre el total de los ahorros
          acumulados será del <b>10%</b> efectivo, en lugar del <b><span class="porcentaje-base">—</span></b>.
        </p>

        <p>
          El rendimiento se calculará sobre el saldo total acumulado, y será aplicado al final del periodo de ahorro.
        </p>

        <div class="section-title">CÁLCULO Y PAGO DEL RENDIMIENTO</div>

        <p>
          Al finalizar las 52 semanas, se calcularán los rendimientos generados sobre los depósitos del Ahorrador, conforme al tipo de rendimiento
          que corresponda según las condiciones establecidas en el presente Contrato. Los rendimientos se pagarán al Ahorrador al finalizar el contrato,
          junto con el total ahorrado, es decir, el monto de los depósitos realizados durante el periodo de 52 semanas.
        </p>

        <div class="section-title">DEVOLUCIÓN DEL AHORRO</div>

        <p>
          Este Ahorro deberá ser devuelto en su totalidad al Ahorrador en fecha <strong><span class="fecha-devolucion-larga">—</span></strong>.
        </p>

        <p>
          Las Partes, de común acuerdo, estarán habilitadas para pactar una prórroga de este Contrato de Ahorro en caso de que, llegado el momento
          del vencimiento de este, la deuda pendiente no se hubiese reintegrado totalmente. Para lo anterior, será necesario reflejar tal prórroga
          por escrito mediante un anexo al presente Contrato.
        </p>

      </div>
    </div>

 

      <!-- SEGUNDA HOJA -->
<div class="page page-sin-logo">
  <div class="page-inner-border"></div>
  <div class="folio">Folio: <span class="folio-contrato">—</span></div>


  <div class="content">
    <div class="logo">
      <img src="img/logo.png" alt="Logo CIP Financial México">
    </div>

    <div class="section-title">AMORTIZACIÓN ANTICIPADA DEL AHORRO</div>

    <p>
      En caso de que el Rendimiento sea superior al Rendimiento legal fijado en el Código Civil del Estado de México,
      la Institución, después de 6 meses contados desde que se celebró el Contrato, podrá reembolsar el Ahorro,
      cualquiera que sea el plazo fijado para ello, debiendo dar aviso al Ahorrador con 2 meses de anticipación
      y pagando los Rendimientos vencidos.
    </p>

    <div class="section-title">MÉTODO DE PAGO</div>

    <p>
      El Ahorro será devuelto en forma de transferencia. Salvo que las Partes acuerden expresamente lo contrario,
      todo método de pago que requiera de entrega física (cheque, efectivo u otros) tendrá lugar en el domicilio fiscal
      de la Institución.
    </p>

    <div class="section-title">RENDIMIENTOS MORATORIOS</div>

    <p>
      En caso de que la Institución no devuelva el Ahorro en los plazos acordados en este Contrato, se cobrará un
      Rendimiento moratorio al tipo del 10% anual. Las Partes acuerdan que estos Rendimientos se calcularán anualmente
      sobre el total adeudado, y se devengarán desde el día siguiente al vencimiento del plazo de pago o devolución del Ahorro,
      siendo por tanto exigibles sin necesidad de que el Ahorrador tenga que realizar un requerimiento formal a la Institución,
      y continuarán devengándose hasta que se haya pagado el total de lo adeudado en virtud de este Contrato.
      En caso de que el Rendimiento moratorio acordado sea superior al legalmente permitido, se aplicará entonces éste último.
    </p>

    <div class="section-title">OBLIGACIONES Y DECLARACIONES DE LA INSTITUCIÓN</div>

    <p>
      La Institución se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la normativa aplicable,
      y en particular declara y garantiza que:
    </p>

    <ul>
      <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
      <li>No se encuentra en situación de insolvencia económica ni bancarrota y dispone de suficiente poder adquisitivo para poder devolver el Ahorro en las condiciones aquí pactadas.</li>
      <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
      <li>Devolverá el Ahorro de conformidad con los términos y condiciones fijados en este Contrato.</li>
      <li>Asumirá todos los recargos y Rendimientos bancarios que hayan sido causados, directa o indirectamente, por el incumplimiento de la Institución en la devolución del Ahorro.</li>
      <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
    </ul>

    <div class="section-title">OBLIGACIONES Y DECLARACIONES DEL AHORRADOR</div>

    <p>
      El Ahorrador se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la normativa aplicable,
      y en particular declara y garantiza que:
    </p>

    <ul>
      <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
      <li>Dispone de capacidad adquisitiva suficiente para realizar el Ahorro en favor de la Institución de conformidad con los términos y condiciones aquí incluidos.</li>
      <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
      <li>Realizará los depósitos acordados en este Contrato a la Institución en los plazos y condiciones incluidas en el mismo.</li>
      <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
    </ul>

    <div class="section-title">INCUMPLIMIENTO Y RESOLUCIÓN DEL CONTRATO</div>

    <p>
      El incumplimiento por parte de la Institución de cualquiera de las obligaciones contraídas en virtud de este Contrato,
      incluyendo de manera enunciativa, pero no limitativa, la devolución del Ahorro y los correspondientes Rendimientos devengados
      de conformidad con lo estipulado en este Contrato; facultará al Ahorrador para:
    </p>

    <ul>
      <li>Resolver el mismo antes del plazo de vencimiento pactado, siempre que previamente el Ahorrador hubiera requerido por escrito a la Institución para cumplir con sus obligaciones.</li>
      <li>Exigir la devolución inmediata de la totalidad del Ahorro, más los correspondientes Rendimientos devengados.</li>
      <li>Ejercer cuantas acciones legales el derecho habilite de cara a exigir el cumplimiento de las obligaciones contractuales y reclamar los daños y perjuicios que dicha resolución anticipada le hubiera podido originar.</li>
    </ul>

    <div class="section-title">LIQUIDACIÓN DE IMPUESTOS</div>

    <p>
      Este Contrato pasará a ser completamente vinculante entre las Partes tras ser debidamente firmado por las mismas
      sin necesidad de que sea elevado a público.
    </p>

  </div>
</div>


      <!-- TERCERA HOJA -->
      <div class="page page-sin-logo">
  <div class="page-inner-border"></div>
  <div class="folio">Folio: <span class="folio-contrato">—</span></div>


  <div class="content">
    <div class="logo">
      <img src="img/logo.png" alt="Logo CIP Financial México">
    </div>

    <div class="section-title">DOMICILIO A EFECTOS DE NOTIFICACIONES</div>

    <p>
      Para efectos de notificaciones y comunicación de cualquier tipo relacionadas con este Contrato.
      El Ahorrador podrá ser contactado en la dirección postal indicada en el encabezado de este Contrato,
      o a través de las siguientes vías:
    </p>


<ul>
  <li>Nombre o razón social: <b class="dato-nombre">—</b></li>
  <li>E-mail: <b class="dato-correo">—</b></li>
  <li>No. Tel.: <b class="dato-telefono">—</b></li>
</ul>



    <p>
      La Institución podrá ser contactada mediante el Representante Legal en la dirección postal indicada
      en el encabezado de este Contrato, o a través de las siguientes vías:
    </p>

  <ul>
<li>Nombre o razón social: <b class="asesor-nombre">—</b></li>
<li>E-mail: <b class="asesor-correo">—</b></li>
<li>No. Tel.: <b class="asesor-telefono">—</b></li>

  </ul>


    <p>
      En ese sentido, si alguna de las Partes desea cambiar esta información de contacto,
      deberá comunicárselo fehacientemente a la otra Parte. Para cualquier información informal
      relativa al día a día fruto de esta relación contractual, las Partes podrán utilizar las vías
      de teléfono y correo electrónico, pero las mismas no servirán como medio fehaciente.
    </p>

    <div class="section-title">PROTECCIÓN DE DATOS DE CARÁCTER PERSONAL</div>

    <p>
      Las Partes se comprometen a cumplir con todas y cada una de las obligaciones incluidas por la
      normativa aplicable en materia de protección de datos personales, esto es, la Ley Federal de
      Protección de Datos Personales en Posesión de los Particulares.
    </p>

    <p>
      En ese sentido, las Partes declaran y reconocen que serán responsables del tratamiento de los
      datos personales de la otra parte, debiendo tratar los mismos de manera segura y confidencial,
      respetando siempre los requisitos y obligaciones incluidos por referidas normativas. Los datos
      personales serán tratados con la finalidad de ejecutar correctamente este Contrato.
      Adicionalmente, los datos personales serán tratados con la finalidad de cumplir con las
      obligaciones legales en materia administrativa, contable, tributaria y financiera.
    </p>

    <p>
      Las Partes tratarán únicamente aquellos datos estrictamente necesarios, adecuados y pertinentes
      para dar cumplimiento con las finalidades señaladas y por ende no serán tratados de manera
      incompatible con las mismas.
    </p>

    <p>
      Adicionalmente, las Partes tratarán estos datos personales de manera confidencial y sobre los
      mismos se aplicarán las medidas técnicas y organizativas adecuadas y suficientes que garanticen
      la confidencialidad y privacidad de estos, así como su integridad y disponibilidad y la resiliencia
      permanente del sistema de información que los contienen.
    </p>

    <p>
      Los datos no serán comunicados a terceros sin autorización, salvo en los supuestos expresamente
      permitidos o exigibles conforme a la ley. Sin perjuicio de lo anterior, los datos personales
      podrán ser comunicados a terceros prestadores de servicios (como bancos u otras entidades
      financieras) en el marco de una relación contractual. No obstante, se garantiza que previamente
      a proceder con ello se suscribirá el correspondiente contrato de encargo de tratamiento que
      regularice referido acceso.
    </p>

    <p>
      Con carácter general, los datos personales serán conservados mientras este Préstamo continúe
      en vigor, y en todo caso, hasta la prescripción de la posible responsabilidad legal que pudiera
      derivarse de lo anterior.
    </p>

    <p>
      En cualquier momento, las Partes podrán ejercer los derechos de acceso, rectificación,
      cancelación y oposición, en las condiciones legalmente previstas y dirigiéndose por escrito a
      las direcciones indicadas en este Contrato. Asimismo, se informa a los interesados que les
      asiste el derecho a efectuar una reclamación ante el Instituto Nacional de Transparencia,
      Acceso a la Información y Protección de Datos Personales en caso de que consideren que el
      tratamiento de sus datos no es el adecuado.
    </p>

    <div class="section-title">ORIGEN DE LOS RECURSOS</div>

    <p>
      Que los recursos con los cuales ha de pagar el Ahorro dispuesto han sido o serán obtenidos o
      generados a través de una fuente de origen lícito y que el destino que dará a los recursos
      obtenidos al amparo del presente Contrato será tan solo a fines permitidos por la ley, y que
      no se encuentran dentro de los supuestos establecidos en los artículos 139 Quáter y 400 Bis
      del Código Penal Federal.
    </p>

    <p>
      Se reitera que el presente Contrato se celebra con motivo de la entrega de un recurso económico
      de origen lícito, mismo que el Ahorrador manifiesta y garantiza no proviene de actividades
      ilícitas o contrarias a la ley, de conformidad con lo dispuesto por la legislación civil vigente
      y en estricto apego a los principios de legalidad.
    </p>

    <p>
      En caso de controversia o cuestionamiento sobre la validez o licitud del presente instrumento,
      el Ahorro queda fundamentado en los artículos <b>2534 y 2535 del Código Civil Federal</b>
      (en cuanto al contrato de mutuo) y, para su ejecución o exigibilidad judicial, en lo establecido
      por el <b>Código Nacional de Procedimientos Civiles y Familiares</b>, particularmente en lo
      relativo a los procedimientos de ejecución de obligaciones contractuales.
    </p>
    <p>
        Las partes se comprometen a dirimir cualquier controversia conforme a derecho, garantizando la transparencia, buena fe y la legalidad del presente acto jurídico
    </p>
  </div>
</div>
        <!-- CUARTA HOJA -->

<div class="page page-sin-logo">
  <div class="page-inner-border"></div>
  <div class="folio">Folio: <span class="folio-contrato">—</span></div>


  <div class="content">
    <div class="logo">
      <img src="img/logo.png" alt="Logo CIP Financial México">
    </div>
    <div class="section-title">LEY APLICABLE Y TRIBUNAL COMPETENTE</div>

    <p>
      Cualquier conflicto que pueda surgir en relación con este Contrato será interpretado
      de conformidad con la ley federal aplicable a los Estados Unidos Mexicanos y al Estado
      de México. En caso de contradicción entre ambas, primará lo dispuesto en la ley estatal,
      salvo que se trate de materias expresamente reservadas a regulación federal.
    </p>

    <p>
      Las Partes se comprometen a buscar siempre de buena fe una solución amistosa ante cualquier
      disputa o conflicto que pudiera surgir relacionado con este Contrato, tratando de evitar
      en todo caso el acudir a la vía judicial o al arbitraje.
    </p>

    <p>
      Si las Partes no fueran capaces de resolver amistosamente estas disputas dentro de los
      14 días siguientes al surgimiento de la disputa, las mismas acuerdan someterse de manera
      expresa e irrevocable a la jurisdicción y competencia de los juzgados y tribunales del
      domicilio de la Institución, con renuncia expresa a cualquier otra jurisdicción que le
      pudiera corresponder, salvo que esta fuera imperativa en aplicación de lo dispuesto en
      la ley aplicable.
    </p>

    <div class="section-title">MODIFICACIONES</div>

    <p>
      Este Contrato de Ahorro únicamente podrá ser modificado mediante acuerdo por escrito
      firmado por todas las Partes.
    </p>

    <div class="section-title">SEPARABILIDAD</div>

    <p>
      En caso de existir contradicción entre cualquier cláusula de este Contrato y la ley
      aplicable, esta última prevalecerá, y las cláusulas que la contradigan se entenderán
      por no puestas. Adicionalmente, toda disposición normativa que por ley deba formar parte
      de este Contrato, se entenderá añadida al Contrato, formando parte de su contenido.
    </p>

    <p>
      La ilicitud, invalidez o ineficacia de alguna de las cláusulas de este Contrato no afectará
      al resto de cláusulas, las cuales seguirán vigentes y serán plenamente eficaces. Con
      respecto a las cláusulas afectadas, se entenderán por no puestas y las Partes se comprometen
      a negociar de buena fe un nuevo texto para esas cláusulas buscando siempre la misma finalidad
      que perseguía la cláusula original.
    </p>

    <p>
      Este Contrato de Ahorro constituye el acuerdo completo entre las Partes, y no existe
      ningún otro documento, término o condición relacionado con el mismo, ni verbal ni de
      ningún otro tipo.
    </p>

    <div class="section-title">CESIÓN DEL CONTRATO</div>

    <p>
      Las Partes no podrán ceder este Contrato salvo cuando cuenten con el consentimiento previo,
      expreso y por escrito de la otra Parte.
    </p>

    <p>
      Y EN PRUEBA DE CONFORMIDAD Y ACEPTACIÓN, las Partes firman este Contrato en fecha
      <span class="fecha-hoy"></span>.
    </p>

<div class="section-title">Beneficiario</div>

<p>En caso de fallecimiento o incapacidad de cualquiera de las Partes:</p>

<ol>
  <li>
    El Ahorrador podrá designar un beneficiario para que asuma las obligaciones derivadas del presente Contrato, incluyendo la continuidad del plan de ahorro y la devolución de los recursos correspondientes, en su caso. Para ello, el Ahorrador notificará a la Institución por escrito el nombre del beneficiario designado.
  </li>

  <li>
    La Institución podrá igualmente designar un beneficiario para recibir los derechos derivados del presente Contrato en caso de fallecimiento o incapacidad del Representante Legal de la Institución, notificando previamente al Ahorrador por escrito el nombre del beneficiario designado.
  </li>

  <li>
    La designación del beneficiario será válida siempre y cuando dicha persona cumpla con los requisitos legales necesarios para asumir las obligaciones contractuales, y ambas Partes acuerden expresamente por escrito los términos y condiciones para dicha transmisión de derechos y obligaciones.
  </li>

  <li>
    En caso de que no se designe un beneficiario de manera explícita por alguna de las Partes, se considerará que los derechos y obligaciones del Contrato se transmiten a los herederos legales de la Parte fallecida, conforme a la legislación vigente aplicable.
  </li>
</ol>

<p>
<strong>Beneficiario designado:</strong>
<span class="beneficiario-nombre">—</span> |
<strong>Parentesco:</strong> <span class="beneficiario-parentesco">—</span>
<br>
<strong>CURP:</strong> <span class="beneficiario-curp">—</span> |
<strong>Teléfono:</strong> <span class="beneficiario-telefono">—</span>
</p>


    <!-- FIRMAS -->
    <div class="firmas-fila">
      <div class="firma-col">
        <div class="firma-titulo">FIRMA DEL AHORRADOR</div>
        <div class="firma-espacio"></div>
        <div class="firma-texto">
          En su condición de AHORRADOR y en su propio nombre y derecho.<br>
          <span class="firma-nombre firma-prestamista">JESSICA ESPERANZA OLGUIN TORRES</span>
        </div>
      </div>

      <div class="firma-col">
        <div class="firma-titulo">FIRMA DEL REPRESENTANTE LEGAL</div>
        <div class="firma-espacio"></div>
        <div class="firma-texto">
          En su condición de REPRESENTANTE LEGAL en su propio nombre y derecho.<br>
<span class="firma-nombre firma-asesor"><?= htmlspecialchars(mb_strtoupper($asesorNombre,'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span>


        </div>
      </div>
    </div>

  </div>
</div>



<div class="page page-sin-logo page-calendario">
  <div class="page-inner-border"></div>
  <div class="folio">Folio: <span class="folio-contrato">—</span></div>

  <div class="content">
    <div class="logo">
      <img src="img/logo.png" alt="Logo CIP Financial México">
    </div>

    <div class="section-title">CALENDARIO DE TRANSFERENCIAS (52 SEMANAS)</div>

    <!-- ✅ Wrapper independiente -->
    <div class="cal-wrap">

      <p class="cal-head">
        Ahorrador: <b class="nombre-ahorrador">—</b> &nbsp;|&nbsp;
        Monto semanal: <b id="monto_numero_cal">—</b> &nbsp;|&nbsp;
        Inicio: <b class="fecha-hoy">—</b>
      </p>

      <p class="cal-head">
        Al comenzar tus abonos en la fecha seleccionada, los siguientes abonos se tendrían que realizar de acuerdo a la siguiente tabla:
      </p>

      <div id="tabla-52-semanas" class="tabla-52-wrap"></div>

      <p class="cal-foot">
        Si los pagos se hicieron de manera ininterrumpida las primeras 18 semanas el rendimiento que genere tu ahorro será del
        <b><span id="pct_cal_18">—</span>%</b>.
        Si se hacen los 52 pagos, a la semana 53 tendrás disponible:
      </p>


      <table class="tabla-resumen">
        <tr><td>TU AHORRO ACUMULADO</td><td id="total_ahorrado">$ 0.00</td></tr>
        <tr><td>RENDIMIENTO</td><td id="rendimiento_generado">$ 0.00</td></tr>
        <tr><td><strong>TOTAL DISPONIBLE</strong></td><td id="total_recibir"><strong>$ 0.00</strong></td></tr>
      </table>

      <p class="cal-foot">
        Si por alguna razón, interrumpiste tus pagos antes de la semana 18, el rendimiento que genere tu ahorro será del 10%
        sobre el total de tu ahorro acumulado
      </p>

    </div><!-- /cal-wrap -->
  </div>
</div>



    </div> <!-- FIN page-wrapper -->
  </div> <!-- FIN contrato -->

  <!-- ================== IMPRESIÓN Y DESCARGA PDF ================== -->
<script>
  function imprimirContrato() {
    window.print();
  }

function descargarPDF() {
  const element = document.getElementById('contrato');

  document.body.classList.add('pdf-mode');

  const opt = {
    margin: 0,
    filename: 'contrato_prestamo.pdf',
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: {
      scale: 2,
      useCORS: true,
      scrollY: 0,     // ✅ evita que lo capture “corridos”
      scrollX: 0
    },
    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' }, // ✅ CARTA
    pagebreak: { mode: ['css', 'legacy'] }
  };

  html2pdf()
    .set(opt)
    .from(element)
    .save()
    .finally(() => document.body.classList.remove('pdf-mode'));
}


  // Numerar páginas automáticamente
  document.addEventListener('DOMContentLoaded', () => {
    const pages = document.querySelectorAll('.page');
    const total = pages.length;

    pages.forEach((page, index) => {
      const num = document.createElement('div');
      num.className = 'page-number';
      num.textContent = `Página ${index + 1} de ${total}`;
      page.appendChild(num);
    });
  });
</script>




<script>
/* =========================================================
   UTILIDADES
========================================================= */
function pad2(n){ return String(n).padStart(2,'0'); }

function addDaysLocal(date, days){
  const d = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  d.setDate(d.getDate() + days);
  return d;
}

function fechaLargaMX(fecha) {
  const meses = [
    "enero","febrero","marzo","abril","mayo","junio",
    "julio","agosto","septiembre","octubre","noviembre","diciembre"
  ];
  const dia  = fecha.getDate();
  const mes  = meses[fecha.getMonth()];
  const anio = fecha.getFullYear();
  return `${dia} de ${mes} de ${anio}`;
}

function parseYMDToDate(ymd){
  const m = String(ymd || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!m) return null;
  const y  = parseInt(m[1],10);
  const mo = parseInt(m[2],10)-1;
  const d  = parseInt(m[3],10);
  return new Date(y, mo, d);
}

function formatoMXN(n){
  const num = Number(n) || 0;
  return new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2
  }).format(num);
}

const $id = (id) => document.getElementById(id);
const val = (id) => ($id(id)?.value ?? '').trim();

function upperJoin(nombre, apP, apM){
  return `${(nombre||'').trim()} ${(apP||'').trim()} ${(apM||'').trim()}`
    .replace(/\s+/g,' ')
    .trim()
    .toUpperCase();
}

/* =========================================================
   NÚMERO A LETRAS (PESOS MXN)
========================================================= */
function numeroALetrasPesos(num) {
  num = Number(num) || 0;

  const enteros  = Math.floor(num);
  const centavos = Math.round((num - enteros) * 100);

  const letrasEnteros = convertirNumero(enteros);
  const centavosStr   = centavos.toString().padStart(2, '0');

  let texto = `${letrasEnteros} pesos ${centavosStr}/100 M.N.`;
  return texto.charAt(0).toUpperCase() + texto.slice(1);
}

function convertirNumero(num) {
  if (num === 0) return 'cero';

  const unidades = [
    '', 'uno', 'dos', 'tres', 'cuatro', 'cinco',
    'seis', 'siete', 'ocho', 'nueve', 'diez',
    'once', 'doce', 'trece', 'catorce', 'quince',
    'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'
  ];
  const decenas = [
    '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta',
    'sesenta', 'setenta', 'ochenta', 'noventa'
  ];
  const centenas = [
    '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos',
    'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
  ];

  function seccion(n, divisor, singular, plural) {
    const cientos = Math.floor(n / divisor);
    const resto   = n - cientos * divisor;
    let letras    = '';

    if (cientos > 0) {
      letras = (cientos === 1) ? singular : (convertirNumero(cientos) + ' ' + plural);
    }
    if (resto > 0) {
      letras += (letras ? ' ' : '') + convertirNumero(resto);
    }
    return letras;
  }

  if (num < 20) return unidades[num];

  if (num < 100) {
    const d = Math.floor(num / 10);
    const r = num % 10;
    if (num >= 20 && num < 30) {
      if (num === 20) return 'veinte';
      return 'veinti' + unidades[r];
    }
    return decenas[d] + (r ? ' y ' + unidades[r] : '');
  }

  if (num < 1000) {
    if (num === 100) return 'cien';
    const c = Math.floor(num / 100);
    const r = num % 100;
    return centenas[c] + (r ? ' ' + convertirNumero(r) : '');
  }

  if (num < 1000000) return seccion(num, 1000, 'mil', 'mil');
  if (num < 1000000000000) return seccion(num, 1000000, 'un millón', 'millones');
  return 'número demasiado grande';
}

/* =========================================================
   1) FECHA DEVOLUCIÓN = 52 semanas
========================================================= */
function calcularYpintarDevolucion52Semanas(){
  const inicioYMD = val('fecha_inicio_ahorro');
  const inicioDate = parseYMDToDate(inicioYMD);

  if (!inicioDate){
    document.querySelectorAll('.fecha-devolucion-larga, .fecha-devolucion')
      .forEach(el => el.textContent = '—');
    return;
  }

  const devolDate = addDaysLocal(inicioDate, 364); // 52 semanas
  const devolLargo = fechaLargaMX(devolDate);

  document.querySelectorAll('.fecha-devolucion-larga, .fecha-devolucion')
    .forEach(el => el.textContent = devolLargo);
}

/* =========================================================
   2) MONTO -> % y monto en contrato
========================================================= */
function getPorcentajePorMonto(m){
  const monto = Number(m);
  if (monto === 200) return 14;
  if (monto === 500) return 15;
  if (monto === 1000) return 16;
  return 16;
}

function pintarMontoYPorcentaje(){
  const sel = $id('monto_semanal');
  if (!sel || !sel.value) return;

  const monto = Number(sel.value);
  const pct   = getPorcentajePorMonto(monto);

  const elPct = $id('porcentaje_rendimiento');
  if (elPct) elPct.textContent = `${pct}%`;

  document.querySelectorAll('.porcentaje-base')
    .forEach(el => el.textContent = `${pct}%`);

  // ✅ nuevo: texto del calendario
  const elPctCal = document.getElementById('pct_cal_18');
  if (elPctCal) elPctCal.textContent = String(pct);

  const elNum = $id('monto_numero');
  const elLet = $id('monto_letras');
  if (elNum) elNum.textContent = formatoMXN(monto);
  if (elLet) elLet.textContent = numeroALetrasPesos(monto);
}


/* =========================================================
   3) PINTAR FOLIO
========================================================= */
function pintarFolio(folio){
  const f = (folio && String(folio).trim()) ? String(folio).trim() : '—';
  document.querySelectorAll('.folio-contrato').forEach(el => el.textContent = f);
}

/* =========================================================
   4) PINTAR ASESOR EN CONTRATO (GLOBAL)
   (ANTES estaba adentro de rellenarContrato y por eso no funcionaba)
========================================================= */
function pintarAsesorEnContrato(){
  const a = window.ASESOR || {};

  const nombre = String(a.nombre || '').trim().toUpperCase();
  const correo = String(a.correo || '').trim();
  const tel    = String(a.telefono || '').trim();

  console.log('🧩 pintarAsesorEnContrato()', { nombre, correo, tel });

  document.querySelectorAll('.asesor-nombre').forEach(el => el.textContent = nombre || '—');
  document.querySelectorAll('.asesor-correo').forEach(el => el.textContent = correo || '—');
  document.querySelectorAll('.asesor-telefono').forEach(el => el.textContent = tel    || '—');
}


/* =========================================================
   5) RELLENAR CONTRATO (pinta datos)
========================================================= */
function rellenarContrato(){
  const nombre     = val('nombre');
  const apPaterno  = val('ap_paterno');
  const apMaterno  = val('ap_materno');
  const rfc        = val('rfc').toUpperCase();
  const cp         = val('cp');
  const direccion  = val('direccion').toUpperCase();

  const nombreCompleto = upperJoin(nombre, apPaterno, apMaterno);
  const dirCompleta = (direccion && cp) ? `${direccion}, C.P. ${cp}` : (direccion || '—');

  document.querySelectorAll('.nombre-ahorrador, .firma-prestamista, #prestamista-nombre')
    .forEach(el => el.textContent = nombreCompleto || '—');

  document.querySelectorAll('.rfc-ahorrador, #prestamista-rfc')
    .forEach(el => el.textContent = rfc || '—');

  document.querySelectorAll('.dir-ahorrador, #prestamista-direccion')
    .forEach(el => el.textContent = dirCompleta);

      // ✅ Pintar BENEFICIARIO en el contrato
  document.querySelectorAll('.beneficiario-nombre')
    .forEach(el => el.textContent = val('beneficiario_nombre') || '—');

  document.querySelectorAll('.beneficiario-parentesco')
    .forEach(el => el.textContent = val('beneficiario_parentesco') || '—');

  document.querySelectorAll('.beneficiario-curp')
    .forEach(el => el.textContent = val('beneficiario_curp').toUpperCase() || '—');

  document.querySelectorAll('.beneficiario-telefono')
    .forEach(el => el.textContent = val('beneficiario_telefono') || '—');


  const inicioDate = parseYMDToDate(val('fecha_inicio_ahorro'));
  if (inicioDate){
    const inicioLargo = fechaLargaMX(inicioDate);
    document.querySelectorAll('.fecha-hoy').forEach(el => el.textContent = inicioLargo);
  }

  calcularYpintarDevolucion52Semanas();
  pintarMontoYPorcentaje();

  // ===== Datos de contacto del AHORRADOR =====
  const nombreContacto = upperJoin(val('nombre'), val('ap_paterno'), val('ap_materno'));

  document.querySelectorAll('.dato-nombre')
    .forEach(el => el.textContent = nombreContacto || '—');

  document.querySelectorAll('.dato-correo')
    .forEach(el => el.textContent = val('correo') || '—');

  document.querySelectorAll('.dato-telefono')
    .forEach(el => el.textContent = val('telefono') || '—');

  // ===== Datos del ASESOR =====
  pintarAsesorEnContrato();
    // ✅ Pintar monto en el calendario
  const monto = Number(val('monto_semanal') || 0);
  const elCal = document.getElementById('monto_numero_cal');
  if (elCal) elCal.textContent = monto ? formatoMXN(monto) : '—';

  // ✅ Generar tabla SIEMPRE que haya datos
  if (typeof generarTabla52Semanas === 'function') {
    generarTabla52Semanas();
  }

}

/* =========================================================
   6) FORM: BLOQUEAR / EDITAR
========================================================= */
// Campos que JAMÁS se deben habilitar cuando ya existe un registro
const FIELDS_LOCK_FOREVER = ['fecha_inicio_ahorro', 'monto_semanal'];
let LOCK_FOREVER_ACTIVE = false;

function bloquearCamposForever(){
  LOCK_FOREVER_ACTIVE = true;
  FIELDS_LOCK_FOREVER.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.disabled = true;
  });
}



let FORM_BLOQUEADO = false;

async function actualizarDatos(){
  rellenarContrato();

  if (!FORM_BLOQUEADO) {
    FORM_BLOQUEADO = true;
    document
      .querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
      .forEach(el => el.disabled = true);

    const card = document.querySelector('.card-form');
    if (card) card.style.display = 'none';
  }
}

function activarEdicion(){
  FORM_BLOQUEADO = false;

  const card = document.querySelector('.card-form');
  if (card) card.style.display = '';

  document
    .querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
    .forEach(el => el.disabled = false);

  // ✅ volver a bloquear los que nunca deben cambiar
  if (LOCK_FOREVER_ACTIVE) bloquearCamposForever();

  $id('nombre')?.focus();
}


window.actualizarDatos = actualizarDatos;
window.activarEdicion  = activarEdicion;

/* =========================================================
   7) GUARDAR EN BD (INSERT / UPDATE)
========================================================= */
async function guardar(datos){
  const res = await fetch('app/controllers/ahorro/guardar.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(datos)
  });

  const text = await res.text();

  try{
    const json = JSON.parse(text);
    if(!json.ok) throw new Error(json.error || 'Error al guardar');
    return json;
  }catch(e){
    console.error('RESPUESTA CRUDA guardar.php:\n', text);
    alert('❌ guardar.php NO devolvió JSON. Respuesta cruda:\n\n' + text.slice(0, 900));
    throw new Error('El servidor no devolvió JSON.');
  }
}

async function guardarYrellenar(){
  // 1) pintar en pantalla
  if (typeof rellenarContrato === 'function') rellenarContrato();
  if (typeof generarTabla52Semanas === 'function') generarTabla52Semanas();


  // 2) tomar id si estamos editando
  const editId = Number($id('ahorro_id')?.value || 0);

  const datos = {
    id: editId, // ✅ si > 0 => UPDATE en tu PHP
    nombre: val('nombre'),
    ap_paterno: val('ap_paterno'),
    ap_materno: val('ap_materno'),
    rfc: val('rfc').toUpperCase(),
    correo: val('correo'),
    telefono: val('telefono'),
    cp: val('cp'),
    direccion: val('direccion'),
    fecha_inicio_ahorro: val('fecha_inicio_ahorro'),
    fecha_devolucion: (() => {
      const inicio = parseYMDToDate(val('fecha_inicio_ahorro'));
      if (!inicio) return '';
      const devol = addDaysLocal(inicio, 364);
      return `${devol.getFullYear()}-${pad2(devol.getMonth()+1)}-${pad2(devol.getDate())}`;
    })(),
    monto_semanal: Number(val('monto_semanal') || 0),
    porcentaje: Number(($id('porcentaje_rendimiento')?.textContent || '').replace(/[^\d]/g,'') || 0),
        // ✅ BENEFICIARIO (AQUÍ)
    beneficiario_nombre: val('beneficiario_nombre'),
    beneficiario_curp: val('beneficiario_curp').toUpperCase(),
    beneficiario_telefono: val('beneficiario_telefono'),
    beneficiario_parentesco: val('beneficiario_parentesco')
  };

  console.log('📌 ENVIANDO A guardar.php:', datos);

  const r = await guardar(datos);

  // ✅ solo pinta folio si viene (en insert viene, en update puede que no)
  if (r && r.folio) pintarFolio(r.folio);

  alert('✅ Guardado con ID: ' + r.id);

  // bloquear
  FORM_BLOQUEADO = true;
  document.querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
    .forEach(el => el.disabled = true);

  const card = document.querySelector('.card-form');
  if (card) card.style.display = 'none';

  const btnEditar = $id('btn-editar');
  if (btnEditar) btnEditar.style.display = '';
}

/* =========================================================
   8) CARGAR REGISTRO (EDITAR)
========================================================= */
async function cargarRegistro(id){
  const set = (inputId, value) => {
    const el = $id(inputId);
    if (!el) return;
    el.value = (value ?? '') === null ? '' : String(value ?? '');
  };

  const normalDate = (ymd) => {
    const s = String(ymd || '').trim();
    if (!s || s === '0000-00-00') return '';
    return s;
  };

  const pickMoney = (v) => {
    const n = parseFloat(String(v ?? '0').replace(/[^0-9.]/g,''));
    if (!isFinite(n) || n <= 0) return '';
    return String(Math.round(n));
  };

  // ✅ IMPORTANTE: usar ./ para forzar relativo al mismo folder del ahorros.php
  const url = new URL('./app/controllers/ahorro/get.php', window.location.href);
  url.searchParams.set('id', String(id));

  const res = await fetch(url.toString(), {
    cache: 'no-store',
    credentials: 'same-origin',              // ✅ manda cookie de sesión
    headers: { 'Accept': 'application/json'} // ✅ pide JSON
  });

  const text = await res.text();
  const head = text.trim().slice(0, 120);
  const ctype = (res.headers.get('content-type') || '').toLowerCase();

  // ✅ DEBUG útil en consola
  console.log('GET URL:', url.toString());
  console.log('STATUS:', res.status, 'redirected:', res.redirected, 'ctype:', ctype);

  // 🚨 Si llega HTML (login/404/errores PHP), aquí lo detectas ANTES del JSON.parse
  if (head.startsWith('<') || head.toLowerCase().includes('<!doctype')) {
    console.error('❌ get.php devolvió HTML (no JSON). Inicio:\n', head);
    console.error('↪ Respuesta completa (primeros 900 chars):\n', text.slice(0, 900));
    throw new Error('get.php devolvió HTML (login/404/error PHP). Revisa ruta y sesión.');
  }

  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    console.error('❌ get.php devolvió texto que no es JSON:\n', text.slice(0, 900));
    throw new Error('get.php NO devolvió JSON válido.');
  }

  if (!json || json.ok !== true) {
    console.error('❌ get.php respondió ok=false:', json);
    throw new Error(json?.error || 'No se pudo cargar');
  }

  const r = json.data || json.row || json.registro;
  if (!r || typeof r !== 'object') {
    console.error('❌ get.php ok=true pero sin data/row válido:', json);
    throw new Error('Respuesta sin "data". Revisa get.php.');
  }

  // ✅ llenar form
  set('nombre', r.nombre);
  set('ap_paterno', r.ap_paterno);
  set('ap_materno', r.ap_materno);
  set('rfc', r.rfc);
  set('cp', r.cp);
  set('correo', r.correo);
  set('telefono', r.telefono);
  set('direccion', r.direccion);
  set('fecha_inicio_ahorro', normalDate(r.fecha_inicio_ahorro));
    // ✅ BENEFICIARIO (FALTABA)
  set('beneficiario_nombre', r.beneficiario_nombre);
  set('beneficiario_parentesco', r.beneficiario_parentesco);
  set('beneficiario_curp', r.beneficiario_curp);
  set('beneficiario_telefono', r.beneficiario_telefono);


  // ✅ guardar id hidden (para UPDATE)
  set('ahorro_id', String(id));

  const montoSel = $id('monto_semanal');
  if (montoSel) montoSel.value = pickMoney(r.monto_semanal);

  // ✅ pintar contrato + folio
  if (typeof rellenarContrato === 'function') rellenarContrato();
  if (typeof pintarFolio === 'function') pintarFolio(String(r.folio || '').trim() || '—');

  // ✅ bloquear y ocultar
  document.querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
    .forEach(el => el.disabled = true);

  const card = document.querySelector('.card-form');
  if (card) card.style.display = 'none';

  const btnEditar = $id('btn-editar');
  if (btnEditar) btnEditar.style.display = '';
  // ✅ si ya existe registro cargado, estos campos jamás se reactivan
bloquearCamposForever();

}


/* =========================================================
   9) VOLVER LISTADO
========================================================= */
function volverListado(){
  // 1) intenta back real si existe historial
  if (window.history.length > 1) {
    window.history.back();
    return;
  }

  // 2) si no hay historial útil, usa la última vista guardada
  const last = sessionStorage.getItem('CIP_LAST_VIEW');
  if (last && typeof last === 'string') {
    window.location.href = last;
    return;
  }

  // 3) fallback final
  window.location.href = 'dashboard_ahorro.php';
}

/* =========================================================
   INIT
========================================================= */
document.addEventListener('DOMContentLoaded', () => {
 pintarAsesorEnContrato();
  // si viene id => cargar registro
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (id) {
    cargarRegistro(id).catch(err => {
      console.error(err);
      alert('❌ No se pudo cargar el registro: ' + (err.message || err));
    });
  }

  // submit form => guardar
  const form = $id('form-prestamo');
  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      try{
        await guardarYrellenar();
      }catch(err){
        console.error(err);
        alert('❌ No se pudo guardar: ' + (err.message || err));
      }
    });
  }

  // change fecha
  const inicioEl = $id('fecha_inicio_ahorro');
  if (inicioEl) {
    inicioEl.addEventListener('change', () => {
      calcularYpintarDevolucion52Semanas();
      if (!FORM_BLOQUEADO) {
        rellenarContrato();
        generarTabla52Semanas();
      }
    });

    calcularYpintarDevolucion52Semanas();
  }

  // change monto
  const montoSel = $id('monto_semanal');
  if (montoSel) {
montoSel.addEventListener('change', () => {
  pintarMontoYPorcentaje();
  if (!FORM_BLOQUEADO) {
    rellenarContrato();
    generarTabla52Semanas();
  }
});

    pintarMontoYPorcentaje();
  }

  // input en vivo
  ['nombre','ap_paterno','ap_materno','rfc','cp','direccion','correo','telefono'].forEach(id => {
    const el = $id(id);
    if (el) el.addEventListener('input', () => {
      if (!FORM_BLOQUEADO) rellenarContrato();
    });
  });

  // numerar páginas
  const pages = document.querySelectorAll('.page');
  const total = pages.length;
  pages.forEach((page, index) => {
    const num = document.createElement('div');
    num.className = 'page-number';
    num.textContent = `Página ${index + 1} de ${total}`;
    page.appendChild(num);
  });

  // ✅ pintar asesor sí o sí (si window.ASESOR existe)
  pintarAsesorEnContrato();
});
</script>




<script>
function generarTabla52Semanas(){
  const cont = document.getElementById('tabla-52-semanas');
  if (!cont) return;

  const inicio = parseYMDToDate(val('fecha_inicio_ahorro'));
  const monto  = Number(val('monto_semanal') || 0);

  if (!inicio || !monto){
    cont.innerHTML = '<p style="margin:0;">—</p>';
    return;
  }

  // 200→14, 500→15, 1000→16
  const pct  = getPorcentajePorMonto(monto);
  const tasa = pct / 100;

  const rendSemanal = monto * tasa; // ej: 200 * 0.14 = 28
  let rendAcum = 0;

  let html = `
    <table class="tabla-52">
      <thead>
        <tr>
          <th>Semana</th>
          <th>Fecha</th>
          <th>Monto</th>
          <th>Rendimiento</th>
        </tr>
      </thead>
      <tbody>
  `;

  let fechaFinalTxt = '—';

  for (let s = 1; s <= 52; s++){
    const fecha = addDaysLocal(inicio, (s - 1) * 7);
    const fechaTxt = `${pad2(fecha.getDate())}/${pad2(fecha.getMonth()+1)}/${fecha.getFullYear()}`;

    if (s === 52) {
  const fecha53 = addDaysLocal(fecha, 7); // ✅ una semana más
  fechaFinalTxt = `${pad2(fecha53.getDate())}/${pad2(fecha53.getMonth()+1)}/${fecha53.getFullYear()}`;
}

    // ✅ Semana 1 no aplica rendimiento. Desde semana 2 se acumula.
    if (s >= 2) rendAcum += rendSemanal;

    html += `
      <tr>
        <td class="td-sem">${s}</td>
        <td class="td-fecha">${fechaTxt}</td>
        <td class="td-monto">${formatoMXN(monto)}</td>
        <td class="td-rend">${s === 1 ? formatoMXN(0) : formatoMXN(rendAcum)}</td>
      </tr>
    `;
  }

const totalDepositos = monto * 52;

// ✅ rendimiento final incluye un extra (ej: 1428 + 28)
const rendimientoFinal = rendAcum + rendSemanal;

// ✅ total final depósitos + rendimiento final
const totalFinal = totalDepositos + rendimientoFinal;


  // ✅ FILA EXTRA: ACUMULADO (con fecha final + total depositado + total final)
html += `
  <tr class="row-acumulado">
    <td class="td-sem td-acum">ACUMULADO</td>
    <td class="td-fecha td-acum-fecha">${fechaFinalTxt}</td>
    <td class="td-monto td-acum-monto">${formatoMXN(totalDepositos)}</td>
    <td class="td-rend td-acum-total">${formatoMXN(rendimientoFinal)}</td>
  </tr>
`;

  html += `</tbody></table>`;
  cont.innerHTML = html;

document.getElementById('total_ahorrado').textContent      = formatoMXN(totalDepositos);
document.getElementById('rendimiento_generado').textContent = formatoMXN(rendimientoFinal);
document.getElementById('total_recibir').textContent       = formatoMXN(totalFinal);


}



  </script>

</body>
</html>
