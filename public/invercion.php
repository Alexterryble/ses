<?php
declare(strict_types=1);

// 1) Verificar que haya sesión y cargar asesor en $_SESSION['asesor']
require_once __DIR__ . '/app/controllers/auth/require_login.php';

// Ya no es obligatorio volver a requerir la conexión aquí SOLO para el asesor,
// a menos que la uses para otras cosas más abajo.
// require_once __DIR__ . '/app/db/conexion.php';

// 2) Tomar todo desde la sesión
$asesorSesion = $_SESSION['asesor'] ?? [];

$asesorId        = (int)($asesorSesion['id'] ?? $_SESSION['asesor_id'] ?? $_SESSION['user_id'] ?? 0);
$asesorNombre    = trim(
    ($asesorSesion['nombre'] ?? '') . ' ' .
    ($asesorSesion['apellido_paterno'] ?? '') . ' ' .
    ($asesorSesion['apellido_materno'] ?? '')
);
$asesorRfc       = (string)($asesorSesion['rfc'] ?? '');
$asesorDomicilio = (string)($asesorSesion['direccion'] ?? '');
$asesorRol       = (string)($asesorSesion['rol'] ?? '');      // 👈 AQUÍ YA VIENE
$asesorCorreo    = (string)($asesorSesion['correo'] ?? '');
$asesorTelefono  = (string)($asesorSesion['telefono'] ?? '');
?>

<script>
  const ASESOR_PHP = <?= json_encode($asesorSesion, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  console.log('ASESOR EN SESSION:', ASESOR_PHP);
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

  /* ===== ESTILO GENERAL EN PANTALLA ===== */
  body {
    margin: 20px;
    padding: 0;
    background: #f4f4f4;
    font-family: "Aptos", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
  }

  /* CONTENEDOR DE LAS PÁGINAS */
  .page-wrapper {
    display: flex;
    flex-direction: column;   /* apila las hojas */
    align-items: center;      /* centra horizontalmente */
  }

  /* CADA HOJA (vista en pantalla) */
  .page {
    width: 210mm;
    height: 295mm;
    background: #ffffff;
    padding: 6mm 8mm 10mm 8mm;
    position: relative;
    box-shadow: 0 0 10px rgba(0,0,0,0.15);
    font-size: 5pt;
    line-height: 1.15;
    margin: 0 auto;
    page-break-after: always;   /* cada .page es una página en el PDF */
  }

  .page:last-child {
    page-break-after: auto;     /* la última ya no fuerza salto extra */
  }

  /* marco interior grueso */
.page-inner-border {
  position: absolute;
  top: 6mm;
  bottom: 6mm;
  left: 15mm;     /* 👈 más adentro izquierda */
  right: 15mm;    /* 👈 más adentro derecha */
  border: 4px solid #cfa72f;
  pointer-events: none;
}


  .content {
    position: relative;
    z-index: 1;
  }

  /* LOGO */
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

  /* TÍTULOS Y TEXTOS DEL CONTRATO */
  .page .content h1 {
    font-size: 10pt;
    text-align: center;
    text-transform: uppercase;
    margin: 0 0 3mm;   /* top en 0; lo controla .logo + h1 */
    font-weight: bold;
  }

  h2 {
    font-size: 10pt;
    text-align: center;
    text-transform: uppercase;
    margin: 4mm 0 1.5mm;
    font-weight: bold;
  }

  p {
    font-size: 10pt;
    text-align: justify;
    margin: 0 0 2mm;
  }

  .section-title {
    font-size: 10pt;
    font-weight: bold;
    text-transform: uppercase;
    text-align: center;
    margin: 4mm 0 1.5mm;
  }

  .page-sin-logo .logo + .section-title {
    margin-top: 1mm;
  }

  ul, ol {
    margin: 0 0 0 12mm;
    padding: 0;
  }

  li {
    font-size: 9pt;
    margin-bottom: 1.5mm;
    text-align: justify;
  }

  /* BLOQUE DE FIRMAS */
  .firmas-fila {
    display: flex;
    justify-content: space-between;
    margin-top: 8mm;
  }

  .firma-col {
    width: 48%;
    text-align: center;
    font-size: 10pt;
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

  /* Número de página */
  .page-number {
    position: absolute;
    bottom: 7mm;
     right: 16mm;
    font-size: 7pt;
  }

  /* ==== BOTONES FLOTANTES (SOLO PANTALLA) ==== */
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

  /* FOLIO EN ESQUINA SUPERIOR DERECHA */
  .folio {
    position: absolute;
    top: 8mm;        /* distancia desde el borde superior de la hoja */
    right: 14mm;     /* un poco dentro del marco dorado */
    font-size: 9pt;
    font-weight: bold;
  }

  /* ===== FORMULARIO Y RESUMEN ===== */

  h1 {
    font-size: 20px;
    margin-bottom: 10px;
  }

  .card {
    background: #ffffff;
    border: 1px solid #cfa72f;
    border-radius: 6px;
    padding: 15px 20px;
    max-width: 700px;
    margin-bottom: 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
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

  /* El formulario (card-form) se ve en pantalla */
.card-form {
  max-width: 900px;      /* ancho máximo de la tarjeta */
  margin: 30px auto;     /* auto a los lados = centrado horizontal */
}

  /* === MODO ESPECIAL SOLO PARA GENERAR PDF (html2pdf) === */
/* === MODO ESPECIAL SOLO PARA GENERAR PDF (html2pdf) === */
body.pdf-mode {
  margin: 0 !important;      /* ✅ IMPORTANTE */
  padding: 0 !important;
  background: #ffffff !important;
}

body.pdf-mode #contrato{
  margin: 0 !important;      /* ✅ evita offset extra */
}

body.pdf-mode .acciones,
body.pdf-mode .card-form{
  display: none !important;
}

/* ✅ Forzar tamaño carta en pdf-mode (igual que impresión) */
body.pdf-mode .page{
  width: 216mm !important;
  height: 279mm !important;
  margin: 0 !important;
  box-shadow: none !important;
  page-break-after: always;
}

body.pdf-mode .page:last-child{
  page-break-after: auto;
}

body.pdf-mode .page-inner-border{
  top: 6mm;
  bottom: 6mm;
  left: 30mm;
  right: 30mm;
}



/* =========================
   ✅ CONTRATO MÁS CHICO (GLOBAL)
   PÉGALO AL FINAL DEL <style>
========================= */

#contrato .page{
  font-size: 8.8pt !important;     /* tamaño base del contrato */
  line-height: 1.12 !important;  /* compacta un poco */
}

/* que todo lo demás herede el tamaño base */
#contrato .page p,
#contrato .page li,
#contrato .page .firma-col,
#contrato .page .folio{
  font-size: 1em !important;
}

/* títulos principales */
#contrato .page .content h1{
  font-size: 1.15em !important;
}

/* subtítulos y secciones */
#contrato .page h2,
#contrato .page .section-title{
  font-size: 1.05em !important;
}

/* opcional: listas un poco más compactas */
#contrato .page ul,
#contrato .page ol{
  margin-bottom: 2mm !important;
}


/* =========================================================
   ✅ CONFIG ÚNICA (HTML + PRINT + PDF)
   - Marco lateral: 10mm
   - Texto dentro del marco: 30mm
   - PDF: marco con ::before (estable)
========================================================= */

/* 1) TEXTO MÁS METIDO (SIEMPRE) */
#contrato .page{
  font-size: 8.7pt !important;     /* 👈 TAMAÑO DE LETRA AQUÍ */
  line-height: 1.12 !important;    /* 👈 ESPACIADO ENTRE LÍNEAS */

  padding-left: 30mm !important;
  padding-right: 30mm !important;
}

/* 2) MARCO MÁS ADENTRO (SIEMPRE) */
#contrato .page-inner-border{
  left: 10mm !important;
  right: 10mm !important;
  top: 6mm !important;
  bottom: 6mm !important;
}

/* 3) NUMERACIÓN adentro (alineada al texto) */
#contrato .page-number{
  right: 30mm !important;
}

/* 4) PDF MODE: NO cambies padding */
body.pdf-mode #contrato .page{
  font-size: 8.7pt !important;
  line-height: 1.12 !important;

  padding-left: 30mm !important;
  padding-right: 30mm !important;
}

/* 5) PDF MODE: usar marco estable con pseudo-elemento */
body.pdf-mode #contrato .page-inner-border{
  display: none !important;
}

body.pdf-mode #contrato .page{
  position: relative !important;
}

body.pdf-mode #contrato .page::before{
  content: "";
  position: absolute;
  top: 6mm;
  bottom: 6mm;
  left: 10mm;
  right: 10mm;
  border: 4px solid #cfa72f;
  pointer-events: none;
  z-index: 0;
}

body.pdf-mode #contrato .page .content{
  position: relative;
  z-index: 1;
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
    bottom: 2mm !important;
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
    right: 29mm !important;
    bottom: 2.9mm !important;
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
    Captura de datos del Inversionista
  </h1>

  <form id="form-prestamo" onsubmit="event.preventDefault(); actualizarDatos();">
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
        <label for="cp">Código postal</label>
        <input type="text" id="cp">
      </div>
      <div class="form-group" style="flex:2">
        <label for="direccion">Dirección</label>
        <input type="text" id="direccion" placeholder="Calle, número, colonia, ciudad, estado">
      </div>
    </div>

    <!-- NUEVA FILA: TELÉFONO Y CORREO -->
    <div class="form-row">
      <div class="form-group">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono">
      </div>
      <div class="form-group" style="flex:2">
        <label for="correo">Correo electrónico</label>
        <input type="email" id="correo">
      </div>
    </div>
    <!-- FIN NUEVA FILA -->

    <div class="form-row">
      <div class="form-row">
  <div class="form-group" style="flex:2">
    <label for="forma_pago">Forma de pago</label>
      <select id="forma_pago" required>
        <option value="">Selecciona…</option>
        <option value="spei">Transferencia SPEI</option>
        <option value="cheque">Cheque</option>
        <option value="efectivo">Efectivo</option>
      </select>
  </div>
</div>



      <div class="form-group">
        <label for="monto">Monto (MXN)</label>
        <input type="number" id="monto" min="0" step="0.01" required>
      </div>

      <div class="form-group">
        <label for="plazo">Plazo del préstamo</label>
        <select id="plazo" required>
          <option value="">Selecciona…</option>
          <option value="0.5">6 meses</option>
          <option value="1">1 año</option>
          <option value="2">2 años</option>
          <option value="5">5 años</option>
        </select>
      </div>
    </div>


<div class="form-row">

  <div class="form-group">
    <label for="beneficiario_nombre">Nombre Beneficiario</label>
    <input type="text" id="beneficiario_nombre">
  </div>

  <div class="form-group">
    <label for="beneficiario_curp">CURP Beneficiario</label>
    <input type="text" id="beneficiario_curp" maxlength="18">
  </div>

</div>

<div class="form-row">

  <div class="form-group">
    <label for="beneficiario_telefono">Teléfono Beneficiario</label>
    <input type="text" id="beneficiario_telefono">
  </div>

  <div class="form-group">
    <label for="beneficiario_parentesco">Parentesco</label>
    <input type="text" id="beneficiario_parentesco" placeholder="Ej: Esposa, Hijo, Hermano">
  </div>

</div>


    <button type="submit">Calcular y rellenar</button>
  </form>
</div>


  <!-- TODO EL CONTRATO (lo que va al PDF) -->
  <div id="contrato">
    <div class="page-wrapper">

      <!-- PRIMERA HOJA -->
      <div class="page">
        <div class="page-inner-border"></div>
          <!-- FOLIO DEL CONTRATO -->
    <div class="folio">Folio: CIP-INV-0001</div>
        <div class="content">

          <div class="logo">
            <img src="img/logo.png" alt="Logo CIP Financial México">
          </div>

          <h1>Contrato de Préstamo de Dinero o Mutuo</h1>

          <p>El presente contrato de préstamo personal o mutuo es celebrado en: C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160, en fecha <span class="fecha-hoy"></span> (en adelante, el &quot;Contrato&quot;).</p>

          <p style="text-align:center; margin:2mm 0;">&mdash; ENTRE &mdash;</p>

          <p>  <b class="nombre-prestamista"></b>, con número de RFC: <b class="rfc-prestamista"></b>,  y domicilio fiscal en  <b class="dir-prestamista"></b>, actuando en su propio nombre y derecho (el &ldquo;Prestamista&rdquo;).</p>

          <p style="text-align:center; margin:2mm 0;">&mdash; Y &mdash;</p>

          <p>Domicilio fiscal en C. José Ma Pino Suarez 119, Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160, actuando en su propio nombre y derecho (la &ldquo;Institución&rdquo;).</p>

          <p>  <b class="nombre-asesor"></b>, con número de RFC: <b class="rfc-asesor"></b>, y domicilio fiscal en <b class="domicilio-asesor"></b>, actuando en su propio nombre y derecho (el “<b class="rol-asesor"></b>”).</p>

          <p>Estos serán considerados individualmente como la &ldquo;Parte&rdquo; y conjuntamente como las &ldquo;Partes&rdquo;.</p>

          <div class="section-title">Declaraciones</div>

          <p>Que las Partes están interesadas en que el Prestamista realice un préstamo personal de dinero a la Institución, todo ello de conformidad con los términos y condiciones incluidos en este Contrato.</p>

          <p>Que, en virtud de lo anterior, las Partes, reconociéndose mutua y recíproca capacidad legal necesaria y suficiente para contratar y obligarse, habiendo entendido el alcance, contenido y consecuencias de las declaraciones emitidas en el presente, y tras haber llegado libre y voluntariamente a un acuerdo mutuo justo para ambas, deciden suscribir este Contrato el cual se regirá en lo sucesivo de conformidad con lo indicado en las siguientes:</p>

          <div class="section-title">Cláusulas</div>

          <div class="section-title">Objeto del Contrato</div>

          <p>En virtud del presente Contrato, el Prestamista entrega a la Institución, en concepto de préstamo personal, la cantidad total de   <strong><b id="contrato-monto-num"></b></strong> (<b id="contrato-monto-letra"></b>), cantidad que la Institución acepta y reconoce recibir. Tras la firma de este Contrato, el mismo constituirá carta de pago formal, eficaz y vinculante entre las Partes. (En adelante, el &ldquo;Préstamo&rdquo;).</p>

          <p>La entrega del Préstamo a la Institución tendrá lugar en: C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160, y con fecha del <b class="fecha-hoy"></b>,<p id="textoFormaPago" style="margin-top:10px;font-weight:600;"></p>

          <div class="section-title">Finalidad del Préstamo</div>

          <p>En lo relativo a la finalidad del Préstamo, las Partes acuerdan que el mismo será utilizado para fines comerciales, incluyendo de manera enunciativa pero no limitativa, para el siguiente fin:</p>

          <ul>
            <li>Sufragar los proyectos de Modalidad 10/40, de los futuros pensionados del IMSS.</li>
          </ul>

          <p>La Institución se compromete a utilizar el Préstamo única y exclusivamente para los fines indicados, sin que pueda destinarlo a otro fin diferente salvo que cuente con el consentimiento por escrito del Prestamista.</p>

          <div class="section-title">Rendimientos Ordinarios</div>

          <p>Mientras este Contrato esté en vigor, las Partes acuerdan que se devengará un Rendimiento ordinario simple al tipo del 20% anual. De aquí en adelante serán referenciados como los &quot;Rendimientos&quot; o el &quot;Rendimiento&quot; de manera indistinta.</p>

          <p>Salvo que las Partes acuerden expresamente lo contrario, estos Rendimientos comenzarán a devengarse en el momento en que el Préstamo sea entregado a la Institución. Sin perjuicio de lo anterior, las mismas estarán autorizadas para acordar periodos de gracia o carencia en donde el capital del Préstamo no generará Rendimientos. Para lo anterior, será necesario el acuerdo previo y por escrito de las Partes.</p>

          <p>Los Rendimientos se calcularán sobre el importe total del Préstamo.</p>

          <div class="section-title">Devolución del Préstamo</div>

          <p>Este Préstamo deberá ser devuelto en su totalidad al Prestamista en fecha <b class="fecha-devolucion"></b>.</p>

          <p>Las Partes, de común acuerdo, estarán habilitadas para pactar una prórroga de este Contrato de Préstamo en caso de que, llegado el momento del vencimiento de este, la deuda pendiente no se hubiese reintegrado totalmente. Para lo anterior, será necesario reflejar tal prórroga por escrito mediante un anexo al presente Contrato.</p>

          
        </div>
      </div>

      <!-- SEGUNDA HOJA -->
      <div class="page page-sin-logo">
        <div class="page-inner-border"></div>
            <div class="folio">Folio: CIP-INV-0001</div>
        <div class="content">

          <div class="logo">
            <img src="img/logo.png" alt="Logo CIP Financial México">
          </div>


          <div class="section-title">Amortización Anticipada del Préstamo</div>

          <p>En caso de que el Rendimiento sea superior al Rendimiento legal fijado en el Código Civil del Estado de México, la Institución, después de 6 meses contados desde que se celebró el Contrato, podrá reembolsar el Préstamo, cualquiera que sea el plazo fijado para ello, debiendo dar aviso al Prestamista con 2 meses de anticipación y pagando los Rendimientos vencidos.</p>

         

           <div class="section-title">Método de Pago</div>

          <p>El Préstamo será devuelto en forma de transferencia. Salvo que las Partes acuerden expresamente lo contrario, todo método de pago que requiera de entrega física (cheque, efectivo u otros) tendrá lugar en la vivienda del Prestamista.</p>

          <div class="section-title">Rendimientos Moratorios</div>

          <p>En caso de que la Institución no devuelva el Préstamo en los plazos acordados en este Contrato, se cobrará un Rendimiento moratorio al tipo del 10% anual. Las Partes acuerdan que estos Rendimientos se calcularán anualmente sobre el total adeudado, y se devengarán desde el día siguiente al vencimiento del plazo de pago o devolución del Préstamo, siendo por tanto exigibles sin necesidad de que el Prestamista tenga que realizar un requerimiento formal a la Institución, y continuarán devengándose hasta que se haya pagado el total de lo adeudado en virtud de este Contrato. En caso de que el Rendimiento moratorio acordado sea superior al legalmente permitido, se aplicará entonces éste último.</p>


          <div class="section-title">Obligaciones y Declaraciones de la Institución</div>

          <p>La Institución se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la normativa aplicable, y en particular declara y garantiza que:</p>

          <ul>
            <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
            <li>No se encuentra en situación de insolvencia económica ni bancarrota y dispone de suficiente poder adquisitivo para poder devolver el Préstamo en las condiciones aquí pactadas.</li>
            <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
            <li>Devolverá el Préstamo de conformidad con los términos y condiciones fijados en este Contrato.</li>
            <li>Asumirá todos los recargos e Rendimientos bancarios que hayan sido causados, directa o indirectamente, por el incumplimiento de la Institución en la devolución del Préstamo.</li>
            <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
          </ul>

          <div class="section-title">Obligaciones y Declaraciones del Prestamista</div>

          <p>El Prestamista se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la normativa aplicable, y en particular declara y garantiza que:</p>

          <ul>
            <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
            <li>Dispone de capacidad adquisitiva suficiente para realizar el Préstamo en favor de la Institución de conformidad con los términos y condiciones aquí incluidos.</li>
            <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
            <li>Prestará la cantidad de dinero acordada en este Contrato a la Institución en los plazos y condiciones incluidas en el mismo.</li>
            <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
          </ul>

          <div class="section-title">Incumplimiento y Resolución del Contrato</div>

          <p>El incumplimiento por parte de la Institución de cualquiera de las obligaciones contraídas en virtud de este Contrato, incluyendo de manera enunciativa, pero no limitativa, la devolución del Préstamo y los correspondientes Rendimientos devengados de conformidad con lo estipulado en este Contrato; facultará al Prestamista para:</p>

          <ul>
            <li>Resolver el mismo antes del plazo de vencimiento pactado, siempre que previamente el Prestamista hubiera requerido por escrito a la Institución para cumplir con sus obligaciones.</li>
            <li>Exigir la devolución inmediata de la totalidad del Préstamo, más los correspondientes Rendimientos devengados.</li>
            <li>Ejercer cuantas acciones legales el derecho habilite de cara a exigir el cumplimiento de las obligaciones contractuales y reclamar los daños y perjuicios que dicha resolución anticipada le hubiera podido originar.</li>
          </ul>

          <div class="section-title">Liquidación de Impuestos</div>

          <p>Este Contrato pasará a ser completamente vinculante entre las Partes tras ser debidamente firmado por las mismas sin necesidad de que sea elevado a público.</p>

         
          
          
        </div>
      </div>

      <!-- TERCERA HOJA -->
      <div class="page page-sin-logo">
        <div class="page-inner-border"></div>
            <div class="folio">Folio: CIP-INV-0001</div>
        <div class="content">

          <div class="logo">
            <img src="img/logo.png" alt="Logo CIP Financial México">
          </div>
 <div class="section-title">Domicilio a Efectos de Notificaciones</div>

          <p>Para efectos de notificaciones y comunicación de cualquier tipo relacionadas con este Contrato, el Prestamista podrá ser contactado en la dirección postal indicada en el encabezado de este Contrato, o a través de las siguientes vías:</p>

          <ul>
            <li>Nombre o razón social: <b id="prestamista-nombre">-</b></li>
            <li>E-mail: <b id="prestamista-correo">-</b></li>
            <li>No. Tel.: <b id="prestamista-telefono">-</b></li>
          </ul>

          <p>La Institución podrá ser contactada mediante el Representante Legal en la dirección postal indicada en el encabezado de este Contrato, o a través de las siguientes vías:</p>

          <ul>
              <li>
                Nombre o razón social:
                <b><?= htmlspecialchars($asesorNombre ?? '', ENT_QUOTES, 'UTF-8'); ?></b>
              </li>
              <li>
                E-mail:
                <b><?= htmlspecialchars($asesorCorreo ?? '', ENT_QUOTES, 'UTF-8'); ?></b>
              </li>
              <li>
                No. Tel.:
                <b><?= htmlspecialchars($asesorTelefono ?? '', ENT_QUOTES, 'UTF-8'); ?></b>
              </li>
          </ul>

          <p>En ese sentido, si alguna de las Partes desea cambiar esta información de contacto, deberá comunicárselo fehacientemente a la otra Parte. Para cualquier información informal relativa al día a día fruto de esta relación contractual, las Partes podrán utilizar las vías de teléfono y correo electrónico, pero las mismas no servirán como medio fehaciente.</p>


          <div class="section-title">Protección de Datos de Carácter Personal</div>

          <p>Las Partes se comprometen a cumplir con todas y cada una de las obligaciones incluidas por la normativa aplicable en materia de protección de datos personales, esto es, la Ley Federal de Protección de Datos Personales en Posesión de los Particulares.</p>

          <p>En ese sentido, las Partes declaran y reconocen que serán responsables del tratamiento de los datos personales de la otra parte, debiendo tratar los mismos de manera segura y confidencial, respetando siempre los requisitos y obligaciones incluidos por las referidas normativas. Los datos personales serán tratados con la finalidad de ejecutar correctamente este Contrato. Adicionalmente, los datos personales serán tratados con la finalidad de cumplir con las obligaciones legales en materia administrativa, contable, tributaria y financiera.</p>

          <p>Las Partes tratarán únicamente aquellos datos estrictamente necesarios, adecuados y pertinentes para dar cumplimiento con las finalidades señaladas y por ende no serán tratados de manera incompatible con las mismas.</p>

          <p>Adicionalmente, las Partes tratarán estos datos personales de manera confidencial y sobre los mismos se aplicarán las medidas técnicas y organizativas adecuadas y suficientes que garanticen la confidencialidad y privacidad de estos, así como su integridad y disponibilidad y la resiliencia permanente del sistema de información que los contienen.</p>


          <p>Los datos no serán comunicados a terceros sin autorización, salvo en los supuestos expresamente permitidos o exigibles conforme a la ley. Sin perjuicio de lo anterior, los datos personales podrán ser comunicados a terceros prestadores de servicios (como bancos u otras entidades financieras) en el marco de una relación contractual. No obstante, se garantiza que previamente a proceder con ello se suscribirá el correspondiente contrato de encargo de tratamiento que regularice referido acceso.</p>

          <p>Con carácter general, los datos personales serán conservados mientras este Préstamo continúe en vigor, y en todo caso, hasta la prescripción de la posible responsabilidad legal que pudiera derivarse de lo anterior.</p>

          <p>En cualquier momento, las Partes podrán ejercer los derechos de acceso, rectificación, cancelación y oposición, en las condiciones legalmente previstas y dirigiéndose por escrito a las direcciones indicadas en este Contrato. Asimismo, se informa a los interesados que les asiste el derecho a efectuar una reclamación ante el Instituto Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales en caso de que consideren que el tratamiento de sus datos no es el adecuado.</p>

          <div class="section-title">Origen de los Recursos</div>

          <p>Que los recursos con los cuales ha de pagar el Préstamo dispuesto han sido o serán obtenidos o generados a través de una fuente de origen lícito y que el destino que dará a los recursos obtenidos al amparo del presente Contrato será tan solo a fines permitidos por la ley, y que no se encuentran dentro de los supuestos establecidos en los artículos 139 Quáter y 400 Bis del Código Penal Federal.</p>

          <p>Se reitera que el presente Contrato se celebra con motivo de la entrega de un recurso económico <b>de origen lícito,</b> mismo que el Prestamista manifiesta y garantiza no proviene de actividades ilícitas o contrarias a la ley, de conformidad con lo dispuesto por la legislación civil vigente y en estricto apego a los principios de legalidad.</p>

          <p>En caso de controversia o cuestionamiento sobre la validez o licitud del presente instrumento, el Préstamo queda <b>fundamentado en los artículos 2534 y 2535 del Código Civil Federal</b> (en cuanto al contrato de mutuo) y, para su ejecución o exigibilidad judicial, en lo establecido por el Código <b>Nacional de Procedimientos Civiles y Familiares</b>, particularmente en lo relativo a los procedimientos de ejecución de obligaciones contractuales.</p>
          <p>Las Partes se comprometen a dirimir cualquier controversia conforme a derecho, garantizando la transparencia, buena fe y la legalidad del presente acto jurídico.</p>

          

         


        </div>
      </div>
      

      <div class="page page-sin-logo">
        <div class="page-inner-border"></div>
            <div class="folio">Folio: CIP-INV-0001</div>
        <div class="content">

          <div class="logo">
            <img src="img/logo.png" alt="Logo CIP Financial México">
          </div>
        
         <div class="section-title">Ley Aplicable y Tribunal Competente</div>

          <p>Cualquier conflicto que pueda surgir en relación con este Contrato será interpretado de conformidad con la ley federal aplicable a los Estados Unidos Mexicanos y al Estado de México. En caso de contradicción entre ambas, primará lo dispuesto en la ley estatal, salvo que se trate de materias expresamente reservadas a regulación federal.</p>

          <p>Las Partes se comprometen a buscar siempre de buena fe una solución amistosa ante cualquier disputa o conflicto que pudiera surgir relacionado con este Contrato, tratando de evitar en todo caso el acudir a la vía judicial o al arbitraje.</p>

          <p>Si las Partes no fueran capaces de resolver amistosamente estas disputas dentro de los 14 días siguientes al surgimiento de la disputa, las mismas acuerdan someterse de manera expresa e irrevocable a la jurisdicción y competencia de los juzgados y tribunales del domicilio de la Institución, con renuncia expresa a cualquier otra jurisdicción que le pudiera corresponder, salvo que esta fuera imperativa en aplicación de lo dispuesto en la ley aplicable.</p>

          <div class="section-title">Modificaciones</div>

          <p>Este Contrato de Préstamo únicamente podrá ser modificado mediante acuerdo por escrito firmado por todas las Partes.</p>
          <div class="section-title">Separabilidad</div>

          <p>En caso de existir contradicción entre cualquier cláusula de este Contrato y la ley aplicable, esta última prevalecerá, y las cláusulas que la contradigan se entenderán por no puestas. Adicionalmente, toda disposición normativa que por ley deba formar parte de este Contrato, se entenderá añadida al Contrato, formando parte de su contenido.</p>

          <p>La ilicitud, invalidez o ineficacia de alguna de las cláusulas de este Contrato no afectará al resto de cláusulas, las cuales seguirán vigentes y serán plenamente eficaces. Con respecto a las cláusulas afectadas, se entenderán por no puestas y las Partes se comprometen a negociar de buena fe un nuevo texto para esas cláusulas buscando siempre la misma finalidad que perseguía la cláusula original.</p>

          <p>Este Contrato de Préstamo constituye el acuerdo completo entre las Partes, y no existe ningún otro documento, término o condición relacionado con el mismo, ni verbal ni de ningún otro tipo.</p>

          <div class="section-title">Beneficiario</div>

          <p>En caso de fallecimiento o incapacidad de cualquiera de las Partes:</p>

          <ol>
            <li>El Prestamista podrá designar un beneficiario para que asuma las obligaciones derivadas del presente Contrato, incluyendo la devolución del Préstamo y el pago de los rendimientos correspondientes. Para ello, el Prestamista notificará a la Institución por escrito el nombre del beneficiario designado.</li>
            <li>La Institución podrá igualmente designar un beneficiario para recibir los pagos del Préstamo en caso de fallecimiento o incapacidad del Representante Legal de la Institución, notificando previamente al Prestamista por escrito el nombre del beneficiario designado.</li>
            <li>La designación del beneficiario será válida siempre y cuando dicha persona cumpla con los requisitos legales necesarios para asumir las obligaciones contractuales, y ambas Partes acuerden expresamente por escrito los términos y condiciones para dicha transmisión de derechos y obligaciones.</li>
            <li>En caso de que no se designe un beneficiario de manera explícita por alguna de las Partes, se considerará que los derechos y obligaciones del Contrato se transmiten a los herederos legales de la Parte fallecida, conforme a la legislación vigente aplicable.</li>
          </ol>

          <p>
          <strong>Beneficiario designado por el Prestamista:</strong>
          <span class="beneficiario_nombre_text">—</span>
          <br>
          <strong>CURP:</strong> <span class="beneficiario_curp_text">—</span>
          <br>
          <strong>Parentesco:</strong> <span class="beneficiario_parentesco_text">—</span>
          &nbsp;&nbsp;|&nbsp;&nbsp;
          <strong>Teléfono:</strong> <span class="beneficiario_tel_text">—</span>
          </p>

          <div class="section-title">Cesión del Contrato</div>

          <p>Las Partes no podrán ceder este Contrato salvo cuando cuenten con el consentimiento previo, expreso y por escrito de la otra Parte.</p>

          <p>Y EN PRUEBA DE CONFORMIDAD Y ACEPTACIÓN, las Partes firman este Contrato en fecha <span class="fecha-hoy"></span><br></p>

          <!-- FIRMAS -->
          <div class="firmas-fila">
            <div class="firma-col">
              <div class="firma-titulo">FIRMA DEL PRESTAMISTA</div>
              <div class="firma-espacio"></div>
              <div class="firma-texto">
                En su condición de PRESTAMISTA y en su propio nombre y derecho.<br>
                <span class="firma-nombre firma-prestamista"></span>
              </div>
            </div>

            <div class="firma-col">
              <div class="firma-titulo">FIRMA DEL <span class="rol-asesor"></span></div>
              <div class="firma-espacio"></div>
              <div class="firma-texto">
                En su condición de <span class="rol-asesor"></span> en su propio nombre y derecho.<br>
                      <span class="firma-nombre firma-asesor"><?= htmlspecialchars($asesorNombre ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
              </div>
            </div>
          </div>


      </div>
      
    </div> <!-- fin .page-wrapper -->
  </div>   <!-- fin #contrato -->

  <!-- ================== IMPRESIÓN Y DESCARGA PDF ================== -->
<script>
  function imprimirContrato() {
    window.print();
  }

function descargarPDF() {
  const element = document.getElementById('contrato');
  if (!element) return;

  document.body.classList.add('pdf-mode');

  // ✅ espera 1 frame para que se apliquen estilos y layout
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {

      const opt = {
        margin: 0,
        filename: 'contrato_prestamo.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: {
          scale: 2,
          useCORS: true,
          backgroundColor: '#ffffff',
          scrollX: 0,
          scrollY: 0
        },
        jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
      };

      html2pdf()
        .set(opt)
        .from(element)
        .save()
        .finally(() => {
          document.body.classList.remove('pdf-mode');
        });

    });
  });
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

<!-- ================== FECHAS Y RELLENADO DEL CONTRATO ================== -->
<script>
  // Fechas que vienen de BD (si es contrato existente)
  let FECHA_SOLICITUD_FIJA  = null; // Date
  let FECHA_DEVOLUCION_FIJA = null; // Date

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

  // Convierte 'YYYY-MM-DD' o 'YYYY-MM-DD HH:MM:SS' a Date
  function parseFechaSQLToDate(sql) {
    if (!sql) return null;
    const m = String(sql).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (!m) return null;
    const year  = parseInt(m[1], 10);
    const month = parseInt(m[2], 10) - 1;
    const day   = parseInt(m[3], 10);
    return new Date(year, month, day);
  }

  // Rellena todo el contrato con los datos del formulario
  function rellenarContrato() {
    // 1) Leer datos del formulario
    const nombre     = document.getElementById('nombre').value.trim().toUpperCase();
    const apPaterno  = document.getElementById('ap_paterno').value.trim().toUpperCase();
    const apMaterno  = document.getElementById('ap_materno').value.trim().toUpperCase();
    const rfc        = document.getElementById('rfc').value.trim().toUpperCase();
    const cp         = document.getElementById('cp').value.trim(); // CP es numérico, lo dejo igual
    const direccion  = document.getElementById('direccion').value.trim().toUpperCase();
    const monto      = parseFloat(document.getElementById('monto').value) || 0;
    const plazoRaw = document.getElementById('plazo').value;
    const plazoAnos = parseFloat(plazoRaw);
    const telefono   = document.getElementById('telefono').value.trim();
    const correo     = document.getElementById('correo').value.trim();

    const bNombre = document.getElementById('beneficiario_nombre')?.value.trim().toUpperCase();
    const bCurp   = document.getElementById('beneficiario_curp')?.value.trim().toUpperCase();
    const bTel = document.getElementById('beneficiario_telefono')?.value.trim();
    const bParen = document.getElementById('beneficiario_parentesco')?.value.trim().toUpperCase();

document.querySelectorAll('.beneficiario_nombre_text')
  .forEach(e => e.textContent = bNombre || '—');
     
document.querySelectorAll('.beneficiario_curp_text')
  .forEach(e => e.textContent = bCurp || '—');

document.querySelectorAll('.beneficiario_tel_text')
  .forEach(e => e.textContent = bTel || '—');


document.querySelectorAll('.beneficiario_parentesco_text')
  .forEach(e => e.textContent = bParen || '—');


    // 2) Fecha del contrato
    //    - Nuevo => hoy
    //    - Desde BD => FECHA_SOLICITUD_FIJA
    let fechaContratoDate;
    if (FECHA_SOLICITUD_FIJA instanceof Date) {
      fechaContratoDate = FECHA_SOLICITUD_FIJA;
    } else {
      fechaContratoDate = new Date();
    }
    const fechaContratoStr = fechaLargaMX(fechaContratoDate);

    // 3) Fecha de devolución
    //    - Desde BD => FECHA_DEVOLUCION_FIJA
    //    - Nuevo => hoy + plazo
    let fechaDevolucionDate;
    if (FECHA_DEVOLUCION_FIJA instanceof Date) {
      fechaDevolucionDate = FECHA_DEVOLUCION_FIJA;
    } else {
fechaDevolucionDate = new Date(fechaContratoDate);

if (!isNaN(plazoAnos)) {
  if (plazoAnos === 0.5) {
    // 6 meses
    fechaDevolucionDate.setMonth(fechaContratoDate.getMonth() + 6);
  } else {
    // 1, 2, 5 años
    fechaDevolucionDate.setFullYear(fechaContratoDate.getFullYear() + plazoAnos);
  }
}
    }
    const fechaDevolucionStr = fechaLargaMX(fechaDevolucionDate);

    // 4) Formatear monto a MXN
    const montoMXN = new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      minimumFractionDigits: 2
    }).format(monto);

    // 4.1) Convertir monto a letra
    const montoEnLetra = numeroALetrasPesos(monto);

    // 5) Nombre completo
    const nombreCompleto = `${nombre} ${apPaterno} ${apMaterno}`.replace(/\s+/g, ' ').trim();

    // 6) Rellenar RESUMEN
    const elResFechaContrato   = document.getElementById('res-fecha-contrato');
    const elResFechaDevolucion = document.getElementById('res-fecha-devolucion');
    const elResNombreCompleto  = document.getElementById('res-nombre-completo');
    const elResCp              = document.getElementById('res-cp');
    const elResDireccion       = document.getElementById('res-direccion');
    const elResMonto           = document.getElementById('res-monto');
    const elResMontoLetra      = document.getElementById('res-monto-letra');
    const elResPlazo           = document.getElementById('res-plazo');

    if (elResFechaContrato)   elResFechaContrato.textContent   = fechaContratoStr;
    if (elResFechaDevolucion) elResFechaDevolucion.textContent = fechaDevolucionStr;
    if (elResNombreCompleto)  elResNombreCompleto.textContent  = nombreCompleto || '—';
    if (elResCp)              elResCp.textContent              = cp || '—';
    if (elResDireccion)       elResDireccion.textContent       = direccion || '—';
    if (elResMonto)           elResMonto.textContent           = montoMXN;
    if (elResMontoLetra)      elResMontoLetra.textContent      = montoEnLetra;
if (elResPlazo) {
  elResPlazo.textContent = !isNaN(plazoAnos)
    ? (plazoAnos === 0.5 ? '6 meses' : `${plazoAnos} año(s)`)
    : '—';
}

    // 7) Actualizar fechas dentro del contrato
    document.querySelectorAll('.fecha-hoy').forEach(el => {
      el.textContent = fechaContratoStr;
    });
    document.querySelectorAll('.fecha-devolucion').forEach(el => {
      el.textContent = fechaDevolucionStr;
    });

    // 8) Nombre del Prestamista en el texto
    document.querySelectorAll('.nombre-prestamista').forEach(el => {
      el.textContent = nombreCompleto || 'Aquí va el nombre completo';
    });

    // 8.1) Firma del PRESTAMISTA (cliente)
    document.querySelectorAll('.firma-prestamista').forEach(el => {
      el.textContent = nombreCompleto || 'Aquí va el nombre completo';
    });

    // 9) RFC del Prestamista
    document.querySelectorAll('.rfc-prestamista').forEach(el => {
      el.textContent = rfc || 'VIRE961016IV1';
    });

    // 10) Monto en el contrato
    const spanMontoNum   = document.getElementById('contrato-monto-num');
    const spanMontoLetra = document.getElementById('contrato-monto-letra');
    if (spanMontoNum)   spanMontoNum.textContent   = montoMXN;
    if (spanMontoLetra) spanMontoLetra.textContent = montoEnLetra;

    // 11) Datos de contacto del Prestamista
    const spanNombre   = document.getElementById('prestamista-nombre');
    const spanCorreo   = document.getElementById('prestamista-correo');
    const spanTelefono = document.getElementById('prestamista-telefono');

    if (spanNombre)   spanNombre.textContent   = nombreCompleto || '—';
    if (spanCorreo)   spanCorreo.textContent   = correo || '—';
    if (spanTelefono) spanTelefono.textContent = telefono || '—';

    // 12) Dirección completa en encabezados, etc.
    const direccionCompleta = (direccion && cp)
      ? `${direccion}, C.P. ${cp}`
      : direccion || '';

    document.querySelectorAll('.dir-prestamista').forEach(el => {
      el.textContent = direccionCompleta || '—';
    });
  }

  // Al cargar la página, si todavía no sabemos la fecha de BD,
  // mostramos al menos la fecha de hoy en .fecha-hoy
  document.addEventListener('DOMContentLoaded', () => {
    const hoyFormateado = fechaLargaMX(new Date());
    if (!FECHA_SOLICITUD_FIJA) {
      document.querySelectorAll('.fecha-hoy').forEach(el => {
        el.textContent = hoyFormateado;
      });
    }
  });
</script>

<!-- ================== NÚMERO A LETRAS (PESOS MXN) ================== -->
<script>
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
        if (cientos === 1) {
          letras = singular;
        } else {
          letras = convertirNumero(cientos) + ' ' + plural;
        }
      }
      if (resto > 0) {
        letras += (letras ? ' ' : '') + convertirNumero(resto);
      }
      return letras;
    }

    if (num < 20) {
      return unidades[num];
    } else if (num < 100) {
      const d = Math.floor(num / 10);
      const r = num % 10;
      if (num >= 20 && num < 30) {
        if (num === 20) return 'veinte';
        return 'veinti' + unidades[r];
      }
      return decenas[d] + (r ? ' y ' + unidades[r] : '');
    } else if (num < 1000) {
      if (num === 100) return 'cien';
      const c = Math.floor(num / 100);
      const r = num % 100;
      return centenas[c] + (r ? ' ' + convertirNumero(r) : '');
    } else if (num < 1000000) {
      return seccion(num, 1000, 'mil', 'mil');
    } else if (num < 1000000000000) {
      return seccion(num, 1000000, 'un millón', 'millones');
    } else {
      return 'número demasiado grande';
    }
  }
</script>

<!-- ================== DATOS DEL ASESOR DESDE PHP ================== -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const asesor = (typeof ASESOR_PHP !== 'undefined' && ASESOR_PHP) ? ASESOR_PHP : {};

  const nombreCompleto = [
    asesor.nombre || '',
    asesor.apellido_paterno || '',
    asesor.apellido_materno || ''
  ].join(' ').replace(/\s+/g, ' ').trim();

  const rfc        = asesor.rfc || '';
  // 🔹 Usamos 'direccion' (como viene en la sesión) y, por si acaso,
  //    caemos a 'domicilio' si algún día lo cambias en PHP.
  const direccion  = (asesor.direccion || asesor.domicilio || '').replace(/\s+/g, ' ').trim();
  const rol        = asesor.rol || '';

  const elNombre    = document.querySelector('.nombre-asesor');
  const elRfc       = document.querySelector('.rfc-asesor');
  const elDomicilio = document.querySelector('.domicilio-asesor');
  const elRol       = document.querySelector('.rol-asesor');
    document.querySelectorAll('.rol-asesor').forEach(el => el.textContent = (rol || '').toUpperCase());


  if (elNombre)    elNombre.textContent    = nombreCompleto;
  if (elRfc)       elRfc.textContent       = rfc;
  if (elDomicilio) elDomicilio.textContent = direccion;
  if (elRol)       elRol.textContent       = rol;
});
</script>

<!-- ================== GUARDAR EN BD + FOLIO + BLOQUEAR FORM + GET ================== -->
<script>
  function getQueryParam(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

function obtenerDatosFormulario() {
  const id = window.ULTIMA_INVERSION_ID || 0;

  const datos = {
    id:         id,
    nombre:     document.getElementById('nombre').value.trim(),
    ap_paterno: document.getElementById('ap_paterno').value.trim(),
    ap_materno: document.getElementById('ap_materno').value.trim(),
    rfc:        document.getElementById('rfc').value.trim(),
    cp:         document.getElementById('cp').value.trim(),
    direccion:  document.getElementById('direccion').value.trim(),
    telefono:   document.getElementById('telefono').value.trim(),
    correo:     document.getElementById('correo').value.trim(),
    monto:      document.getElementById('monto').value,
    plazo:      document.getElementById('plazo').value,

    // ✅ NUEVO
    forma_pago: (document.getElementById('forma_pago')?.value || '').trim(),
    beneficiario_nombre: document.getElementById('beneficiario_nombre')?.value.trim(),
beneficiario_curp: document.getElementById('beneficiario_curp')?.value.trim(),
beneficiario_telefono: document.getElementById('beneficiario_telefono')?.value.trim(),
beneficiario_parentesco: document.getElementById('beneficiario_parentesco')?.value.trim(),

    
  };

  console.log('DATOS A GUARDAR:', datos);
  return datos;
}


  async function guardarInversion(datos) {
    const btn = document.querySelector('#form-prestamo button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Guardando...';
    }

    try {
      const resp = await fetch('app/controllers/invercion/guardar_inversion.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos)
      });

      const contentType = resp.headers.get('Content-Type') || '';

      if (!contentType.includes('application/json')) {
        const text = await resp.text();
        console.error('Respuesta NO JSON:', resp.status, text);
        throw new Error('El servidor devolvió una página HTML (' + resp.status + '), revisa la ruta del PHP.');
      }

      const json = await resp.json();
      console.log('RESPUESTA GUARDAR:', json);

      if (!resp.ok || !json.ok) {
        throw new Error(json.error || 'Error al guardar');
      }

      window.ULTIMA_INVERSION_ID = json.inversion_id;
      return json;

    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Calcular y rellenar';
      }
    }
  }

  let FORM_BLOQUEADO = false;
  window.ULTIMA_INVERSION_ID = window.ULTIMA_INVERSION_ID || 0;

async function actualizarDatos() {
  // ✅ si es nuevo: id = 0
  // ✅ si es edición: id = ULTIMA_INVERSION_ID (ya viene en obtenerDatosFormulario)
  rellenarContrato();
  const datos = obtenerDatosFormulario();

  try {
    const res = await guardarInversion(datos);

    if (res.ok) {
      if (res.folio) {
        document.querySelectorAll('.folio').forEach(el => {
          el.textContent = 'Folio: ' + res.folio;
        });
      }

      // ✅ ya existe, bloquear todo de nuevo
      FORM_BLOQUEADO = true;
      document.querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
        .forEach(el => el.disabled = true);

      const card = document.querySelector('.card-form');
      if (card) card.style.display = 'none';

      // ✅ mostrar botón editar
      const btnEditar = document.getElementById('btn-editar');
      if (btnEditar) btnEditar.style.display = 'inline-block';
    }
  } catch (err) {
    console.error(err);
    alert('No se pudo guardar: ' + err.message);
  }
}


  function cargarInversionEnFormulario(inv) {
    if (!inv) return;

    const byId = (id) => document.getElementById(id);

    if (byId('nombre'))      byId('nombre').value      = inv.nombre        || '';
    if (byId('ap_paterno'))  byId('ap_paterno').value  = inv.ap_paterno    || '';
    if (byId('ap_materno'))  byId('ap_materno').value  = inv.ap_materno    || '';
    if (byId('rfc'))         byId('rfc').value         = inv.rfc           || '';
    if (byId('cp'))          byId('cp').value          = inv.codigo_postal || '';
    if (byId('direccion'))   byId('direccion').value   = inv.direccion     || '';
    if (byId('telefono'))    byId('telefono').value    = inv.telefono      || '';
    if (byId('correo'))      byId('correo').value      = inv.correo        || '';
    if (byId('monto'))       byId('monto').value       = inv.monto         || '';
if (byId('plazo')) {
  const plazoSelect = byId('plazo');

  let plazo = String(inv.plazo_anios ?? inv.plazo ?? '').trim();

  // Normalizar valores que pueden venir de BD
  if (plazo === '6' || plazo === '6 meses' || plazo === '0.50' || plazo === '0.5') {
    plazo = '0.5';
  } else if (plazo === '12' || plazo === '12 meses' || plazo === '1.00' || plazo === '1 año' || plazo === '1') {
    plazo = '1';
  } else if (plazo === '24' || plazo === '24 meses' || plazo === '2.00' || plazo === '2 años' || plazo === '2') {
    plazo = '2';
  } else if (plazo === '60' || plazo === '60 meses' || plazo === '5.00' || plazo === '5 años' || plazo === '5') {
    plazo = '5';
  }

  plazoSelect.value = plazo;

  plazoSelect.dispatchEvent(new Event('change', { bubbles: true }));
  plazoSelect.dispatchEvent(new Event('input', { bubbles: true }));
}
    if (byId('beneficiario_nombre')) byId('beneficiario_nombre').value = inv.beneficiario_nombre || '';
if (byId('beneficiario_curp')) byId('beneficiario_curp').value = inv.beneficiario_curp || '';
if (byId('beneficiario_telefono')) byId('beneficiario_telefono').value = inv.beneficiario_telefono || '';
if (byId('beneficiario_parentesco')) byId('beneficiario_parentesco').value = inv.beneficiario_parentesco || '';

    // ✅ forma de pago (BD)
if (byId('forma_pago')) {
  byId('forma_pago').value = inv.forma_pago || '';
  byId('forma_pago').dispatchEvent(new Event('change')); // ✅ actualiza el texto
}


    window.ULTIMA_INVERSION_ID = inv.id;

    // ✅ Guardar fechas que vienen de la BD
    FECHA_SOLICITUD_FIJA  = parseFechaSQLToDate(inv.fecha_solicitud);
    FECHA_DEVOLUCION_FIJA = parseFechaSQLToDate(inv.fecha_devolucion);

    // Rellenar contrato con esos datos + fechas fijas
    rellenarContrato();

    if (inv.folio) {
      document.querySelectorAll('.folio').forEach(el => {
        el.textContent = 'Folio: ' + inv.folio;
      });
    }

    const btnEditar = document.getElementById('btn-editar');
    if (btnEditar) {
      btnEditar.style.display = 'inline-block';
    }

    FORM_BLOQUEADO = true;

    document
      .querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
      .forEach(el => el.disabled = true);

    const card = document.querySelector('.card-form');
    if (card) {
      card.style.display = 'none';
    }
    const fp = document.getElementById('forma_pago');
if (fp) {
  fp.value = inv.forma_pago || '';
  fp.dispatchEvent(new Event('change')); // ✅ pinta el texto del contrato
}
  }

  function activarEdicion() {
    FORM_BLOQUEADO = false;

    const card = document.querySelector('.card-form');
    if (card) {
      card.style.display = '';
    }

    document
      .querySelectorAll('#form-prestamo input, #form-prestamo select, #form-prestamo button')
      .forEach(el => {
        if (el.id === 'plazo') {
          el.disabled = true; // plazo no se toca en edición
        } else {
          el.disabled = false;
        }
      });

    const btn = document.querySelector('#form-prestamo button[type="submit"]');
    if (btn) {
      btn.textContent = 'Actualizar y rellenar';
    }

    const nombre = document.getElementById('nombre');
    if (nombre) nombre.focus();
  }

  window.activarEdicion = activarEdicion;

  document.addEventListener('DOMContentLoaded', async () => {
    const id = getQueryParam('id');
    if (!id) return; // modo nuevo

    try {
      const resp = await fetch('app/controllers/invercion/obtener_inversion.php?id=' + encodeURIComponent(id));
      const contentType = resp.headers.get('Content-Type') || '';

      if (!contentType.includes('application/json')) {
        const text = await resp.text();
        console.error('Respuesta NO JSON al obtener inversión:', resp.status, text);
        return;
      }

      const json = await resp.json();
      console.log('RESPUESTA obtener_inversion:', json);

      if (!json.ok) {
        alert('No se pudo cargar el contrato: ' + (json.error || 'Error desconocido'));
        return;
      }

      // ⚠️ Asegúrate de que en el JSON vengan:
      // id, nombre, ap_paterno, ap_materno, rfc, codigo_postal,
      // direccion, telefono, correo, monto, plazo_anios,
      // fecha_solicitud, fecha_devolucion, folio, estado
      cargarInversionEnFormulario(json.inversion);

    } catch (e) {
      console.error('Error cargando inversión:', e);
      alert('Error al cargar el contrato en pantalla');
    }
  });


function volverListado() {
  const params = new URLSearchParams(window.location.search);
  const fromRaw = params.get('from');

  if (fromRaw) {
    try {
      const from = decodeURIComponent(fromRaw);
      window.location.href = from;
      return;
    } catch (e) {
      // si por alguna razón no venía encoded
      window.location.href = fromRaw;
      return;
    }
  }

  // fallback: si no hay from
  if (document.referrer) {
    const ref = new URL(document.referrer, window.location.href);
    if (ref.origin === window.location.origin) {
      window.location.href = ref.href;
      return;
    }
  }

  // último fallback: mándalo a un default
  window.location.href = '/sempiternal/public/resumen.php';
}




// Por si lo quieres usar en inline (ya lo hicimos arriba)
window.volverListado = volverListado;

</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const formaPago = document.getElementById('forma_pago');
  const textoPago = document.getElementById('textoFormaPago');
  if (!formaPago || !textoPago) return;

  const pintarTexto = () => {
    let html = '';

    switch (formaPago.value) {
      case 'spei':
        html = `La transferencia se realizará a la siguiente cuenta bancaria 
        <b>INBURSA Clabe Interbancaria: 036441500738804892</b>.`;
        break;

      case 'cheque':
        html = `El pago será realizado <b>en forma de cheque</b>.`;
        break;

      case 'efectivo':
        html = `El pago será entregado <b>en forma de efectivo</b>.`;
        break;

      default:
        html = '';
    }

    textoPago.innerHTML = html;
  };

  formaPago.addEventListener('change', pintarTexto);

  // ✅ para que si ya viene seleccionado (por BD o recarga) lo pinte
  pintarTexto();
});

</script>




</body>
</html>
