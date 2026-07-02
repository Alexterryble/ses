<?php
// /public/index.php
require_once __DIR__ . '/app/controllers/auth/require_login.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Solicitud de Crédito CIP</title>
  <link rel="stylesheet" href="css/formulario.css">
  <!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>


  
 <div id="formatoFinal">
<div class="logo-container">
  <img src="img/logo.png" alt="Logo CIP">
  <div><strong>CONSULTORÍA INTEGRAL DE PENSIONES</strong></div>
</div>
<div class="folio-en-esquina">
  <b>Folio:</b> <span id="folioTexto">—</span>
</div>
<table class="credit-application-table">
    <tr>
    <td colspan="6" style="background-color: #2163b2; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 6px 12px; border-left: 1px solid #2a6ebb;">
<center>SOLICITUD DE CRÉDITO</center>
</td>
    </tr>
    <tr>
        <td colspan="2" style="padding: 6px;">
            <strong>Instrucciones:</strong>1) El trámite de esta solicitud es completamente gratuito. 2) Lea con atención los datos solicitados, escriba claro y con letra de molde. 3)  <strong> Es muy importante que conteste por completo toda la información solicitada.</strong>
        </td>
    </tr>

    <tr>
        <td style="width: 50%;">Nombre de quien lo atendió:<strong id="res-atendio"></strong></td>
        <td style="width: 50%;">¿Cómo se enteró?: <strong id="res-medio"></strong></td>
    </tr>
</table>

<table class="loan-details-table no-top-border">
    <tr>
        <td colspan="3" style="width: 34%;">Monto del préstamo solicitado: $ <strong id="res-monto"></strong> M.N.</td>
        <td colspan="3" style="width: 33%;">Plazo requerido: <strong id="res-plazo"></strong> meses</td>
        <td colspan="3" style="width: 33%;">Frecuencia del pago: <strong id="res-frecuencia"></strong></td>
    </tr>
</table>

  <div style="display: flex; justify-content: space-between; gap: 1px; align-items: flex-start; flex-wrap: wrap;margin-top: 1px;">
    <!-- IZQUIERDA -->
  <div class="half">
  <table class="tabla-interna">
    <tr><th colspan="4" style="background-color: #1B68B2; color: white; text-align: left; padding: 6px;"> <CENTER>DATOS DEL SOLICITANTE</CENTER></th></tr>
    <tr><td colspan="4">Nombre(s) sin abreviaturas:<strong id="res-nombres"></strong></td></tr>
    <tr>
      <td colspan="2">Apellido Paterno: <strong id="res-apellido-paterno"></strong></td>
      <td colspan="2"> Apellido Materno: <strong id="res-apellido-materno"></strong>
    </tr>
    <tr>
      <td colspan="2">Fecha de nacimiento (AAAA/MM/DD):<strong id="res-fecha-nacimiento"></strong></td>
      <td colspan="2">Género:<br><strong id="res-genero"></strong></td>
    </tr>
    <tr>
      <td colspan="2">Lugar de nacimiento:<br><strong id="res-estado-nacimiento"></strong><br></td>
      <td colspan="2">Dependientes Económicos:<br><strong id="res-dependientes"></strong></td>
    </tr>
    <tr>
      <td colspan="2">Nacionalidad:<br>&nbsp;<strong id="res-nacionalidad"></strong></td>
      <td>País de nacimiento:<br><strong id="res-pais-nacimiento"></strong></td>
      <td>¿FIEL / SAT?<br><strong id="res-fiel"></strong></td>
    </tr>
    <tr>
      <td colspan="2">RFC con Homoclave:<br>&nbsp;<strong id="res-rfc"></strong></td>
      <td colspan="2">CURP:<br><strong id="res-curp"></strong></td>
    </tr>
    <tr>
      <td colspan="2">
        Estado civil:<br><strong id="res-estado-civil"></strong><br>
        Tiempo: <strong id="res-tiempo-estado-civil"></strong>
      </td>
      <td colspan="2">
        Nivel máximo de estudios:<br><strong id="res-escolaridad"></strong><br>
        Profesión: <strong id="res-profesion"></strong>
      </td>
    </tr>
    <tr><td colspan="4">Dirección actual (calle y número) exterior e interior:<strong id="res-direccion"></strong></td></tr>
    <tr><td colspan="4">Entre qué calles se encuentra:<strong id="res-entre-calles"></strong></td></tr>
    <tr>
      <td colspan="2">Colonia:<strong id="res-colonia"></strong></td>
      <td colspan="2">Código Postal:<strong id="res-cp"></strong></td>
    </tr>
    <tr>
      <td>Municipio / Delegación:<br>&nbsp;<strong id="res-municipio"></strong></td>
      <td>Estado:<br>&nbsp;<strong id="res-estado"></strong></td>
      <td>País:<br>&nbsp;<strong id="res-pais"></strong></td>
      <td>Tiempo en este domicilio:<br>&nbsp;<strong id="res-tiempo-domicilio"></strong></td>
    </tr>
    <tr>
      <td colspan="2">Teléfono(s):<strong id="res-telefono"></strong></td>
      <td colspan="2">Tel. Celular:<strong id="res-celular"></strong></td>
    </tr>
    <tr>
      <td colspan="3" style="width: 60%;">Correo electrónico:<br>&nbsp;<strong id="res-correo"></strong></td>
      <td style="width: 40%;">Horario de contacto: <br>&nbsp;<strong id="res-mejor-hora"></strong></td>
    </tr>
  </table>
  </div>

  <!-- DERECHA -->
  <div class="half">
 <table class="tabla-interna"> <!-- puedes subir o bajar este número -->
    <tr><th colspan="4" style="background-color: #1B68B2; color: white; text-align: left; padding: 6px;"><CENTER>EN CASO DE SER EMPLEADO (ASALARIADO)</CENTER></th></tr>
    <tr><td colspan="4">Puesto / Posición en el empleo:<br>&nbsp;<strong id="res-puesto"></strong></td></tr>
    <tr>
      <td colspan="2">Nombre de la empresa / Negocio / patrón:<br><strong id="res-empresa"></strong></td>
      <td colspan="2">Actividad / Giro de la Empresa:<br>&nbsp;<strong id="res-giro-empresa"></strong></td>
    </tr>
    <tr><td colspan="4">Dirección Actual (calle y número):<br>&nbsp;<strong id="res-direccion-trabajo"></strong></td></tr>
    <tr><td colspan="4">Entre qué calles se encuentra:<br>&nbsp;<strong id="res-calles-trabajo"></strong></td></tr>
    <tr><td colspan="4">Referencia:<br><br>&nbsp;<strong id="ref_empresa_trabajo"></strong></td></tr>
    <tr><td colspan="4">Colonia:<br>&nbsp;<strong id="res-colonia-trabajo"></strong></td></tr>
    <tr>
      <td>Municipio:<br>&nbsp;<strong id="res-municipio-trabajo"></strong></td>
      <td>Estado:<br>&nbsp;<strong id="res-estado-trabajo"></strong></td>
      <td>País:<br>&nbsp;<strong id="res-pais-trabajo"></strong></td>
      <td>Tiempo en este empleo:<br>&nbsp;<strong id="res-tiempo-empleo"></strong></td>
    </tr>
    <tr>
      <td colspan="2">Teléfono:<br>&nbsp;<strong id="res-telefono-trabajo"></strong></td>
      <td colspan="2">Horario de trabajo:<br>&nbsp;<strong id="res-horario-trabajo"></strong></td>
    </tr>
    <tr>
      <td colspan="2">Sueldo Mensual Fijo:<br>&nbsp;<strong id="res-sueldo"></strong></td>
      <td colspan="2">
        Forma de pago:<br>&nbsp;<strong id="res-forma-pago"></strong>
      </td>
    </tr>
    <tr>
      <td colspan="2">Otros Ingresos Variables:<br>&nbsp;<strong id="res-otros-ingresos"></strong></td>
      <td colspan="2">Fuente de estos Ingresos:<br>&nbsp;<strong id="res-fuente-ingresos"></strong></td>
    </tr>
    <tr>
      <td colspan="4">
        El negocio se encuentra en:<br>&nbsp;<strong id="res-ubicacion-negocio"></strong>
      </td>
    </tr>
  </table>
</div>



<table border="1" style="border-collapse: collapse; width: 100%; font-size: 8px; margin-top: 1px; margin-bottom: 0; border-color: #2a6ebb;">
  <tr>
    <td style="padding: 4px; text-align: justify;">
      ¿Usted desempeña o ha desempeñado funciones públicas destacadas en un país extranjero o en territorio nacional, como son, entre otros, jefes de estado o de gobierno, líderes políticos, funcionarios gubernamentales, judiciales o militares de alta jerarquía, altos ejecutivos de empresas estatales, funcionarios o miembros importantes de partidos políticos?
    </td>
    <td style="width: 60px; text-align: center; font-weight: bold;">
      <span id="res-funcion-publica"></span>
    </td>
  </tr>
  <tr>
    <td style="padding: 4px; text-align: justify;">
      ¿Usted es cónyuge o concubina (rio) o tiene parentesco por consanguinidad o afinidad hasta el segundo grado con personas que caen en el supuesto de la pregunta anterior?
    </td>
    <td style="width: 60px; text-align: center; font-weight: bold;">
      <span id="res-relacion-publica"></span>
    </td>
  </tr>
</table>


<table>
  <tr>
    <td colspan="6" style="background-color: #2163b2; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 6px 12px; border-left: 1px solid #2a6ebb;">
  <CENTER>DATOS DEL SOLICITANTE</CENTER>
</td>

  </tr>
          <td>Tipo de vivienda:<span id="res-tipo-vivienda" style="margin-left: 15px; font-weight: bold;"></span></td>
          <td>Pago Casa: <br><span id="res-pago-casa" style="margin-left: 15px; font-weight: bold;"></span></td>
          <td>Pago servicios: <br><span id="res-pago-servicios" style="font-weight: bold;"></span></td>
          <td>Pago otros: <br><span id="res-pago-otros" style="font-weight: bold;"></span></td>
          <td colspan="2">Gasto mensual predial, agua, etc.: <br><span id="res-gasto-mensual" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td style="vertical-align: middle; height: 6px;">Si la vivienda es propia o está hipotecada</td>

    <td>Valor de la casa <br>$ <span id="res-valor-casa" style="margin-left: 15px; font-weight: bold;"></span></td>
    <td colspan="2">Saldo hipoteca: <span id="res-saldo-hipoteca" style="font-weight: bold;"></span></td>
    <td colspan="2">Empresa financia hipoteca: <span id="res-empresa-hipoteca" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td style="vertical-align: middle; height: 6px;">Si la vivienda se Renta es de familiares o huésped</td>
    <td>Nombre propietario: <br><span id="res-nombre-propietario" style="margin-left: 15px; font-weight: bold;"></span></td>
    <td>Parentesco: <span id="res-parentesco" style="margin-left: 15px; font-weight: bold;"></span></td>
    <td colspan="3">Teléfono: <span id="res-telefono-propietario" style="margin-left: 15px; font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td>
      ¿Posee automóvil? <br> 
    <span id="res-posee-auto" style="margin-left: 15px; font-weight: bold;"></span>
    </td>
    <td>Marca, Modelo, Año: <span id="res-auto-detalle" style="font-weight: bold;"></span></td>
    <td>Valor factura: <span id="res-auto-valor" style="font-weight: bold;"></span></td>
    <td colspan="2">Empresa financia crédito: <span id="res-auto-empresa" style="font-weight: bold;"></span></td>
    <td>Mensualidad: <span id="res-auto-mensualidad" style="font-weight: bold;"></span></td>
  </tr>
</table>

<br>

<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
  <!-- TEXTO -->
  <div style="width: 75%; line-height: 1.5;">
    <p style="margin: 0; font-size: 8px; text-align: justify;">
      <i>DECLARO QUE PARA EFECTOS DEL CRÉDITO QUE VOY A CONTRATAR ACTÚO A NOMBRE Y POR CUENTA PROPIA, PROPORCIONARÉ COPIA SIMPLE DE IDENTIFICACIÓN OFICIAL, CURP, RFC, FIEL (SI CUENTO CON ELLA), Y COMPROBANTE DE DOMICILIO COMO PARTE INTEGRAL DE MI EXPEDIENTE.</i>
      Hago constar que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en 
      <a href="https://www.cipmexico.com.mx/" target="_blank">www.cipmexico                                    .com.mx/</a>
    </p>
  </div>

<!-- CUADRO DE FIRMA -->
<td style="text-align: center; vertical-align: top;">
  <div style="display: flex; flex-direction: column; align-items: center;">
    <canvas id="firmaCanvasVista1" width="210" height="70" style="border: 1px solid #ccc;"></canvas>
    <div style="font-size: 9px; margin-top: 6px; text-align: center; width: 210px;">
      <b>Firma del SOLICITANTE</b>
    </div>
  </div>
</td>

</div>

<!-- AUTORIZACIÓN PARA INVESTIGACIÓN DE CRÉDITO -->
<table >
<tr>
<td colspan="6" style="background-color: #2a6ebb; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 6px 12px; border-left: 1px solid #2a6ebb;">
  <CENTER>AUTORIZACIÓN PARA INVESTIGACIÓN DE CRÉDITO</CENTER>
</td>
</tr>
<tr>
  <td style="border: 1px solid #2a6ebb; padding: 2px; width: 50%;">
    Fecha de consulta: <span id="fecha-consulta" style="font-weight: bold;"></span>
  </td>
  <td style="border: 1px solid #2a6ebb; padding: 2px; width: 50%;">
    Folio de consulta: <span id="res-folio-consulta" style="font-weight: bold;"></span>
  </td>
</tr>

  <tr>
    <td colspan="2" style="padding: 1px; text-align: justify;">
      Por este conducto autorizo expresamente a <b><u>CONSULTORÍA INTEGRAL DE PENSIONES                                    </u></b>, (en adelante) para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
      Declaro que conozco la naturaleza y alcance de <b>(i)</b> las Sociedades de Información Crediticia; <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;
      <b>(iii)</b> la información que se solicitará a las Sociedades de Información Crediticia y, <b>(iv)</b> el uso que hará de tal información.
      Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
      Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CONSULTORÍA INTEGRAL DE PENSIONES                                    </u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.
    </td>
  </tr>
  <tr>
<td style="padding: 8px; vertical-align: top;">
  <b>LUGAR Y FECHA:</b><br>
  En que firma la autorización de consulta
  <span id="res-lugar">N/A</span>, <span id="res-fecha">N/A</span>
</td>

<td style="text-align: center; vertical-align: top;">
  <div>
    <canvas id="firmaCanvasVista2" width="210" height="70" style="border: 1px solid #ccc;"></canvas>
    <div style="font-size: 9px; margin-bottom: 2px;"><b>Firma de autorización</b></div>
  </div>
</td>
  </tr>
</table>
</div>


<div class="page-break"></div>

<div class="formato">
  <!-- TÍTULO + LOGO -->
<div class="logo-container">
  <img src="img/logo.png" alt="Logo CIP">
  <div><strong>CONSULTORÍA INTEGRAL DE PENSIONES</strong></div>
</div>

  <table style="width: 100%; border-collapse: collapse; font-family: Arial; font-size: 9px; table-layout: fixed;" border="1">
  <!-- Encabezado azul - Esta fila abarca las 6 columnas lógicas -->
  <tr>
    <td colspan="6" style="background-color: #2163b2; color: white; font-weight: bold; text-align: left; padding: 5px;">
      1.- REFERENCIA DEL SOLICITANTE <span style="font-weight: normal;"></span>
    </td>
  </tr>

  <!-- Fila de Nombres y Dirección: 50% y 50% (cada una abarca 3 de 6 columnas lógicas) -->
  <tr>
    <th style="width: 50%; text-align: left;" colspan="3">Nombre Completo:</th>
    <th style="width: 50%; text-align: left;" colspan="3">Dirección:</th>
  </tr>
  <tr>
    <td colspan="3"><span id="ref_fam_nombre" style="font-weight: bold;"></span><br></td>
    <td colspan="3"><span id="ref_fam_direccion" style="font-weight: bold;"></span><br></td>
  </tr>

  <!-- Fila inferior con Teléfono, Celular, Parentesco: Cada una ocupa 2 de 6 columnas lógicas (aproximadamente 33.33%) -->
  <tr>
    <th style="width: 33.33%; text-align: left;" colspan="2">Teléfono Fijo:</th>
    <th style="width: 33.33%; text-align: left;" colspan="2">Celular:</th>
    <th style="width: 33.34%; text-align: left;" colspan="2">Parentesco:</th>
  </tr>
  <tr>
    <td colspan="2"><span id="ref_fam_telefono" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_fam_celular" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_fam_parentesco" style="font-weight: bold;"></span><br></td>
  </tr>

  <!-- SEGUNDA REFERENCIA FAMILIAR -->
  <tr>
    <td colspan="6" style="background-color: #2a6ebb; color: white; font-weight: bold; font-size: 9px; text-align: left;">
      2.- REFERENCIA DEL SOLICITANTE <span style="font-weight: normal;"></span>
    </td>
  </tr>

  <!-- Fila de Nombres y Dirección (segunda referencia): 50% y 50% (cada una abarca 3 de 6 columnas lógicas) -->
  <tr>
    <th style="width: 50%; text-align: left;" colspan="3">Nombre Completo:</th>
    <th style="width: 50%; text-align: left;" colspan="3">Dirección:</th>
  </tr>
  <tr>
    <td colspan="3"><span id="ref_fam_nombre_2" style="font-weight: bold;"></span><br></td>
    <td colspan="3"><span id="ref_fam_direccion_2" style="font-weight: bold;"></span><br></td>
  </tr>

  <!-- Fila inferior con Teléfono, Celular, Parentesco (segunda referencia): Cada una ocupa 2 de 6 columnas lógicas -->
  <tr>
    <th style="width: 33.33%; text-align: left;" colspan="2">Teléfono Fijo:</th>
    <th style="width: 33.33%; text-align: left;" colspan="2">Celular:</th>
    <th style="width: 33.34%; text-align: left;" colspan="2">Parentesco:</th>
  </tr>
  <tr>
    <td colspan="2"><span id="ref_fam_telefono_2" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_fam_celular_2" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_fam_parentesco_2" style="font-weight: bold;"></span><br></td>
  </tr>
</table>

  <!-- TABLA DE REFERENCIA PERSONAL -->
<!-- TABLA DE REFERENCIA PERSONAL -->
<table style="width: 100%; border-collapse: collapse; font-family: Arial; font-size: 9px; table-layout: fixed;" border="1">
  <!-- TÍTULO: Abarca las 6 columnas lógicas -->
  <tr>
    <td colspan="6" style="background-color: #2163b2; color: white; font-weight: bold; text-align: left; padding: 6px; border-left: 1px solid #2a6ebb;">
      3.- REFERENCIA PERSONAL DEL SOLICITANTE <span style="font-weight: normal;"></span>
    </td>
  </tr>

  <!-- Nombre y dirección: Cada uno abarca 3 de 6 columnas (50% y 50%) -->
  <tr>
    <th style="width: 50%; text-align: left;" colspan="3">Nombre Completo:</th>
    <th style="width: 50%; text-align: left;" colspan="3">Dirección:</th>
  </tr>
  <tr>
    <td colspan="3"><span id="ref_per_nombre" style="font-weight: bold;"></span><br></td>
    <td colspan="3"><span id="ref_per_direccion" style="font-weight: bold;"></span><br></td>
  </tr>

  <!-- Teléfono, Celular y Parentesco: Cada uno abarca 2 de 6 columnas (aprox. 33.33%) -->
  <tr>
    <th style="width: 33.33%; text-align: left;" colspan="2">Teléfono Fijo:</th>
    <th style="width: 33.33%; text-align: left;" colspan="2">Celular:</th>
    <th style="width: 33.34%; text-align: left;" colspan="2">Parentesco:</th>
  </tr>
  <tr>
    <td colspan="2"><span id="ref_per_telefono" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_per_celular" style="font-weight: bold;"></span><br></td>
    <td colspan="2"><span id="ref_per_parentesco" style="font-weight: bold;"></span><br></td>
  </tr>

  <!-- SEGUNDA REFERENCIA PERSONAL: Título abarca 6 columnas -->
</table>

</div>
<br>
<table style="margin-top: 1px;">
<tr>
<td colspan="6" style="background-color: #2a6ebb; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 2px 2px; border-left: 1px solid #2a6ebb; margin-bottom: 5px;">
   <CENTER> DATOS DEL AVAL</CENTER>
  </td>
</tr>
    <tr>
      <td colspan="2">Parentesco: <span id="co_deudor_parentesco" style="font-weight: bold;"></span></td>
      <td colspan="4">Nombre: <span id="co_nombre" style="font-weight: bold;"></span></td>
    </tr>
  <tr>
    <td colspan="3">Apellido Paterno: <span id="co_apellido_paterno" style="font-weight: bold;"></span></td>
    <td colspan="3">Apellido Materno: <span id="co_apellido_materno" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    
    <td colspan="4">Dependientes Económicos: <span id="co_dependientes" style="font-weight: bold;"></span></td>
    <td colspan="2">Género: <span id="co_genero" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="2">Fecha de nacimiento: <span id="co_nacimiento" style="font-weight: bold;"></span></td>
    <td colspan="2">Entidad federativa: <span id="co_entidad" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="3">Nacionalidad: <span id="co_nacionalidad" style="font-weight: bold;"></span></td>
    <td colspan="3">País de nacimiento: <span id="co_pais_nacimiento" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="3">RFC con Homoclave: <span id="co_rfc" style="font-weight: bold;"></span></td>
    <td colspan="3">CURP: <span id="co_curp" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="6">Dirección actual: <span id="co_direccion" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="6">Entre qué calles: <span id="co_entre_calles" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="4">Colonia: <span id="co_colonia" style="font-weight: bold;"></span></td>
    <td colspan="2">Código Postal: <span id="co_cp" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="2">Municipio: <span id="co_municipio" style="font-weight: bold;"></span></td>
    <td colspan="2">Estado: <span id="co_estado" style="font-weight: bold;"></span></td>
    <td>País: <span id="co_pais" style="font-weight: bold;"></span></td>
    <td>Tiempo en este domicilio: <span id="co_tiempo" style="font-weight: bold;"></span></td>
  </tr>
  <tr>
    <td colspan="3">Teléfono Fijo: <span id="co_tel" style="font-weight: bold;"></span></td>
    <td colspan="3">Tel. Celular: <span id="co_cel" style="font-weight: bold;"></span>
    </td>
  </tr>
  <tr>
         <td colspan="2">Horario de contacto: <span id="co_mejor_hora" style="font-weight: bold;"></span></td>
         <td colspan="4">Correo electrónico: <span id="co_correo" style="font-weight: bold;"></span></td>  
  </tr>
</table>


<table border="1" style="border-collapse: collapse; width: 100%; font-size: 8px; margin-top: 0px; border-color: #2a6ebb;margin-top: 1px;">
  <tr>
    <td style="padding: 4px; text-align: justify;">
      ¿Usted desempeña o ha desempeñado funciones públicas destacadas en un país extranjero o en territorio nacional, como son, entre otros, jefes de estado o de gobierno, líderes políticos, funcionarios gubernamentales, judiciales o militares de alta jerarquía, altos ejecutivos de empresas estatales, funcionarios o miembros importantes de partidos políticos?
    </td>
    <td style="width: 60px; text-align: center; font-weight: bold;">
      <span id="res-funcion-publica-2"></span>
    </td>
  </tr>
  <tr>
    <td style="padding: 4px; text-align: justify;">
      ¿Usted es cónyuge o concubina (rio) o tiene parentesco por consanguinidad o afinidad hasta el segundo grado con personas que caen en el supuesto de la pregunta anterior?
    </td>
    <td style="width: 60px; text-align: center; font-weight: bold;">
      <span id="res-relacion-publica-2"></span>
    </td>
  </tr>
</table>



<div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-top: 10px;">
  <!-- TEXTO -->
  <div style="width: 75%; line-height: 1.5;">
    <p style="margin: 0; font-size: 8px; text-align: justify;">
      <i>DECLARO QUE PARA EFECTOS DEL CRÉDITO QUE VOY A CONTRATAR ACTÚO A NOMBRE Y POR CUENTA PROPIA, PROPORCIONARÉ COPIA SIMPLE DE IDENTIFICACIÓN OFICIAL, CURP, RFC, FIEL (SI CUENTO CON ELLA), Y COMPROBANTE DE DOMICILIO COMO PARTE INTEGRAL DE MI EXPEDIENTE.</i>
      Hago constar que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES                                    </span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES                                    </span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en 
      <a href="https://www.cipmexico.com.mx/" target="_blank">www.cipmexico.com.mx/</a>
    </p>
  </div>

  <!-- FIRMA DEL SOLICITANTE -->
  <div style="display: flex; flex-direction: column; align-items: center;">
    <canvas id="firmaVistaSolicitante" width="210" height="70" style="border: 1px solid #ccc;"></canvas>
    <div style="font-size: 9px; margin-top: 8px; text-align: center; width: 210px;">
      <b>Firma del AVAL</b>
    </div>
  </div>
</div>



<table style="margin-top: 1px;">
<tr>
<td colspan="6" style="background-color: #2a6ebb; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 6px 12px; border-left: 1px solid #2a6ebb;">
  <CENTER>AUTORIZACIÓN PARA INVESTIGACIÓN DE CRÉDITO</CENTER>
</td>
</tr>

  <tr>
    <td colspan="2" style="padding: 1px; text-align: justify;">
      Por este conducto autorizo expresamente a <b><u>CONSULTORÍA INTEGRAL DE PENSIONES                                    </u></b>, (en adelante) para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
      Declaro que conozco la naturaleza y alcance de <b>(i)</b> las Sociedades de Información Crediticia; <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;
      <b>(iii)</b> la información que se solicitará <u>a</u> las Sociedades de Información Crediticia y, <b>(iv)</b> el uso que hará de tal información.
      Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
<b><u>Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CONSULTORÍA INTEGRAL DE PENSIONES                                    </u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.</u></b>
    </td>
  </tr>


<tr>
  <td style="padding:8px; vertical-align:top; width:50%;">
    <b>LUGAR Y FECHA:</b><br>
    En que firma la autorización de consulta
    <span id="lugar-funcionario"></span>, <span id="fecha-funcionario"></span>
  </td>

  <td style="padding:8px; text-align:center; vertical-align:top; width:50%;">
    <div style="width:210px; margin:0 auto;">
      <canvas id="firmaVistaAutorizacion" width="210" height="70" style="border:1px solid #ccc;"></canvas>
      <div style="font-size:9px; margin-top:4px; text-align:center;"><b>Firma de autorización del AVAL</b></div>
    </div>
  </td>
</tr>


</table>


<button id="btnImprimir" onclick="imprimirFormato()" style="
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 10px 20px;
  background-color: #1B0088;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  z-index: 1000;
">
  Imprimir Formato
</button>

<button id="btnDescargarPDF" onclick="descargarFormato()" style="
  position: fixed;
  bottom: 70px;
  right: 20px;
  padding: 10px 20px;
  background-color: #006E3C;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  z-index: 1000;
">
  Descargar PDF
</button>
  <!-- Botón de regresar flotante -->
<button id="btnRegresar" onclick="window.location.href='https://hp-v1-production.up.railway.app/'" style="
  position: fixed;
  bottom: 120px;  /* arriba de Descargar (70px) e Imprimir (20px) */
  right: 20px;
  padding: 10px 20px;
  background-color: #a07500;
  color: white;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  z-index: 1000;
">
  Regresar
</button>
</div>







<script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>

<script src="js/formato.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



<script>
window.descargarFormato = async function () {
  const el = document.getElementById('formatoFinal');
  if (!el) { alert('No se encontró #formatoFinal'); return; }

  // === Configura tu hoja ===
  const FORMATO  = 'letter';          // 'letter' | 'a4'
  const ORIENT   = 'portrait';        // 'portrait' | 'landscape'
  const MARGENES = [5, 5, 5, 5];      // [top,right,bottom,left] en mm  (ajusta si quieres)

  const FOLIO = (document.querySelector('#folioTag')?.textContent || 'formato').trim();

  // Tamaños correctos en mm
  const page = (FORMATO === 'a4')
    ? { w: 210,   h: 210 }            // A4
    : { w: 214, h: 214 };         // Letter

  // mm -> px (96 px = 1 in)
  const PX_PER_MM = 96 / 25.4;

  // Ancho imprimible (px) = ancho hoja - márgenes laterales
  const printableWidthPx = (page.w - (MARGENES[1] + MARGENES[3])) * PX_PER_MM;

  // Guardar estilos previos
  const prevEl = {
    width: el.style.width,
    maxWidth: el.style.maxWidth,
    transform: el.style.transform,
    transformOrigin: el.style.transformOrigin
  };
  const prevBody = {
    padding: document.body.style.padding,
    background: document.body.style.background
  };

  // Fijar ancho imprimible SIN escalar
  el.style.width = printableWidthPx + 'px';
  el.style.maxWidth = printableWidthPx + 'px';
  el.style.transform = 'none';
  el.style.transformOrigin = 'initial';

  // Modo PDF para compactar y controlar marca de agua
  el.classList.add('pdf-tight', 'pdf-mode');

  // Quitar padding del body temporalmente
  document.body.style.padding = '0';
  document.body.style.background = 'white';

  // Ocultar botón durante exportación
  const btn = document.getElementById('btnDescargarPDF');
  const prevBtn = btn?.style.display;
  if (btn) btn.style.display = 'none';

  const opt = {
    margin: MARGENES,
    filename: `formato_${FOLIO}.pdf`,
    image: { type: 'jpeg', quality: 0.98 },
    html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
    jsPDF: { unit: 'mm', format: FORMATO, orientation: ORIENT },
    pagebreak: { mode: ['css', 'legacy'] } // usa .page-break / .pagebreak y .starts-page en el HTML/CSS
  };

  try {
    await html2pdf().set(opt).from(el).save();
  } finally {
    // Restaurar
    el.classList.remove('pdf-tight', 'pdf-mode');
    el.style.width = prevEl.width || '';
    el.style.maxWidth = prevEl.maxWidth || '';
    el.style.transform = prevEl.transform || '';
    el.style.transformOrigin = prevEl.transformOrigin || '';
    document.body.style.padding = prevBody.padding || '';
    document.body.style.background = prevBody.background || '';
    if (btn) btn.style.display = prevBtn || '';
  }
};
</script>

</body>
</html>

