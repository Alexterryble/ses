<?php
require_once __DIR__ . '/../app/controllers/auth/require_login.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="UTF-8">
  <title>Contrato - Hoja 1</title>
  <link rel="stylesheet" href="/css/contrato/contrato.css" />
  <style>
    .hidden{display:none!important}
  .huellas-mini{
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 30px;
  margin: 10px 0 18px;
  width: 100%;
}

.huella-mini-box{
  position: relative;
  width: 100px;
  height: 110px;
  border: 1.5px solid #b7c6d9;
  border-radius: 10px;
  background: #fff;
}

.huella-mini-label{
  position: absolute;
  top: -12px;
  left: 7px;
  display: inline-block;
  padding: 2px 14px 3px;
  background: #fff;
  border: 1.5px solid #b7c6d9;
  border-radius: 999px;
  font-family: Arial, Helvetica, sans-serif;
  font-size: 7px;
  font-weight: 700;
  line-height: 1.05;
  text-align: center;
  color: #0b3b7a;
  text-transform: uppercase;
  letter-spacing: .2px;
}

.btn--back{
  border:1px solid #6c757d;
  background:#fff;
  color:#495057;
}

.btn--back:hover{
  background:#f1f3f5;
}

/* ===== Ajuste de colores del contrato ===== */

/* color principal nuevo */
:root{
  --cip-azul:#0b3b7a;
  --cip-azul-2:#1f5e9c;
  --cip-borde:#b7c6d9;
  --cip-borde-fuerte:#8ea9c4;
}

/* Bordes generales que antes se veían dorados */
.page,
.encabezado,
.firma,
.firmas,
.firmas-2col,
.signature-modern,
.signature-divider,
.signature-line,
.contrato-box,
.bloque,
.seccion,
.tabla,
.tabla th,
.tabla td{
  border-color: var(--cip-borde) !important;
}

/* Títulos o separadores que pudieran traer dorado */
h2, h3, h4,
.titulo h2,
.titulo h3,
.subtitulo,
.clausula-titulo{
  color: var(--cip-azul) !important;
}

/* Si hay líneas decorativas o pseudo-elementos */
hr,
.divider,
.linea,
.signature-divider,
.signature-line{
  border-color: var(--cip-borde-fuerte) !important;
  background: var(--cip-borde-fuerte) !important;
}

/* Número de contrato / folio en azul */
.contrato-num,
.contrato-num span,
#out_folio{
  color: var(--cip-azul) !important;
  font-weight: 700 !important;
}

/* Si el folio tiene fondo o borde rojo en el css externo */
.folio,
.folio-box,
.badge-folio,
.contrato-num{
  border-color: var(--cip-azul) !important;
}

/* Huellas mini también combinadas en azul */
.huella-mini-box{
  border: 1.5px solid var(--cip-borde) !important;
}

.huella-mini-label{
  border: 1.5px solid var(--cip-borde) !important;
  color: var(--cip-azul) !important;
}

/* Botón regresar ya acorde al azul */
.btn--back{
  border:1px solid var(--cip-borde-fuerte) !important;
  color:var(--cip-azul) !important;
}

.btn--back:hover{
  background:#eef4fb !important;
}


/* =========================================================
   SWEETALERT2 - DISEÑO CIP CLARO / PROFESIONAL
   Pegar al final del <style>
========================================================= */

/* Capa del modal */
.swal2-container {
  z-index: 999999 !important;
}

/* Fondo oscuro transparente */
.swal2-backdrop-show {
  background: rgba(15, 23, 42, 0.38) !important;
}

/* Caja principal */
.swal2-popup.cip-swal {
  width: 430px !important;
  max-width: calc(100vw - 32px) !important;
  border-radius: 22px !important;
  padding: 34px 32px 28px !important;
  background: #ffffff !important;
  color: #1e293b !important;
  border: 1px solid rgba(183, 198, 217, 0.9) !important;
  box-shadow: 0 24px 70px rgba(15, 23, 42, 0.24) !important;
}

/* Ícono success */
.swal2-popup.cip-swal .swal2-icon.swal2-success {
  border-color: #b7d7bd !important;
  color: #4f9d5d !important;
  margin-top: 6px !important;
}

.swal2-popup.cip-swal .swal2-icon.swal2-success .swal2-success-ring {
  border-color: rgba(79, 157, 93, 0.28) !important;
}

.swal2-popup.cip-swal .swal2-icon.swal2-success [class^="swal2-success-line"] {
  background-color: #4f9d5d !important;
}

/* Ícono error */
.swal2-popup.cip-swal .swal2-icon.swal2-error {
  border-color: #fecaca !important;
  color: #dc2626 !important;
}

.swal2-popup.cip-swal .swal2-x-mark-line-left,
.swal2-popup.cip-swal .swal2-x-mark-line-right {
  background-color: #dc2626 !important;
}

/* Spinner/loading */
.swal2-popup.cip-swal .swal2-loader {
  border-color: #0b5f89 transparent #0b5f89 transparent !important;
}

/* Título */
.swal2-title.cip-swal-title {
  color: #0b3b7a !important;
  font-size: 29px !important;
  font-weight: 800 !important;
  line-height: 1.15 !important;
  margin: 14px 0 10px !important;
  font-family: Georgia, "Times New Roman", serif !important;
}

/* Texto */
.swal2-html-container.cip-swal-text {
  color: #334155 !important;
  font-size: 16px !important;
  font-weight: 500 !important;
  line-height: 1.45 !important;
  margin: 8px 0 6px !important;
}

.swal2-html-container.cip-swal-text strong {
  color: #1e293b !important;
  font-weight: 800 !important;
}

/* Contenedor de botones */
.swal2-actions {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  gap: 14px !important;
  margin-top: 26px !important;
}

/* Quita diseño default raro */
.swal2-styled {
  box-shadow: none !important;
  outline: none !important;
}

/* Botón AVANZAR / CONFIRMAR */
.swal2-confirm.cip-swal-confirm,
button.swal2-confirm.cip-swal-confirm {
  min-width: 126px !important;
  height: 43px !important;
  background: linear-gradient(135deg, #0b5f89, #0876a8) !important;
  color: #ffffff !important;
  border: 1px solid #0876a8 !important;
  border-radius: 11px !important;
  padding: 0 24px !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  letter-spacing: 0.1px !important;
  box-shadow: 0 10px 22px rgba(8, 118, 168, 0.30) !important;
  cursor: pointer !important;
  transition: all 0.18s ease !important;
}

.swal2-confirm.cip-swal-confirm:hover,
button.swal2-confirm.cip-swal-confirm:hover {
  background: linear-gradient(135deg, #084e73, #066894) !important;
  border-color: #066894 !important;
  transform: translateY(-1px) !important;
  box-shadow: 0 13px 26px rgba(8, 118, 168, 0.34) !important;
}

.swal2-confirm.cip-swal-confirm:focus,
button.swal2-confirm.cip-swal-confirm:focus {
  box-shadow: 0 0 0 4px rgba(8, 118, 168, 0.18) !important;
}

/* Botón SALIR / DENY */
.swal2-deny.cip-swal-cancel,
button.swal2-deny.cip-swal-cancel {
  min-width: 106px !important;
  height: 43px !important;
  background: #eef4fb !important;
  color: #334155 !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 11px !important;
  padding: 0 22px !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08) !important;
  cursor: pointer !important;
  transition: all 0.18s ease !important;
}

.swal2-deny.cip-swal-cancel:hover,
button.swal2-deny.cip-swal-cancel:hover {
  background: #dbeafe !important;
  color: #0b3b7a !important;
  border-color: #b7c6d9 !important;
  transform: translateY(-1px) !important;
}

/* Botón CANCELAR / QUEDARME AQUÍ */
.swal2-cancel.cip-swal-cancel,
button.swal2-cancel.cip-swal-cancel {
  min-width: 126px !important;
  height: 43px !important;
  background: #f8fafc !important;
  color: #475569 !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 11px !important;
  padding: 0 22px !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06) !important;
  cursor: pointer !important;
  transition: all 0.18s ease !important;
}

.swal2-cancel.cip-swal-cancel:hover,
button.swal2-cancel.cip-swal-cancel:hover {
  background: #e2e8f0 !important;
  color: #0f172a !important;
  transform: translateY(-1px) !important;
}

/* Botón ERROR */
.swal2-confirm.cip-swal-error,
button.swal2-confirm.cip-swal-error {
  min-width: 116px !important;
  height: 43px !important;
  background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
  color: #ffffff !important;
  border: 1px solid #b91c1c !important;
  border-radius: 11px !important;
  padding: 0 24px !important;
  font-size: 15px !important;
  font-weight: 800 !important;
  box-shadow: 0 10px 22px rgba(220, 38, 38, 0.25) !important;
  cursor: pointer !important;
}

.swal2-confirm.cip-swal-error:hover,
button.swal2-confirm.cip-swal-error:hover {
  background: linear-gradient(135deg, #b91c1c, #991b1b) !important;
  transform: translateY(-1px) !important;
}

/* Responsive */
@media (max-width: 480px) {
  .swal2-popup.cip-swal {
    width: calc(100vw - 24px) !important;
    padding: 30px 22px 24px !important;
    border-radius: 18px !important;
  }

  .swal2-title.cip-swal-title {
    font-size: 25px !important;
  }

  .swal2-html-container.cip-swal-text {
    font-size: 15px !important;
  }

  .swal2-actions {
    gap: 10px !important;
    flex-wrap: wrap !important;
  }

  .swal2-confirm.cip-swal-confirm,
  .swal2-deny.cip-swal-cancel,
  .swal2-cancel.cip-swal-cancel {
    min-width: 120px !important;
  }
}
</style>

</head>
<body>
<!-- ===== FORMULARIO DE PRESTATARIO (rediseño) ===== -->
<div class="print-hidden ui-card" id="ui-prestatario">
  <div class="ui-card__header">
    <div>
      <h3>Datos del Prestatario</h3>
      <p class="ui-sub">Selecciona de la lista o captura manualmente.</p>
    </div>
  </div>

  <!-- Selección rápida -->
  <div class="ui-row">
<div class="ui-field">
  <label for="sel_persona">Seleccionar prestatario</label>
  <select id="sel_persona" class="ui-input">
    <option value="" disabled selected>Selecciona persona…</option>
    <option value="1">EDER LOPEZ DOLORES</option>
    <option value="2">ANA YELI GARCIA PEREZ</option>
    <option value="3">HORACIO ANTONIO RAMÍREZ</option>
    <option value="4">MARIO COLÍN MARTÍNEZ</option>
    <option value="5">VICENTE GONZÁLEZ ROJAS</option>
    <option value="6">CINTHIA HERNÁNDEZ TÉLLEZ</option>
    <option value="7">EMANUEL JESÚS MONROY GONZÁLEZ</option>
    <option value="8">MARÍA DE LOURDES TÉLLEZ LORENZO</option>
    <option value="9">MERCEDES TORRES TORRES</option>
  </select>
 <div class="ui-field ui-col-3">
  <label for="tipo_contrato">Tipo de Contrato</label>
  <select id="tipo_contrato" class="ui-input">
    <option value="40" selected>PERSONAL 40 RETROACTIVO</option>
    <option value="10">PERSONAL 10</option>
  </select>
</div>

</div>
    <div class="ui-field ui-field--sm">
      <label for="plazo_meses">Plazo (meses)</label>
      <select id="plazo_meses" class="ui-input">
        <option value="4">4</option>
        <option value="5">5</option>
        <option value="6">6</option>
        <option value="7">7</option>
        <option value="8">8</option>
        <option value="9">9</option>
        <option value="10">10</option>
        <option value="11">11</option>
        <option value="12">12</option>
        <option value="13">13</option>
        <option value="14">14</option>
        <option value="15">15</option>
        <option value="16">16</option>
      </select>
    </div>
  </div>

  <!-- Captura manual -->
  <div class="ui-grid">
    <div class="ui-field ui-col-2">
      <label for="m_prest_nombre">Nombre / Razón social</label>
      <input id="m_prest_nombre" type="text" class="ui-input" placeholder="Ej. EDER LOPEZ DOLORES">
    </div>
    <div class="ui-field">
      <label for="m_prest_rfc">RFC</label>
      <input id="m_prest_rfc" type="text" class="ui-input" placeholder="Ej. LODE900317D31">
    </div>
    <div class="ui-field ui-col-3">
      <label for="m_prest_dir">Dirección</label>
      <textarea id="m_prest_dir" rows="3" class="ui-input" placeholder="Calle, número&#10;Colonia&#10;Municipio, Estado C.P. 00000"></textarea>
      <small class="ui-hint">Puedes escribir en varias líneas.</small>
    </div>
    <div class="ui-field ui-col-2">
      <label for="m_prest_email">E-mail</label>
      <input id="m_prest_email" type="email" class="ui-input" placeholder="correo@ejemplo.com">
    </div>
    <div class="ui-field">
      <label for="m_prest_tel">Teléfono</label>
      <input id="m_prest_tel" type="text" class="ui-input" placeholder="0000000000">
    </div>
    <div class="ui-field ui-col-3">
  <label for="m_monto">Monto del préstamo</label>
  <input id="m_monto" type="text" class="ui-input" placeholder="609729.83">
  <small class="ui-hint">Escribe sin $ (ej. 609729.83). Se formatea automáticamente.</small>
</div>


<div class="ui-field ui-col-3">
  <label for="metodo_entrega">Forma de Entrega</label>
  <select id="metodo_entrega" class="ui-input">
    <option value="efectivo" selected>Efectivo</option>
    <option value="cheque">Cheque</option>
    <option value="transferencia">Transferencia</option>
  </select>
</div>

<div id="transferencia_fields" class="ui-grid hidden">
  <div class="ui-field ui-col-3">
    <label for="m_prest_banco">Banco (opcional)</label>
    <input id="m_prest_banco" type="text" class="ui-input" placeholder="Nombre del banco">
  </div>
  <div class="ui-field ui-col-3">
    <label for="m_prest_clabe">CLABE o Número de Tarjeta</label>
    <input id="m_prest_clabe" type="text" class="ui-input" placeholder="012345678912345678">
  </div>
</div>


<div class="ui-field ui-col-3" id="beneficiario_field">
  <label for="beneficiario_ctrl">Beneficiario</label>
  <select id="beneficiario_ctrl" class="ui-input" hidden></select>
  <input  id="beneficiario_ctrl_input" type="text" class="ui-input" placeholder="Nombre completo" hidden>
</div>

<!-- Extra de beneficiario SOLO si eligen “Otro…” -->
<div id="bene_extra_wrap" class="ui-grid hidden">
  <div class="ui-field ui-col-3">
    <label for="bene_extra_nombre">Nombre completo</label>
    <input id="bene_extra_nombre" type="text" class="ui-input" placeholder="Nombre completo">
  </div>
<div class="ui-field">
  <label for="bene_extra_parentesco">Parentesco</label>
  <select id="bene_extra_parentesco" class="ui-input">
    <option value="">Selecciona…</option>

    <!-- Núcleo familiar -->
    <option value="ESPOSA/O">Esposa/o</option>
    <option value="CONCUBINA/O">Concubina/o</option>
    <option value="PAREJA">Pareja</option>
    <option value="PADRE/MADRE">Padre/Madre</option>
    <option value="HIJO/A">Hijo/a</option>
    <option value="HERMANO/A">Hermano/a</option>

    <!-- Familia extendida -->
    <option value="ABUELO/A">Abuelo/a</option>
    <option value="NIETO/A">Nieto/a</option>
    <option value="TIO/TIA">Tío/Tía</option>
    <option value="SOBRINO/A">Sobrino/a</option>
    <option value="PRIMO/A">Primo/a</option>

    <!-- Afinidad -->
    <option value="SUEGRO/A">Suegro/a</option>
    <option value="YERNO/NUERA">Yerno/Nuera</option>
    <option value="CUNADO/A">Cuñado/a</option>

    <!-- Familia por reconstitución -->
    <option value="PADRASTRO/MADRASTRA">Padrastro/Madrastra</option>
    <option value="HIJASTRO/A">Hijastro/a</option>

    <!-- Otros vínculos -->
    <option value="TUTOR/A">Tutor/a</option>
    <option value="AMIGO/A">Amigo/a</option>
    <option value="VECINO/A">Vecino/a</option>
    <option value="COMPANERO/A DE TRABAJO">Compañero/a de trabajo</option>
    <option value="SIN PARENTESCO">Sin parentesco</option>

    <!-- Libre -->
    <option value="OTRO">Otro</option>
  </select>
</div>

<!-- Campo “Otro” (se muestra solo si eligen OTRO) -->
<div id="bene_extra_parentesco_otro_wrap" class="ui-field" style="display:none; margin-top:8px;">
  <label for="bene_extra_parentesco_otro">Especifica el parentesco</label>
  <input id="bene_extra_parentesco_otro" class="ui-input" type="text" placeholder="Escribe el parentesco…" />
</div>

  <div class="ui-field">
    <label for="bene_extra_cel">Celular</label>
    <input id="bene_extra_cel" class="ui-input" type="text" placeholder="10 dígitos">
  </div>
  <div class="ui-field ui-col-2">
    <label for="bene_extra_mail">Correo</label>
    <input id="bene_extra_mail" class="ui-input" type="email" placeholder="correo@ejemplo.com">
  </div>
</div>


  </div>



  <!-- Acciones -->
<div class="ui-actions">
  <span class="ui-chip">Base: hoy</span>
  <div class="ui-actions__right">
      <button type="button" class="btn btn--ghost" onclick="window.location.href='/index.php'">
      Regresar
    </button>

    <button id="btn_generar" type="button" class="btn btn--ghost">Generar Formato</button>
   <button id="btn_guardar" onclick="guardarContrato()">Guardar Formato</button>

    <button id="btn_imprimir" type="button" class="btn btn--primary">Imprimir</button>
  </div>
</div>

</div>


<div class="page watermark-bottom">
     <!-- Encabezado -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>

    <div class="titulo">
      <h2>CONTRATO DE CRÉDITO <span id="out_modalidad">40 RETROACTIVO </span></h2>
    </div>
  </div>
</div>
    <p>
      El presente contrato de préstamo personal o mutuo es celebrado en: 
      <strong>C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160</strong>, 
      en fecha <strong><span id="out_fecha"></span></strong> (en adelante, el "Contrato").
    </p>

    <center><p><strong>-- ENTRE --</strong></p></center>

<p>

  <strong><span id="out_rep_nombre"></span></strong>, actuando en su propio nombre y derecho (el “<strong>Prestamista</strong>”). con número de RFC: 
  <strong><span id="out_rep_rfc"></span></strong>, y domicilio fiscal en <span id="out_rep_dir"></span>.
</p>

    <center><p><strong>-- Y --</strong></p></center>

    <p>
      <strong><span id="out_prestatario"></span></strong>, con número de RFC: 
      <strong><span id="out_rfc_prestatario"></span></strong>, y domicilio fiscal en <span id="out_dir_prestatario"></span>, 
      actuando en su propio nombre y derecho (el “<strong>Prestatario</strong>”).
    </p>

    <p>
      Estos serán considerados individualmente como la “Parte” y conjuntamente como las “Partes”.
    </p>

    <center><h3>DECLARACIONES</h3></center>
    <p>
      Las Partes expresan su interés en que el <strong>Prestamista</strong> conceda un préstamo personal de dinero al <strong>Prestatario</strong>, 
      conforme a los términos y condiciones establecidos en el presente Contrato. En consecuencia, ambas Partes, 
      contando con la capacidad legal necesaria para contratar y obligarse, y habiendo comprendido plenamente el 
      alcance, contenido y efectos de las declaraciones realizadas, acuerdan suscribir este Contrato de manera libre y 
      voluntaria, el cual se regirá en adelante por las disposiciones establecidas en las siguientes cláusulas.
    </p>

    <center><h3>CLÁUSULAS</h3></center>
    <p><strong>Primera. Objeto del contrato.</strong></p>
    <p>
      En virtud del presente Contrato, el <strong>Prestamista</strong> entrega al <strong>Prestatario</strong>, en concepto de préstamo personal, la 
      cantidad total de <strong> <span class="out_monto_num">0.0</span> <span class="out_monto_letra">Cero pesos 00/100 M.N.</span></strong>, 
      cantidad que el <strong>Prestatario</strong> acepta y reconoce recibir. Tras la firma de este Contrato, el mismo constituirá carta de pago 
      formal, eficaz y vinculante entre las Partes. (En adelante, el “Préstamo”).<br>
      La entrega del Préstamo al <strong>Prestatario</strong> tendrá lugar en: 
      C José Ma. Pino Suarez 119 Pte. Col. Santa Ana Tlapaltitlán, Toluca, México. C.P. 50160, 
      y con fecha del <strong><span id="out_fecha_entrega"></span></strong>, en <strong id="out_metodo_entrega"></strong>.
    </p>

    <p><strong>Segunda. Finalidad del Préstamo.</strong></p>
<p>
  En lo relativo a la finalidad del Préstamo, las Partes acuerdan que el mismo será utilizado para fines comerciales,
  incluyendo de manera enunciativa pero no limitativa, para los siguientes fines:
</p>
<ul>
  <strong><li>Sufragar proyectos para futuros pensionados del IMSS y socios ahorradores.</li></strong>
</ul>
<p>
  El <strong>Prestatario</strong> se compromete a utilizar el Préstamo única y exclusivamente para los fines indicados, sin que
  pueda destinarlo a otro fin diferente salvo que cuente con el consentimiento por escrito del <strong>Prestamista</strong>.
</p>
<ul>
  <!-- Solo para Modalidad 40 -->
  <li class="mod-only" data-mod="40">
    Financiamiento para la inscripción a la continuación voluntaria al régimen obligatorio (Modalidad 40), incluso de manera retroactiva cuando proceda.
  </li>

  <!-- Solo para Modalidad 10 -->
  <li class="mod-only" data-mod="10">
    Financiamiento para trabajadores independientes (Modalidad 10), recuperación de derechos y régimen.
  </li>
</ul>

    <p><strong>Tercera. Comisiones y Cálculo del Monto Financiado.</strong></p>
<p>
  Las Partes acuerdan que, además del monto del préstamo de 
  <strong> <span class="out_monto_num">0.0</span> <span class="out_monto_letra">Cero pesos 00/100 M.N.</span></strong>, 
  se deberán aplicar las siguientes comisiones:
</p>
<ol>
  <li><strong>Comisión por Investigación:</strong> Lo que aplique por área geográfica</li>
  <li><strong>Comisión por Apertura:</strong> 6.5% sobre el total solicitado.</li>
  <li><strong>Seguro de Vida:</strong> 4% sobre el total solicitado (o lo que aplique el seguro vigente).</li>
</ol>
  </div>
   
  
  
  <!-- Segunda hoja -->
<div class="page watermark-bottom">
  <!-- Encabezado (igual que en la hoja 1) -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>
  </div>
</div>

  <!-- Contenido de la segunda hoja -->
<p>
  El monto financiado resultante será la suma de las tres comisiones más el monto solicitado. Este monto será
  utilizado para el cálculo de los intereses ordinarios y moratorios que se devengarán en este contrato.
</p>

<p>
  Sobre el monto financiado (la suma del total solicitado más las comisiones de investigación, apertura y seguro de
  vida), se devengará un interés ordinario del <strong>9.05% + IVA mensual</strong> durante
  <span id="out_meses_interes"></span> meses.
</p>

<p><strong>El cálculo del interés ordinario será el siguiente:</strong></p>
<ul>
  <li>Monto financiado (total solicitado + comisiones).</li>
  <li>Interés ordinario: <strong>9.05% + IVA</strong> sobre el monto financiado por
    <span id="out_meses_financiado"></span> meses.</li>
</ul>

<p>Este interés se calculará y pagará de acuerdo con los plazos establecidos, generando un <strong>Total</strong>.</p>

<p>
  De aquí en adelante serán referenciados como los “<strong>Intereses</strong>” o el “<strong>Interés</strong>” de manera indistinta.
  Salvo que las Partes acuerden expresamente lo contrario, estos Intereses comenzarán a devengarse en el momento en que
  el Préstamo sea entregado al <strong>Prestatario</strong>. Sin perjuicio de lo anterior, las mismas estarán autorizadas para acordar
  periodos de gracia o carencia en donde el capital del Préstamo no generará Intereses. Para lo anterior, será necesario
  el acuerdo previo y por escrito de las Partes. Los Intereses continuarán devengándose hasta que el <strong>Prestatario</strong> haya
  devuelto la totalidad del Préstamo de conformidad con lo acordado en este Contrato. Para el cálculo de estos Intereses,
  las Partes deciden acordar las siguientes reglas:
</p>

<ol>
  <li>El tiempo mínimo del financiamiento será a <span id="out_min_meses"></span> meses.</li>
  <li>No podrá solicitarse por ningún motivo antes de lo acordado.</li>
  <li>El tiempo máximo para liquidar el préstamo será a <span id="out_max_meses"></span> meses.</li>
</ol>

<p>Los Intereses se calcularán sobre el importe total del Préstamo.</p>


<p><strong>Cuarta. Devolución del Préstamo.</strong></p>
<p>
  Este Préstamo deberá ser devuelto en su totalidad al <strong>Prestamista</strong> en fecha
  <strong><span id="out_fecha_devol"></span></strong>. Las Partes, de común acuerdo, estarán habilitadas para
  pactar una prórroga de este Contrato de Préstamo en caso de que, llegado el momento del vencimiento de este, la deuda
  pendiente no se hubiese reintegrado totalmente. Para lo anterior, será necesario reflejar tal prórroga por escrito
  mediante el <strong>Anexo A</strong> al presente Contrato.
</p>


<p><strong>Sexta. Método de Pago.</strong></p>
<p>
  El Préstamo será devuelto en forma de transferencia a nombre de
    <strong><span class="out_asesor_conectado">CIP Financial México, S.A.S. de C.V.</span></strong>. Salvo que las Partes acuerden expresamente lo contrario,
  todo método de pago que requiera de entrega física (cheque, efectivo u otros) tendrá lugar en la vivienda del <strong>Prestamista</strong>.
</p>
<p><strong>Datos bancarios:</strong></p>
<ul id="datos-banco">
<li><strong>Banco:</strong> <span id="out_banco">—</span></li>
<li><strong>Cuenta:</strong> <span id="out_cuenta">—</span></li>
<li><strong>CLABE:</strong> <span id="out_clabe">—</span></li>
</ul>



<p><strong>Séptima. Intereses Moratorios.</strong></p>
<p>
  En caso de que el <strong>Prestatario</strong> no devuelva el Préstamo en los plazos acordados en este Contrato, se cobrará un
  interés moratorio del <strong>3% mensual</strong> sobre el <strong>Subtotal</strong> calculado anteriormente.
  Las Partes acuerdan que estos intereses se calcularán <strong>anualmente</strong> sobre el total adeudado y se
  devengarán desde el día siguiente al vencimiento del plazo de pago o devolución del Préstamo, siendo por tanto exigibles sin necesidad de que el
  <strong>Prestamista</strong> tenga que realizar un requerimiento formal al <strong>Prestatario</strong>, y continuarán devengándose hasta que se
  haya pagado el total de lo adeudado en virtud de este Contrato. En caso de que el Interés moratorio acordado
  sea superior al legalmente permitido, se aplicará entonces éste último.
</p>

</div>


<!-- tercera hoja -->
<div class="page watermark-bottom">
  <!-- Encabezado (igual que en la hoja 1) -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>
  </div>
</div>
<p><strong>Octava. Cuotas de Cobranza.</strong></p>
<p>
  El <strong>Prestatario</strong> se compromete a pagar una cuota de cobranza del <strong>10%</strong> sobre el monto total de la deuda,
  la cual será calculada de acuerdo con el saldo pendiente de pago en cada periodo.
</p>


<p><strong>Novena. Obligaciones y Declaraciones del Prestatario.</strong></p>

<p>
  El <strong>Prestatario</strong> se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la
  normativa aplicable, y en particular declara y garantiza que:
</p>

<ul>
  <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
  <li>No se encuentra en situación de insolvencia económica ni bancarrota y dispone de suficiente poder adquisitivo para poder devolver el Préstamo en las condiciones aquí pactadas.</li>
  <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
  <li>Devolverá el Préstamo de conformidad con los términos y condiciones fijados en este Contrato.</li>
  <li>Asumirá todos los recargos e Intereses bancarios que hayan sido causados, directa o indirectamente, por el incumplimiento del <strong>Prestatario</strong> en la devolución del Préstamo.</li>
  <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
</ul>


<p><strong>Décima. Obligaciones y Declaraciones del Prestamista.</strong></p>

<p>
  El <strong>Prestamista</strong> se compromete a cumplir con todas las obligaciones asumidas en virtud de este Contrato y con la
  normativa aplicable, y en particular declara y garantiza que:
</p>

<ul>
  <li>Dispone de capacidad legal necesaria y suficiente para poder contratar y, en particular, para poder celebrar este Contrato.</li>
  <li>Dispone de capacidad adquisitiva suficiente para realizar el Préstamo en favor del <strong>Prestatario</strong> de conformidad con los términos y condiciones aquí incluidos.</li>
  <li>No existe ningún impedimento de carácter legal, técnico o administrativo que imposibilite suscribir este Contrato.</li>
  <li>Prestará la cantidad de dinero acordada en este Contrato al </strong>Prestatario</strong> en los plazos y condiciones incluidas en el mismo.</li>
  <li>Que todas las declaraciones y manifestaciones aquí expresadas son verídicas y ciertas.</li>
</ul>


<p><strong>Décima Primera. Incumplimiento y Resolución del Contrato.</strong></p>

<p>
  El incumplimiento por parte del <strong>Prestatario</strong> de cualquiera de las obligaciones contraídas en virtud de este Contrato,
  incluyendo de manera enunciativa, pero no limitativa, la devolución del Préstamo y los correspondientes Intereses
  devengados de conformidad con lo estipulado en este Contrato, facultará al <strong>Prestamista</strong> para:
</p>

<ul>
  <li>Resolver el mismo antes del plazo de vencimiento pactado, siempre que previamente el <strong>Prestamista</strong> hubiera requerido por escrito al <strong>Prestatario</strong> para cumplir con sus obligaciones.</li>
  <li>Exigir la devolución inmediata de la totalidad del Préstamo, más los correspondientes Intereses devengados.</li>
  <li>Ejercer cuantas acciones legales el Derecho habilite para exigir el cumplimiento de las obligaciones contractuales y reclamar los daños y perjuicios que dicha resolución anticipada le hubiera podido originar.</li>
</ul>


<p><strong>Décima Segunda. Liquidación de Impuestos.</strong></p>
<p>
  Las Partes acuerdan que cualquier impuesto, tasa, contribución o gravamen derivado del presente Contrato de
  Préstamo será cubierto conforme a la legislación fiscal vigente. En caso de que alguna autoridad determine la
  obligación de pago de impuestos adicionales relacionados con este Contrato, el <strong>Prestatario</strong> será responsable de su
  liquidación, salvo disposición legal en contrario.
</p>

<p><strong>Décima Tercera. Domicilio a Efectos de Notificaciones.</strong></p>

<p>
  Para efectos de notificaciones y comunicación de cualquier tipo relacionadas con este Contrato, el <strong>Prestamista</strong>
  podrá ser contactado en la dirección postal indicada en el encabezado de este Contrato, o a través de las
  siguientes vías:
</p>
<ul>
  <li><strong>Razón social:</strong> <span id="out_prest_nombre_ntf"></span></li>
  <li><strong>E-mail:</strong> <span id="out_prest_email"></span></li>
  <li><strong>No. Tel.:</strong> <span id="out_prest_tel"></span></li>
</ul>

</div>



<!-- tercera hoja -->
<div class="page watermark-bottom">
  <!-- Encabezado (igual que en la hoja 1) -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>
  </div>
</div>


<p>
  El <strong>Prestatario</strong> podrá ser contactado en la dirección postal indicada en el encabezado de este Contrato,
  o a través de las siguientes vías:
</p>
<ul>
  <li><strong>Nombre o razón social:</strong> <span id="out_prestatario_nombre_ntf"></span></li>
  <li><strong>E-mail:</strong> <span id="out_prestatario_email"></span></li>
  <li><strong>No. Tel.:</strong> <span id="out_prestatario_tel"></span></li>
</ul>

<p>
  En ese sentido, si alguna de las Partes desea cambiar esta información de contacto, deberá comunicárselo
  fehacientemente a la otra Parte. Para cualquier información informal relativa al día a día fruto de esta
  relación contractual, las Partes podrán utilizar las vías de teléfono y correo electrónico; no obstante,
  dichas vías no constituirán medio fehaciente.
</p>


<p><strong>Décima Cuarta. Protección de Datos de Carácter Personal.</strong></p>

<p>
Las Partes se comprometen a cumplir con la Ley Federal de Protección de Datos Personales en Posesión de los 
Particulares, garantizando el tratamiento seguro y confidencial de los datos personales. Estos serán utilizados 
exclusivamente para la ejecución del contrato y el cumplimiento de obligaciones legales en materia administrativa, 
contable, tributaria y financiera. Los datos solo serán tratados en la medida necesaria y bajo estrictas medidas de 
seguridad. No serán compartidos con terceros sin autorización, salvo cuando la ley lo exija o dentro de relaciones 
contractuales reguladas con prestadores de servicios. Se conservarán mientras el préstamo esté vigente y hasta 
la posible prescripción de responsabilidades legales. Las Partes podrán ejercer en cualquier momento sus 
derechos de acceso, rectificación, cancelación y oposición, además de presentar reclamaciones ante el Instituto 
Nacional de Transparencia, Acceso a la Información y Protección de Datos Personales si consideran que su 
tratamiento no ha sido adecuado. 
</p>


<p><strong>Décima Quinta. Origen de los Recursos.</strong></p>

<p>
  Que los recursos con los cuales ha de pagar el <strong>Préstamo</strong> dispuesto han sido o serán obtenidos o generados
  a través de una fuente de origen lícito y que el destino que dará a los recursos obtenidos al amparo del presente
  Contrato será únicamente para fines permitidos por la ley, y que no se encuentran dentro de los supuestos
  establecidos en los artículos <strong>139 Quáter</strong> y <strong>400 Bis</strong> del <strong>Código Penal Federal</strong>.
  Se reitera que el presente Contrato se celebra con motivo de la entrega de un recurso económico de origen lícito,
  mismo que el <strong>Prestamista</strong> manifiesta y garantiza no proviene de actividades ilícitas o contrarias a la ley, de
  conformidad con lo dispuesto por la legislación civil vigente y en estricto apego a los principios de legalidad.
  En caso de controversia o cuestionamiento sobre la validez o licitud del presente instrumento, el Préstamo queda
  fundamentado en los artículos <strong>2534</strong> y <strong>2535</strong> del <strong>Código Civil Federal</strong>
  (en cuanto al contrato de mutuo) y, para su ejecución o exigibilidad judicial, en lo establecido por el
  <strong>Código Nacional de Procedimientos Civiles y Familiares</strong>, particularmente en lo relativo a los
  procedimientos de ejecución de obligaciones contractuales.
  Las Partes se comprometen a dirimir cualquier controversia conforme a derecho, garantizando la transparencia, buena fe y la legalidad del presente acto jurídico.
</p>


<p><strong>Décima Sexta. Ley Aplicable y Tribunal Competente.</strong></p>

<p>
  Cualquier conflicto que pueda surgir en relación con este Contrato será interpretado de conformidad con la ley
  federal aplicable a los Estados Unidos Mexicanos y al Estado de México. En caso de contradicción entre ambas,
  primará lo dispuesto en la ley estatal, salvo que se trate de materias expresamente reservadas a regulación federal.

  Las Partes se comprometen a buscar siempre de buena fe una solución amistosa ante cualquier disputa o conflicto que
  pudiera surgir relacionado con este Contrato, tratando de evitar en todo caso el acudir a la vía judicial o al arbitraje.
</p>

<p>
  Si las Partes no fueran capaces de resolver amistosamente estas disputas dentro de los 14 días siguientes al surgimiento
  de la disputa, las mismas acuerdan someterse de manera expresa e irrevocable a la jurisdicción y competencia de los
  juzgados y tribunales del domicilio del <strong>Prestatario</strong>, con renuncia expresa a cualquier otra jurisdicción que le pudiera
  corresponder, salvo que esta fuera imperativa en aplicación de lo dispuesto en la ley aplicable.
</p>

<p><strong>Décima Séptima. Modificaciones.</strong></p>

<p>
  Cualquier modificación, adición o enmienda al presente Contrato de Préstamo deberá realizarse mediante
  acuerdo por escrito, firmado por todas las Partes involucradas. Dicho acuerdo deberá especificar con claridad
  los cambios realizados y entrará en vigor a partir de la fecha de firma de todas las Partes, salvo que se
  establezca un plazo distinto en el mismo documento.
</p>

</div>


<!-- tercera hoja -->
<div class="page watermark-bottom">
  <!-- Encabezado (igual que en la hoja 1) -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>
  </div>
</div>

<p>

  Ninguna modificación verbal ni comunicación realizada por medios distintos al acuerdo escrito producirá efecto
  jurídico alguno.
</p>



<p><strong>Décima Octava. Separabilidad.</strong></p>

<p>
  En caso de existir contradicción entre cualquier cláusula de este Contrato y la ley aplicable, esta última
  prevalecerá, y las cláusulas que la contradigan se entenderán por no puestas. Adicionalmente, toda disposición
  normativa que por ley deba formar parte de este Contrato se entenderá añadida al Contrato, formando parte de su contenido.
  La ilicitud, invalidez o ineficacia de alguna de las cláusulas de este Contrato no afectará al resto de cláusulas,
  las cuales seguirán vigentes y serán plenamente eficaces. Con respecto a las cláusulas afectadas, se entenderán por no
  puestas y las Partes se comprometen a negociar de buena fe un nuevo texto para esas cláusulas, buscando siempre la misma
  finalidad que perseguía la cláusula original.
  Este Contrato de Préstamo constituye el acuerdo completo entre las Partes, y no existe ningún otro documento, término o
  condición relacionado con el mismo, ni verbal ni de ningún otro tipo.
</p>


<p><strong>Décima Novena. Contratación de Seguro en Caso de Fallecimiento.</strong></p>

<p>
  El <strong>Prestatario</strong> se obliga, al momento de la firma del presente Contrato, a contratar un seguro de vida con una
  suma asegurada equivalente, como mínimo, al monto total del Préstamo, es decir,
<strong id="montoContrato">Cargando monto...</strong>,
  con el objeto de garantizar la cobertura de la deuda en caso de fallecimiento del <strong>Prestatario</strong> durante la vigencia del presente Contrato. Dicho seguro deberá:
</p>

<ul>
  <li>Ser contratado a nombre del <strong>Prestatario</strong> en una institución aseguradora legalmente autorizada para operar en México.</li>
  <li>Establecer expresamente como beneficiario preferente a <strong><span class="out_asesor_conectado">CIP Financial México, S.A.S. de C.V.</span></strong> en su carácter de <strong>Prestamista</strong>.</li>
  <li>Mantenerse vigente por todo el plazo de duración del Préstamo.</li>
  <li>Cubrir, en caso de fallecimiento del <strong>Prestatario</strong>, la totalidad del saldo insoluto del Préstamo, incluyendo capital e intereses ordinarios y/o moratorios devengados hasta ese momento.</li>
  <li>Entregar una copia de la póliza al <strong>Prestamista</strong> junto con el presente Contrato como condición indispensable para su validez.</li>
</ul>

<p>
  En caso de que el <strong>Prestatario</strong> no contrate o no mantenga vigente el seguro descrito, se considerará unincumplimiento grave del presente Contrato, facultando al <strong>Prestamista</strong> para declarar el vencimiento anticipado de
la deuda, y exigir el reembolso inmediato de la totalidad del Préstamo más los intereses devengados, así como 
para ejercer todas las acciones legales correspondientes.
 
</p>

<h3>CESIÓN DEL CONTRATO</h3>

<p>
Las Partes no podrán ceder este Contrato salvo cuando cuenten con el consentimiento previo, expreso y por 
escrito de la otra Parte.<strong>Y EN PRUEBA DE CONFORMIDAD Y ACEPTACIÓN</strong>, las Partes firman este Contrato en fecha
  <strong><span id="out_fecha_actual"></span></strong>.
</p>


<div class="firmas firmas-2col">
  <!-- Prestamista -->
  <div class="firma">
    <p><strong>FIRMA DEL PRESTAMISTA</strong></p>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>

    <p>En su condición de <strong>Prestamista</strong> y en su propio<br>nombre y derecho.</p>
    <p><strong id="out_nombre_simple"></strong></p>
  </div>

  <!-- Prestatario -->
  <div class="firma">
    <p><strong>FIRMA DEL PRESTATARIO</strong></p>

    <div class="huellas-mini">
      <div class="huella-mini-box">
        <span class="huella-mini-label">PULGAR<br>IZQ.</span>
      </div>

      <div class="huella-mini-box">
        <span class="huella-mini-label">PULGAR<br>DER.</span>
      </div>
    </div>
    <br>
    <p>En su condición de <strong>Prestatario</strong> y en su propio<br>nombre y derecho.</p>
    <p><strong id="firma_prestatario_nombre"></strong></p>
  </div>
</div>
</div>



</div>


<!-- tercera hoja -->
<div class="page watermark-bottom" data-watermark="SIN TEXTO">
  <!-- Encabezado (igual que en la hoja 1) -->
<div class="encabezado">
  <div class="contrato-box">
    <p class="contrato-num">Contrato no. <span id="out_folio"></span></p>
  </div>

  <div class="centro-head">
    <div class="logo-centro">
      <img src="/img/logo.png" alt="Logo CIP">
    </div>
    <div class="titulo">
    </div>
  </div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/js/contrato/contrato.js"></script>
<script src="/js/contrato/contrato_bene.js"></script>


<script>
// ===============================
// FUNCIÓN: NÚMERO A LETRAS (MX)
// ===============================
function numeroALetrasMX(num) {
  const unidades = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];
  const especiales = ['diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
  const decenas = ['', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
  const centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

  function convertirMenor100(n) {
    if (n < 10) return unidades[n];
    if (n >= 10 && n < 20) return especiales[n - 10];
    if (n >= 20 && n < 30) {
      if (n === 20) return 'veinte';
      return 'veinti' + unidades[n % 10];
    }

    const d = Math.floor(n / 10);
    const u = n % 10;
    return decenas[d] + (u ? ' y ' + unidades[u] : '');
  }

  function convertirMenor1000(n) {
    if (n === 0) return '';
    if (n === 100) return 'cien';
    if (n < 100) return convertirMenor100(n);

    const c = Math.floor(n / 100);
    const resto = n % 100;

    return centenas[c] + (resto ? ' ' + convertirMenor100(resto) : '');
  }

  function convertirNumero(n) {
    if (n === 0) return 'cero';

    if (n < 1000) {
      return convertirMenor1000(n);
    }

    if (n < 1000000) {
      const miles = Math.floor(n / 1000);
      const resto = n % 1000;

      let textoMiles = '';
      if (miles === 1) {
        textoMiles = 'mil';
      } else {
        textoMiles = convertirMenor1000(miles) + ' mil';
      }

      return textoMiles + (resto ? ' ' + convertirMenor1000(resto) : '');
    }

    const millones = Math.floor(n / 1000000);
    const resto = n % 1000000;

    let textoMillones = '';
    if (millones === 1) {
      textoMillones = 'un millón';
    } else {
      textoMillones = convertirNumero(millones) + ' millones';
    }

    return textoMillones + (resto ? ' ' + convertirNumero(resto) : '');
  }

  const numero = Number(num) || 0;
  const entero = Math.floor(numero);
  let centavos = Math.round((numero - entero) * 100);

  // Ajuste por si redondea a 100
  let enteroFinal = entero;
  if (centavos === 100) {
    enteroFinal += 1;
    centavos = 0;
  }

  let letras = convertirNumero(enteroFinal).trim();
  letras = letras.charAt(0).toUpperCase() + letras.slice(1);

  return `${letras} pesos ${centavos.toString().padStart(2, '0')}/100 M.N.`;
}

// ===============================
// SCRIPT PRINCIPAL
// ===============================
document.addEventListener('DOMContentLoaded', async () => {
  const el = document.getElementById('montoContrato');
  if (!el) return;

  const params = new URLSearchParams(window.location.search);
  const solicitudId = params.get('solicitud_id');

  if (!solicitudId) {
    el.textContent = '—';
    return;
  }

  const MONTO_MINIMO = 250000;

  // ✅ LOCAL / PRODUCCIÓN
  const base = (location.hostname === 'localhost') ? '/hp/public' : '';
  const endpoint = `${location.origin}${base}/app/controllers/contratos/get_monto_caratula.php?solicitud_id=${encodeURIComponent(solicitudId)}`;

  const formatoMoneda = (n) =>
    '$ ' + Number(n).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

  try {
    const res = await fetch(endpoint, { cache: 'no-store' });

    if (!res.ok) {
      el.textContent = '—';
      return;
    }

    const data = await res.json();

    if (!data || data.ok !== true) {
      el.textContent = '—';
      return;
    }

    const montoBD = Number(data.monto_total_pagar || 0);
    const montoFinal = (montoBD < MONTO_MINIMO) ? MONTO_MINIMO : montoBD;

    const letras = numeroALetrasMX(montoFinal);

    el.innerHTML = `<strong>${formatoMoneda(montoFinal)} (${letras})</strong>`;
  } catch (err) {
    el.textContent = '—';
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const pintarAsesorConectado = () => {
    const nombreAsesor =
      document.getElementById('out_rep_nombre')?.textContent?.trim() ||
      document.getElementById('out_nombre_simple')?.textContent?.trim() ||
      '';

    if (!nombreAsesor) return false;

    document.querySelectorAll('.out_asesor_conectado').forEach(el => {
      el.textContent = nombreAsesor;
    });

    return true;
  };

  if (pintarAsesorConectado()) return;

  let intentos = 0;
  const timer = setInterval(() => {
    intentos++;

    if (pintarAsesorConectado() || intentos >= 20) {
      clearInterval(timer);
    }
  }, 300);
});
</script>
</body>
</html>
