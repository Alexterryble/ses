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
</head>
<body>

<div class="form-container formulario-multipaso" id="formulario">
 

<!-- Paso 1 -->
<div class="step active" id="step-1">
  <h1>Llenar solicitud de crédito</h1>

  <label for="atendio">Nombre de quien lo atendió:</label>
  <select id="atendio" name="atendio">
    <option value="">-- Selecciona --</option>
    <!-- tus opciones... -->
  </select>
  <!-- Se rellena automáticamente desde el select -->
  <input type="hidden" id="atendido_por" name="atendido_por">

  <label>¿Cómo se enteró de nosotros?</label>
  <select id="medio">
    <option value="">Seleccione</option>
    <option value="Voseo">Voseo</option>
    <option value="Volante">Volante</option>
    <option value="TV">TV</option>
    <option value="Radio">Radio</option>
    <option value="Recomendado">Recomendado</option>
    <option value="Letrero">Letrero</option>
    <option value="Internet">Internet</option>
    <option value="Prensa">Prensa</option>
    <option value="Cambaceo">Cambaceo</option>
    <option value="Asesor">Asesor</option>
    <option value="Otros">Otros</option>
  </select>

  <label>Monto del préstamo solicitado:</label>
  <input type="number" id="monto" inputmode="decimal" />

  <label for="plazo">Plazo en meses:</label>
  <input type="number" id="plazo" min="1" max="60" step="1" placeholder="Ej. 12" />

 <label for="tasa_mensual">Tasa mensual (%):</label>
<input
  type="number"
  id="tasa_mensual"
  name="tasa_mensual"
  min="0"
  step="0.01"
  value="10.5"
  placeholder="Ej. 10.5"
/>

  <label for="frecuencia">Frecuencia del pago:</label>
  <input type="text" id="frecuencia" class="badge-frecuencia" value="Mensual" readonly />

<label for="contrato_modalidad">Tipo de contrato (modalidad):</label>
<select id="contrato_modalidad">
  <option value="">-- Selecciona --</option>
  <option value="P10">Personal 10</option>
  <option value="SEM_P10">Sem Personal 10</option>
  <option value="P10_ORD">Personal 10 Ordinario</option>
  <option value="P40">Personal 40 Retro</option>
  <option value="P40_ORD">Personal 40 Ordinario</option>
</select>

  <div class="navigation-buttons">
    <span></span>
    <button type="button" onclick="guardarPaso1()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>
  </div>
</div>

<div class="step" id="step-2">
  <h1>Datos del Solicitante</h1>
  <hr>
  <label>Nombre(s) sin abreviaturas:</label>
  <input type="text" id="nombres">

  <label>Apellido Paterno:</label>
  <input type="text" id="apellido_paterno">

  <label>Apellido Materno:</label>
  <input type="text" id="apellido_materno">

  <label>Género:</label>
  <select id="genero">
    <option value="">Seleccione</option>
    <option value="Hombre">Hombre</option>
    <option value="Mujer">Mujer</option>
  </select>

  <label>Fecha de nacimiento:</label>
  <input type="date" id="fecha_nacimiento" placeholder="DD/MM/AAAA">

  <label>Código Postal:</label>
  <input type="text" id="cp">

<label for="estado_nacimiento">Entidad federativa de nacimiento:</label>
<select id="estado_nacimiento">
  <option value="">Seleccione un estado</option>
  <option value="Aguascalientes">Aguascalientes</option>
  <option value="Baja California">Baja California</option>
  <option value="Baja California Sur">Baja California Sur</option>
  <option value="Campeche">Campeche</option>
  <option value="Chiapas">Chiapas</option>
  <option value="Chihuahua">Chihuahua</option>
  <option value="Ciudad de México">Ciudad de México</option>
  <option value="Coahuila">Coahuila</option>
  <option value="Colima">Colima</option>
  <option value="Durango">Durango</option>
  <option value="Estado de México">Estado de México</option>
  <option value="Guanajuato">Guanajuato</option>
  <option value="Guerrero">Guerrero</option>
  <option value="Hidalgo">Hidalgo</option>
  <option value="Jalisco">Jalisco</option>
  <option value="Michoacán">Michoacán</option>
  <option value="Morelos">Morelos</option>
  <option value="Nayarit">Nayarit</option>
  <option value="Nuevo León">Nuevo León</option>
  <option value="Oaxaca">Oaxaca</option>
  <option value="Puebla">Puebla</option>
  <option value="Querétaro">Querétaro</option>
  <option value="Quintana Roo">Quintana Roo</option>
  <option value="San Luis Potosí">San Luis Potosí</option>
  <option value="Sinaloa">Sinaloa</option>
  <option value="Sonora">Sonora</option>
  <option value="Tabasco">Tabasco</option>
  <option value="Tamaulipas">Tamaulipas</option>
  <option value="Tlaxcala">Tlaxcala</option>
  <option value="Veracruz">Veracruz</option>
  <option value="Yucatán">Yucatán</option>
  <option value="Zacatecas">Zacatecas</option>
</select>

<label>Colonia:</label>
<select id="colonia"></select>

<label>Municipio / Delegación:</label>
<input type="text" id="municipio">

<label for="estado">Estado:</label>
<select id="estado" required>
  <option value="" selected disabled>Seleccione un estado…</option>
  <option value="Aguascalientes">Aguascalientes</option>
  <option value="Baja California">Baja California</option>
  <option value="Baja California Sur">Baja California Sur</option>
  <option value="Campeche">Campeche</option>
  <option value="Chiapas">Chiapas</option>
  <option value="Chihuahua">Chihuahua</option>
  <option value="Ciudad de México">Ciudad de México</option>
  <option value="Coahuila">Coahuila</option>
  <option value="Colima">Colima</option>
  <option value="Durango">Durango</option>
  <option value="Estado de México">Estado de México</option>
  <option value="Guanajuato">Guanajuato</option>
  <option value="Guerrero">Guerrero</option>
  <option value="Hidalgo">Hidalgo</option>
  <option value="Jalisco">Jalisco</option>
  <option value="Michoacán">Michoacán</option>
  <option value="Morelos">Morelos</option>
  <option value="Nayarit">Nayarit</option>
  <option value="Nuevo León">Nuevo León</option>
  <option value="Oaxaca">Oaxaca</option>
  <option value="Puebla">Puebla</option>
  <option value="Querétaro">Querétaro</option>
  <option value="Quintana Roo">Quintana Roo</option>
  <option value="San Luis Potosí">San Luis Potosí</option>
  <option value="Sinaloa">Sinaloa</option>
  <option value="Sonora">Sonora</option>
  <option value="Tabasco">Tabasco</option>
  <option value="Tamaulipas">Tamaulipas</option>
  <option value="Tlaxcala">Tlaxcala</option>
  <option value="Veracruz">Veracruz</option>
  <option value="Yucatán">Yucatán</option>
  <option value="Zacatecas">Zacatecas</option>
</select>


<label for="pais">País:</label>
<select id="pais" required autocomplete="country-name">
  <option value="" selected disabled>Seleccione un país…</option>
  <!-- Las opciones se insertan por JS -->
</select>


  <label>Dirección actual (calle y número):</label>
  <input type="text" id="direccion">

  <label>Entre qué calles se encuentra:</label>
  <input type="text" id="entre_calles">


  <label>Nacionalidad:</label>
  <input type="text" id="nacionalidad">

<label for="pais_nacimiento">País de nacimiento:</label>
<select id="pais_nacimiento" required></select>

  <label>¿Cuenta con FIEL / SAT?</label>
  <select id="fiel">
    <option value="">Seleccione</option>
    <option value="Sí">Sí</option>
    <option value="No">No</option>
  </select>
  <label>CURP:</label>
<input type="text" id="curp">

<label>RFC con Homoclave:</label>
<input type="text" id="rfc">



<label>Tiempo en este domicilio:</label>
<input type="text" id="tiempo_domicilio" name="tiempo_domicilio">

  <label>Estado civil:</label>
  <select id="estado_civil">
    <option value="">Seleccione</option>
    <option value="Casado(a)">Casado(a)</option>
    <option value="Unión libre">Unión libre</option>
    <option value="Soltero(a)">Soltero(a)</option>
    <option value="Separado(a)">Separado(a)</option>
    <option value="Divorciado(a)">Divorciado(a)</option>
    <option value="Viudo(a)">Viudo(a)</option>
  </select>

<label>Tiempo (en estado civil):</label>
<input type="text" id="tiempo_estado_civil" name="tiempo_estado_civil">


    <label>Dependientes económicos:</label>
  <input type="text" id="dependientes">

  <label>Nivel máximo de estudios:</label>
  <select id="escolaridad">
    <option value="">Seleccione</option>
    <option value="Primaria">Primaria</option>
    <option value="Secundaria">Secundaria</option>
    <option value="Preparatoria">Preparatoria</option>
    <option value="Técnico">Técnico</option>
    <option value="Licenciatura">Licenciatura</option>
    <option value="Posgrado">Posgrado</option>
  </select>

  <label>Profesión:</label>
  <input type="text" id="profesion">

  <label>Correo electrónico (si cuenta con él):</label>
  <input type="email" id="correo">

  <label>Teléfono(s):</label>
  <input type="text" id="telefono">

  <label>Tel. Celular:</label>
  <input type="text" id="celular">

  <label>Horario de contacto:</label>
  <input type="text" id="mejor_hora">

  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
<button type="button" onclick="guardarPaso2()">Guardar</button>
<button type="button" onclick="omitir()">Omitir</button>
  </div>
</div>



<div class="step" id="step-3">
  <h1>Información Laboral</h1>

<div class="trabaja-container">
    <label for="trabaja">¿Actualmente trabaja?</label>
    <input type="hidden" id="trabaja" value="">

    <div class="work-cards" role="radiogroup" aria-label="¿Trabaja actualmente?">
        <div class="work-card">
            <input type="radio" id="opTrabajaSi" name="trabaja_cards" value="si">
            <label for="opTrabajaSi">
                <span class="icon">💼</span>
                <span class="title">Sí</span>
                <span class="desc">Capturar datos laborales</span>
            </label>
        </div>

        <div class="work-card">
            <input type="radio" id="opTrabajaNo" name="trabaja_cards" value="no">
            <label for="opTrabajaNo">
                <span class="icon">🌱</span>
                <span class="title">No</span>
                <span class="desc">Marcar N/A y desactivar</span>
            </label>
        </div>
    </div>
</div>


  <hr>
  <label>Puesto / Posición en el empleo:</label>
  <input type="text" id="puesto">

  <label>Nombre de la empresa / negocio / patrón:</label>
  <input type="text" id="empresa">

<label for="giro_empresa">Giro o actividad de la empresa:</label>
<select id="giro_empresa" class="form-control"></select>

<label>Código Postal (trabajo):</label>
<input type="text" id="cp_trabajo" inputmode="numeric" maxlength="5" class="form-control">

  <label>Dirección del trabajo:</label>
  <input type="text" id="direccion_trabajo">

  <label>Entre qué calles se encuentra (trabajo):</label>
  <input type="text" id="calles_trabajo">

  <label>Referencia (Ubicación de empresa/trabajo):</label>
  <input type="text" id="ref_empresa_trabajo_input">

<label>Colonia (trabajo):</label>
<select id="colonia_trabajo" class="form-control"></select>

<label>Municipio (trabajo):</label>
<input type="text" id="municipio_trabajo" class="form-control">

<label>Estado (trabajo):</label>
<input type="text" id="estado_trabajo" class="form-control">


  <label>País (trabajo):</label>
  <input type="text" id="pais_trabajo">

  <label>Tiempo en este empleo:</label>
  <input type="text" id="tiempo_empleo">

  <label>Teléfono (trabajo):</label>
  <input type="text" id="telefono_trabajo">

  <label>Horario de trabajo:</label>
  <input type="text" id="horario_trabajo">

  <label>Sueldo mensual fijo:</label>
  <input type="text" id="sueldo">

  <label>Forma de pago:</label>
  <select id="forma_pago">
    <option value="">Seleccione</option>
    <option value="Diario">Diario</option>
    <option value="Semanal">Semanal</option>
    <option value="Quincenal">Quincenal</option>
    <option value="Mensual">Mensual</option>
  </select>

  <label>Otros ingresos variables:</label>
  <input type="text" id="otros_ingresos">

  <label>Fuente de estos ingresos:</label>
  <input type="text" id="fuente_ingresos">

  <label>El negocio se encuentra en:</label>
  <select id="ubicacion_negocio">
    <option value="">Seleccione</option>
    <option value="Local">Local</option>
    <option value="Vía pública">Vía pública</option>
    <option value="Mercado/Tianguis">Mercado/Tianguis</option>
    <option value="Cambaceo">Cambaceo</option>
    <option value="Oficios">Oficios</option>
    <option value="Domicilio particular">Domicilio particular</option>
  </select>

  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" onclick="guardarPaso3()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>

  </div>
</div>




<div class="step" id="step-4">
  <h1>Datos de Vivienda del Solicitante</h1>
  <hr>
<div class="form-group">
  <label class="titulo-seccion">Tipo de vivienda:</label>
  <div class="radio-horizontal-alineado">
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Propia"> Propia
    </label>
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Familiar"> Familiar
    </label>
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Hipoteca"> Hipoteca
    </label>
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Huesped"> Huésped
    </label>
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Renta"> Renta
    </label>
    <label class="radio-opcion">
      <input type="radio" name="tipo_vivienda" value="Otro"> Otro
    </label>
  </div>
</div>

  <br>

  <label>Pago Casa:</label>
  <input type="text" id="pago_casa">

  <label>Pago de servicios:</label>
  <input type="text" id="pago_servicios">

  <label>Pago otros:</label>
  <input type="text" id="pago_otros">

  <label>Gasto mensual predial, agua, etc.:</label>
  <input type="text" id="gasto_mensual">



<div id="grupo-propia" style="display: none;">
  <label>Valor de la casa:</label>
  <input type="text" id="valor_casa">
</div>

<div id="grupo-hipoteca" style="display: none;">
  <label>Saldo de la hipoteca:</label>
  <input type="text" id="saldo_hipoteca">

  <label>Empresa que financia la hipoteca:</label>
  <input type="text" id="empresa_hipoteca">
</div>

<div id="grupo-propietario" style="display: none;">
  <label>Nombre del propietario:</label>
  <input type="text" id="nombre_propietario">

  <div id="campo-parentesco">
    <label>Parentesco:</label>
    <input type="text" id="parentesco">
  </div>

  <label>Teléfono del propietario:</label>
  <input type="text" id="telefono_propietario">
</div>



<div class="radio-fila">
  <label>¿Posee auto?</label>
  <label>Sí<input type="radio" name="posee_auto" value="Sí" onchange="toggleAutoFields()"> </label>
  <label>No<input type="radio" name="posee_auto" value="No" onchange="toggleAutoFields()"> </label>
</div>


<div id="auto-fields" style="display: none;">
  <label>Marca, Modelo, Año del auto:</label>
  <input type="text" id="marca_auto" name="marca_auto">

  <label>Valor factura del auto:</label>
  <input type="text" id="valor_auto" name="valor_auto">

  <label>Empresa que financia el auto:</label>
  <input type="text" id="empresa_auto" name="empresa_auto">

  <label>Mensualidad:</label>
  <input type="text" id="mensualidad_auto" name="mensualidad_auto">
</div>

  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="guardarPaso4()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>

    
  </div>
</div>


<div class="step" id="step-5">
<h1>Firmas e Investigación</h1>

<div class="pregunta-radio">
  <label>¿Ha desempeñado funciones públicas destacadas?</label>
  <div class="radio-fila">
    <label>Sí<input type="radio" name="funcion_publica" value="Sí"></label>
    <label>No<input type="radio" name="funcion_publica" value="No"></label>
  </div>
</div>

<div class="pregunta-radio">
  <label>¿Tiene parentesco con alguien que desempeña funciones públicas?</label>
  <div class="radio-fila">
    <label>Sí<input type="radio" name="relacion_funcion_publica" value="Sí"></label>
    <label>No<input type="radio" name="relacion_funcion_publica" value="No"></label>
  </div>
</div>

  <label>Folio de consulta:</label>
  <input type="text" id="folio_consulta">


 <div class="declaracion" style="background-color: #f9f9f9; border-left: 4px solid #1B0088; padding: 15px; font-size: 14px; line-height: 1.5;">
    <i>
      DECLARO QUE PARA EFECTOS DEL CRÉDITO QUE VOY A CONTRATAR ACTÚO A NOMBRE Y POR CUENTA PROPIA, PROPORCIONARÉ COPIA SIMPLE DE IDENTIFICACIÓN OFICIAL, CURP, RFC, FIEL (SI CUENTO CON ELLA), Y COMPROBANTE DE DOMICILIO COMO PARTE INTEGRAL DE MI EXPEDIENTE.
    </i><br><br>

    Hago constar que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en:

    <a href="https://www.cipmexico.com.mx/" target="_blank">https://www.cipmexico.com.mx/</a>
  </div>

<!-- ✅ Firma 1: Consulta Crediticia -->
<div class="firma-bloque">
  <label><strong>Firma de autorización</strong></label>
  <canvas id="firmaCanvas1" width="350" height="200"></canvas>
  <button type="button" onclick="borrarFirma('firmaCanvas1', ['firmaCanvasVista1'])" class="btn btn-sm btn-primary mt-2">Borrar firma</button>
</div>

  <br><br>
 

 <div style="background-color: #fdfdfd; border-left: 4px solid #1B0088; padding: 15px; font-size: 14px; line-height: 1.6; text-align: justify;">
    Por este conducto autorizo expresamente a <b><u>CONSULTORÍA INTEGRAL DE PENSIONES</u></b> (en adelante), para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
    <br><br>
    Declaro que conozco la naturaleza y alcance de:  
    <b>(i)</b> las Sociedades de Información Crediticia;  
    <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;  
    <b>(iii)</b> la información que se solicitará <u>a</u> las Sociedades de Información Crediticia; y  
    <b>(iv)</b> el uso que <u>hará de</u> tal información.
    <br><br>
    Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
    <br><br>
    Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CIP Financial México</u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.
    <br><br>
  </div>
  <br><br>
<div class="firma-bloque">
  <label><strong>Firma del solicitante (Declaración y privacidad):</strong></label>
  <canvas id="firmaCanvas2" width="350" height="200"></canvas>
  <button type="button" onclick="borrarFirma('firmaCanvas2', ['firmaCanvasVista2'])" class="btn btn-sm btn-primary mt-2">Borrar firma</button>
</div>


<div class="pregunta-radio">
  <label for="lugar">Lugar:</label>
  <input type="text" id="lugar" placeholder="Ej. Toluca, CDMX">
</div>

<div class="pregunta-radio">
  <label for="fecha">Fecha:</label>
  <input type="date" id="fecha">
</div>
  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" onclick="guardarPaso5()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>

  </div>
</div>

<!-- Segunda hoja -->
<div class="step" id="step-6">
  <h1>Referencias Familiares Del Solicitante</h1>
  <hr>

  <h3>1.- Referencia del Solicitante</h3>
  <label>Nombre completo:</label>
  <input type="text" id="form_ref_fam_nombre">

  <label>Dirección:</label>
  <input type="text" id="form_ref_fam_direccion">

  <label>Teléfono(s):</label>
  <input type="text" id="form_ref_fam_telefono">

  <label>Celular:</label>
  <input type="text" id="form_ref_fam_celular">

  <label>Correo:</label>
<input type="email" id="form_ref_fam_correo" placeholder="correo@ejemplo.com">


  <label>Parentesco:</label>
  <input type="text" id="form_ref_fam_parentesco">


  <hr>

  <h3>2.- Referencia del Solicitante</h3>
  <label for="form_ref_fam_nombre_2">Nombre completo:</label>
  <input type="text" id="form_ref_fam_nombre_2" name="form_ref_fam_nombre_2">

  <label for="form_ref_fam_direccion_2">Dirección:</label>
  <input type="text" id="form_ref_fam_direccion_2" name="form_ref_fam_direccion_2">

  <label for="form_ref_fam_telefono_2">Teléfono(s):</label>
  <input type="text" id="form_ref_fam_telefono_2" name="form_ref_fam_telefono_2">

  <label for="form_ref_fam_celular_2">Celular:</label>
  <input type="text" id="form_ref_fam_celular_2" name="form_ref_fam_celular_2">

    <!-- 2.- Referencia del Solicitante (Familiar #2) -->
<label for="form_ref_fam_correo_2">Correo:</label>
<input type="email" id="form_ref_fam_correo_2" name="form_ref_fam_correo_2" placeholder="correo@ejemplo.com">


  <label for="form_ref_fam_parentesco_2">Parentesco:</label>
  <input type="text" id="form_ref_fam_parentesco_2" name="form_ref_fam_parentesco_2">



  <hr>
  <h3>3.- Referencia del Solicitante</h3>

  <label>Nombre completo:</label>
  <input type="text" id="form_ref_per_nombre">

  <label>Dirección:</label>
  <input type="text" id="form_ref_per_direccion">

  <label>Teléfono(s):</label>
  <input type="text" id="form_ref_per_telefono">

  <label>Celular:</label>
  <input type="text" id="form_ref_per_celular">

  <!-- 3.- Referencia del Solicitante (Personal #1) -->
<label>Correo:</label>
<input type="email" id="form_ref_per_correo" placeholder="correo@ejemplo.com">


  <label>Parentesco:</label>
  <input type="text" id="form_ref_per_parentesco">

  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" onclick="guardarPaso6()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>
  </div>
</div>




<div class="step" id="step-7">
  <h1>Datos del Co-deudor</h1>
  <hr>

  <label>Nombre:</label>
  <input type="text" id="form_co_nombre">

  <label>Apellido Paterno:</label>
  <input type="text" id="form_co_apellido_paterno">

  <label>Apellido Materno:</label>
  <input type="text" id="form_co_apellido_materno">

  <label>Parentesco:</label>
  <input type="text" id="form_co_parentesco">

  <label>Correo electrónico:</label>
  <input type="email" id="form_co_correo">

  <label>Género:</label>
  <select id="form_co_genero">
    <option value="">Seleccione</option>
    <option value="Masculino">Masculino</option>
    <option value="Femenino">Femenino</option>
    <option value="Otro">Otro</option>
  </select>

  <label>Fecha de nacimiento:</label>
  <input type="date" id="form_co_nacimiento">

  <!-- === Dirección (mismo orden que step-2) === -->
  <label>Código Postal:</label>
  <input type="text" id="form_co_cp">

  <label for="form_co_entidad">Entidad federativa de nacimiento:</label>
  <select id="form_co_entidad">
    <option value="">Seleccione un estado</option>
    <option value="Aguascalientes">Aguascalientes</option>
    <option value="Baja California">Baja California</option>
    <option value="Baja California Sur">Baja California Sur</option>
    <option value="Campeche">Campeche</option>
    <option value="Chiapas">Chiapas</option>
    <option value="Chihuahua">Chihuahua</option>
    <option value="Ciudad de México">Ciudad de México</option>
    <option value="Coahuila">Coahuila</option>
    <option value="Colima">Colima</option>
    <option value="Durango">Durango</option>
    <option value="Estado de México">Estado de México</option>
    <option value="Guanajuato">Guanajuato</option>
    <option value="Guerrero">Guerrero</option>
    <option value="Hidalgo">Hidalgo</option>
    <option value="Jalisco">Jalisco</option>
    <option value="Michoacán">Michoacán</option>
    <option value="Morelos">Morelos</option>
    <option value="Nayarit">Nayarit</option>
    <option value="Nuevo León">Nuevo León</option>
    <option value="Oaxaca">Oaxaca</option>
    <option value="Puebla">Puebla</option>
    <option value="Querétaro">Querétaro</option>
    <option value="Quintana Roo">Quintana Roo</option>
    <option value="San Luis Potosí">San Luis Potosí</option>
    <option value="Sinaloa">Sinaloa</option>
    <option value="Sonora">Sonora</option>
    <option value="Tabasco">Tabasco</option>
    <option value="Tamaulipas">Tamaulipas</option>
    <option value="Tlaxcala">Tlaxcala</option>
    <option value="Veracruz">Veracruz</option>
    <option value="Yucatán">Yucatán</option>
    <option value="Zacatecas">Zacatecas</option>
  </select>

  <label>Colonia:</label>
  <select id="form_co_colonia"></select>

  <label>Municipio / Delegación:</label>
  <input type="text" id="form_co_municipio">

  <label>Estado:</label>
  <input type="text" id="form_co_estado">

  <label>País:</label>
  <input type="text" id="form_co_pais">

  <label>Dirección actual (calle y número):</label>
  <input type="text" id="form_co_direccion">

  <label>Entre qué calles se encuentra:</label>
  <input type="text" id="form_co_entre_calles">

  <!-- === Otros datos === -->
  <label>Dependientes Económicos:</label>
  <input type="number" id="form_co_dependientes">

  <label>Nacionalidad:</label>
  <input type="text" id="form_co_nacionalidad">

  <label>País de nacimiento:</label>
  <input type="text" id="form_co_pais_nacimiento">

  <label>RFC con Homoclave:</label>
  <input type="text" id="form_co_rfc">

  <label>CURP:</label>
  <input type="text" id="form_co_curp">

  <label>Tiempo en este domicilio:</label>
  <input type="text" id="form_co_tiempo">

  <label>Teléfono(s):</label>
  <input type="text" id="form_co_tel">

  <label>Tel. Celular:</label>
  <input type="text" id="form_co_cel">

  <label>Horario de contacto:</label>
  <input type="text" id="form_co_mejor_hora">

  <!-- Navegación -->
  <div class="navigation-buttons">
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" onclick="guardarPaso7()">Guardar</button>
    <button type="button" onclick="omitir()">Omitir</button>
  </div>
</div>




<div class="step" id="step-8">
<hr>
<h1>Funciones Públicas y Firma</h1>

<div class="pregunta-radio">
  <label>¿Usted desempeña funciones públicas destacadas?</label>
  <div class="radio-fila">
    <label>Sí <input type="radio" name="form_funcion_publica" value="Sí"></label>
    <label>No <input type="radio" name="form_funcion_publica" value="No"></label>
  </div>
</div>

<div class="pregunta-radio">
  <label>¿Tiene relación con alguien que desempeñe funciones públicas?</label>
  <div class="radio-fila">
    <label>Sí <input type="radio" name="form_relacion_publica" value="Sí"></label>
    <label>No <input type="radio" name="form_relacion_publica" value="No"></label>
  </div>
</div>

  <!-- TEXTO -->
 <div class="declaracion" style="background-color: #f9f9f9; border-left: 4px solid #1B0088; padding: 15px; font-size: 14px; line-height: 1.5;">
    <i>
      DECLARO QUE PARA EFECTOS DEL CRÉDITO QUE VOY A CONTRATAR ACTÚO A NOMBRE Y POR CUENTA PROPIA, PROPORCIONARÉ COPIA SIMPLE DE IDENTIFICACIÓN OFICIAL, CURP, RFC, FIEL (SI CUENTO CON ELLA), Y COMPROBANTE DE DOMICILIO COMO PARTE INTEGRAL DE MI EXPEDIENTE.
    </i><br><br>

    Hago constar que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CONSULTORÍA INTEGRAL DE PENSIONES</span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en:<br><br>

    <a href="https://www.cipmexico.com.mx/" target="_blank">https://www.cipmexico.com.mx/</a>
  </div>

<div class="firma-bloque">
  <label><strong>Firma de autorización</strong></label><br>
  <canvas id="firmaAutorizacion" width="350" height="200"></canvas><br>
<button id="borrarFirmaAutorizacion" type="button" onclick="borrarFirmaAutorizacion()" class="btn btn-sm btn-primary mt-2 no-print">Borrar firma</button>
</div>



 <div style="background-color: #fdfdfd; border-left: 4px solid #1B0088; padding: 15px; font-size: 14px; line-height: 1.6; text-align: justify;">
    Por este conducto autorizo expresamente a <b><u>CONSULTORÍA INTEGRAL DE PENSIONES</u></b> (en adelante), para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
    <br><br>
    Declaro que conozco la naturaleza y alcance de:  
    <b>(i)</b> las Sociedades de Información Crediticia;  
    <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;  
    <b>(iii)</b> la información que se solicitará <u>a</u> las Sociedades de Información Crediticia; y  
    <b>(iv)</b> el uso que <u>hará de</u> tal información.
    <br><br>
    Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
    <br><br>
    Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CIP Financial México</u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.
    <br><br>
  </div>

<div class="firma-bloque">
  <label><strong>Firma del formulario</strong></label><br>
  <canvas id="firmaFormulario" width="350" height="200"></canvas><br>
  <button id="borrarFirmaFormulario" type="button" onclick="borrarFirmaFormulario()" class="btn btn-sm btn-primary mt-2 no-print">Borrar firma</button>
</div>


<div class="pregunta-radio">
  <label for="lugar_funcionario">Lugar:</label>
  <input type="text" id="lugar_funcionario" placeholder="Ej. Toluca, México">
</div>

<div class="pregunta-radio">
  <label for="fecha_funcionario">Fecha:</label>
  <input type="date" id="fecha_funcionario">
</div>


  <!-- Botón final -->
  <div class="navigation-buttons" style="margin-top: 30px;">
    <button type="button" class="btn-back" onclick="goBack()">Volver</button>
    <button type="button" onclick="nextPrev(-1)">Anterior</button>
    <button type="button" onclick="guardarYGenerar()">Generar Formato Borrador</button>


  </div>
</div>
</div>


<div class="formato hidden" id="formatoFinal">

<div class="logo-container">
  <img src="img/logo.png" alt="Logo CIP">
  <div><strong>CONSULTORÍA INTEGRAL DE PENSIONES</strong></div>
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
    <tr><td colspan="4">Nombre(s) sin abreviaturas:<br> <strong id="res-nombres"></strong></td></tr>
    <tr>
      <td colspan="2">Apellido Paterno: <strong id="res-apellido-paterno"></strong><br>&nbsp;</td>
      <td colspan="2"> Apellido Materno: <strong id="res-apellido-materno"></strong>
    </tr>
    <tr>
      <td colspan="2">Fecha de nacimiento (AAAA/MM/DD):<br><strong id="res-fecha-nacimiento"></strong></td>
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
      <td style="width: 40%;">Horario de contacto: <strong id="res-mejor-hora"></strong></td>
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
      Hago constar que <b><span style="text-decoration: underline;">CIP Financial México</span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CIP Financial México</span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en 
      <a href="https://cipMéxico.com.mx/" target="_blank">www.cipmexico.com.mx/</a>
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
      Por este conducto autorizo expresamente a <b><u>CIP Financial México                                    </u></b>, (en adelante) para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
      Declaro que conozco la naturaleza y alcance de <b>(i)</b> las Sociedades de Información Crediticia; <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;
      <b>(iii)</b> la información que se solicitará a las Sociedades de Información Crediticia y, <b>(iv)</b> el uso que hará de tal información.
      Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
      Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CIP Financial México                                    </u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.
    </td>
  </tr>
  <tr>
<td style="padding: 8px; vertical-align: top;">
  <b>LUGAR Y FECHA:</b><br>
  En que firma la autorización de consulta
  <span id="res-lugar">N/A</span>, <span id="res-fecha">N/A</span><br>
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
      <div class="folio-en-esquina">
        Folio: <strong id="res-folio"></strong>
    </div>
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
      3.- REFERENCIA DEL SOLICITANTE<span style="font-weight: normal;"></span>
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
</table>

</div>

<table style="margin-top: 1px;">
<tr>
<td colspan="6" style="background-color: #2a6ebb; color: white; font-weight: bold; font-family: Arial; font-size: 9px; padding: 2px 2px; border-left: 1px solid #2a6ebb; margin-bottom: 5px;">
   <CENTER> DATOS DEL CO - DEUDOR</CENTER>
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
      Hago constar que <b><span style="text-decoration: underline;">CIP Financial México                                    </span></b> hizo de mi conocimiento el Aviso de Privacidad previo a la obtención de mis datos. Estoy de acuerdo con el tratamiento que <b><span style="text-decoration: underline;">CIP Financial México                                    </span></b> le dará a los datos que le he proporcionado, así como con las finalidades señaladas en el propio Aviso de Privacidad, que manifiesto bajo protesta de decir verdad me fue entregado y que lo puedo consultar en cualquier momento en 
      <a href="https://cipMéxico.com.mx/" target="_blank">www.cipmexico.com.mx/</a>
    </p>
  </div>

  <!-- FIRMA DEL SOLICITANTE -->
  <div style="display: flex; flex-direction: column; align-items: center;">
    <canvas id="firmaVistaSolicitante" width="210" height="70" style="border: 1px solid #ccc;"></canvas>
    <div style="font-size: 9px; margin-top: 8px; text-align: center; width: 210px;">
      <b>Firma del SOLICITANTE</b>
    </div>
  </div>
</div>


<table style="margin-top: 1px; width: 100%; border-collapse: collapse; table-layout: fixed;">
  <tr>
    <td colspan="2" style="padding: 1px; text-align: justify;">
      Por este conducto autorizo expresamente a <b><u>CIP Financial México</u></b>, (en adelante) para que, por conducto de sus funcionarios facultados, lleve a cabo las investigaciones sobre mi comportamiento crediticio en las Sociedades de Información Crediticia que estime conveniente.
      Declaro que conozco la naturaleza y alcance de <b>(i)</b> las Sociedades de Información Crediticia; <b>(ii)</b> la información contenida en los reportes de crédito y en los reportes de crédito especiales;
      <b>(iii)</b> la información que se solicitará <u>a</u> las Sociedades de Información Crediticia y, <b>(iv)</b> el uso que hará de tal información.
      Autorizo que se realicen consultas periódicas de mi historial crediticio, consintiendo que esta autorización se encuentre vigente por un período de tres años contados a partir de la firma del presente documento y/o durante todo el tiempo que mantenga una relación jurídica con la empresa.
<b><u>Estoy consciente y acepto que este documento quede bajo custodia de <b><u>CIP Financial México                                    </u></b>, para efectos de control y cumplimiento del artículo 28 de la Ley para Regular las Sociedades de Información Crediticia.</u></b>
    </td>
  </tr>
  <tr>
    <!-- LUGAR Y FECHA -->
    <td style="width: 50%; padding: 8px; vertical-align: top; border-right: 1px solid #2a6ebb;">
      <b>LUGAR Y FECHA:</b><br>
      En que firma la autorización de consulta<span id="lugar-funcionario"></span>, <span id="fecha-funcionario"></span><br>
    </td>

    <!-- FIRMA -->
    <td style="width: 50%; padding: 8px; text-align: center; vertical-align: top;">
      <div style="width: 210px; margin: 0 auto;">
        <canvas id="firmaVistaAutorizacion" width="210" height="70" style="border: 1px solid #ccc;"></canvas>
        <div style="font-size: 9px; margin-top: 4px; text-align: center;"><b>Firma de autorización</b></div>
      </div>
    </td>
  </tr>
</table>

<!-- Botón de regresar flotante -->
<button id="btnRegresar" class="btn-regresar" onclick="window.history.back()">Regresar</button>

</div>

<script src="js/crear.js"></script>
<script src="js/crea.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>




<script>
  // URL absoluta de producción
  const PROD_INDEX = 'https://sempiternal-v1-production.up.railway.app/index.php';

  function goBack() {
    try {
      const sameOriginRef = document.referrer && new URL(document.referrer).origin === location.origin;
      // Si venimos de otra página del mismo origen, prioriza volver (mejor UX)
      if (sameOriginRef) {
        history.back();
        // Por si history.back() no navega (p.ej. entrada directa), haz fallback:
        setTimeout(() => {
          // ¿estamos aún en la misma página?
          if (document.visibilityState === 'visible') location.href = resolveIndexUrl();
        }, 150);
      } else {
        location.href = resolveIndexUrl();
      }
    } catch {
      location.href = resolveIndexUrl();
    }
  }

  // Resuelve la URL de index.php según entorno
  function resolveIndexUrl() {
    const isProd = location.host.includes('up.railway.app');
    if (isProd) return PROD_INDEX;
    // En dev: usa /index.php relativo al origen actual
    return new URL('/index.php', location.origin).href;
  }
</script>




<script>
(function initFechasHoy(ids = ['fecha', 'fecha_funcionario']) {
  function hoyISO() {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  }

  function setDefaultToday(el) {
    if (!el || el.value) return; // no sobrescribir si ya tiene valor
    if ('valueAsDate' in el) {
      const now = new Date();
      el.valueAsDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    } else {
      el.value = hoyISO();
    }
    el.dispatchEvent(new Event('input',  { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function run() {
    ids.forEach(id => setDefaultToday(document.getElementById(id)));
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
</script>



<script>
(function autoFillLugares(){
  // ====== CONFIG ======
  const IDS       = ['lugar_funcionario', 'lugar'];
  const CACHE_KEY = 'geo:lugar:es';
  const CACHE_MS  = 24 * 60 * 60 * 1000; // 24h
  const FALLBACK  = 'Toluca, Estado de México, MX';
  const MAX_TRIES = 3;

  // ====== helpers ======
  function setIfEmpty(id, value){
    const el = document.getElementById(id);
    if (!el || !value) return;
    if (el.value && el.value.trim() !== '') return;
    el.value = value;
    el.dispatchEvent(new Event('input',  { bubbles:true }));
    el.dispatchEvent(new Event('change', { bubbles:true }));
  }

  function readCache(){
    try {
      const raw = localStorage.getItem(CACHE_KEY);
      if (!raw) return null;
      const { v, t } = JSON.parse(raw);
      if (!v || !t) return null;
      if (Date.now() - t > CACHE_MS) return null;
      return v;
    } catch { return null; }
  }

  function writeCache(v){
    try { localStorage.setItem(CACHE_KEY, JSON.stringify({ v, t: Date.now() })); } catch {}
  }

  // --- 1) INTENTO WEB NORMAL ---
  function getPositionBrowser(opts){
    return new Promise((resolve, reject) => {
      if (!('geolocation' in navigator)) return reject(new Error('No hay geolocalización en este contexto'));
      navigator.geolocation.getCurrentPosition(resolve, reject, opts);
    });
  }

  // --- 2) INTENTO PUENTE ANDROID (si lo tienes) ---
  async function getPositionAndroidBridge(){
    // aquí adáptalo al nombre que pusiste en tu WebView
    // ej. en Android: webView.addJavascriptInterface(new MyGeo(), "AndroidGeo");
    if (window.AndroidGeo && typeof window.AndroidGeo.getLocation === 'function') {
      // esperamos algo como "19.289,-99.655"
      const s = window.AndroidGeo.getLocation();
      if (s && typeof s === 'string' && s.includes(',')) {
        const [lat, lon] = s.split(',').map(Number);
        if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
          return { coords: { latitude: lat, longitude: lon } };
        }
      }
    }

    // alternativa: algunos lo exponen como window.Android.getLocation()
    if (window.Android && typeof window.Android.getLocation === 'function') {
      const s = window.Android.getLocation();
      if (s && typeof s === 'string' && s.includes(',')) {
        const [lat, lon] = s.split(',').map(Number);
        if (!Number.isNaN(lat) && !Number.isNaN(lon)) {
          return { coords: { latitude: lat, longitude: lon } };
        }
      }
    }

    throw new Error('No hay puente Android');
  }

  // --- reverse geocode ---
  async function reverseGeocodeES(lat, lon){
    const url = new URL('https://nominatim.openstreetmap.org/reverse');
    url.searchParams.set('lat', String(lat));
    url.searchParams.set('lon', String(lon));
    url.searchParams.set('format', 'jsonv2');
    url.searchParams.set('accept-language', 'es');
    url.searchParams.set('addressdetails', '1');

    const resp = await fetch(url);
    const j = await resp.json().catch(() => ({}));
    const a = j.address || {};
    const ciudad = a.city || a.town || a.village || a.locality || a.municipality || a.county;
    const estado = a.state || a.region;
    const pais   = a.country;
    return [ciudad, estado, pais].filter(Boolean).join(', ');
  }

  async function tryOnce() {
    // 1) browser normal
    try {
      const pos = await getPositionBrowser({
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 300000
      });
      const txt = await reverseGeocodeES(pos.coords.latitude, pos.coords.longitude);
      if (txt) return txt;
    } catch (e) {
      console.warn('[geo] navegador no dio posición:', e.message || e);
    }

    // 2) android bridge
    try {
      const pos = await getPositionAndroidBridge();
      const txt = await reverseGeocodeES(pos.coords.latitude, pos.coords.longitude);
      if (txt) return txt;
    } catch (e) {
      console.warn('[geo] puente Android no disponible:', e.message || e);
    }

    return null;
  }

  async function run(){
    // si los 2 ya tienen valor, no hagas nada
    const allFilled = IDS.every(id => {
      const el = document.getElementById(id);
      return el && el.value && el.value.trim() !== '';
    });
    if (allFilled) return;

    // 1) caché
    const cached = readCache();
    if (cached) {
      IDS.forEach(id => setIfEmpty(id, cached));
      return;
    }

    // 2) reintentar varias veces porque en WebView el permiso llega tarde
    let ubic = null;
    for (let i = 0; i < MAX_TRIES && !ubic; i++) {
      ubic = await tryOnce();
      if (!ubic) {
        // espera un poquito y vuelve a pedir
        await new Promise(r => setTimeout(r, 2000));
      }
    }

    // 3) si ya la tenemos
    if (ubic) {
      IDS.forEach(id => setIfEmpty(id, ubic));
      writeCache(ubic);
      return;
    }

    // 4) último recurso
    IDS.forEach(id => setIfEmpty(id, FALLBACK));
  }

  // dispara
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', run);
  } else {
    run();
  }
})();
</script>

<script>
(function () {
  const $ = s => document.querySelector(s);

  // IDs de campos laborales a bloquear cuando NO trabaja
  const FIELD_IDS = [
    'puesto','empresa','giro_empresa','cp_trabajo','direccion_trabajo',
    'calles_trabajo','ref_empresa_trabajo_input','colonia_trabajo',
    'municipio_trabajo','estado_trabajo','pais_trabajo','tiempo_empleo',
    'telefono_trabajo','horario_trabajo','sueldo','forma_pago',
    'otros_ingresos','fuente_ingresos','ubicacion_negocio'
  ];

  // Defaults para “No trabaja”
  const DEFAULTS_NO = {
    puesto:'N/A', empresa:'N/A', giro_empresa:'',
    cp_trabajo:'', direccion_trabajo:'N/A', calles_trabajo:'N/A',
    ref_empresa_trabajo_input:'N/A', colonia_trabajo:'',
    municipio_trabajo:'N/A', estado_trabajo:'N/A', pais_trabajo:'N/A',
    tiempo_empleo:'N/A', telefono_trabajo:'', horario_trabajo:'N/A',
    sueldo:'0', forma_pago:'', otros_ingresos:'0', fuente_ingresos:'N/A',
    ubicacion_negocio:''
  };

  function setDisabledMode(disabled) {
    FIELD_IDS.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      el.disabled = disabled;
      el.classList.toggle('is-disabled', disabled);
    });
  }

  function fillDefaultsForNoTrabajo() {
    Object.entries(DEFAULTS_NO).forEach(([id,val]) => {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName === 'SELECT') el.value = val;
      else el.value = val;
      el.dispatchEvent(new Event('input', {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    });
  }

  function clearForSiTrabajo() {
    FIELD_IDS.forEach(id => {
      const el = document.getElementById(id);
      if (!el) return;
      if (el.tagName !== 'SELECT') {
        if (el.value === 'N/A' || el.value === '0') el.value = '';
      }
      el.dispatchEvent(new Event('input', {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    });
  }

  function syncHidden(value) {
    const h = document.getElementById('trabaja');
    if (h) h.value = value; // para tu guardarPaso3 existente
  }

  function onChangeCards() {
    const val = document.querySelector('input[name="trabaja_cards"]:checked')?.value || '';
    syncHidden(val);
    if (val === 'no') { setDisabledMode(true);  fillDefaultsForNoTrabajo(); }
    else if (val === 'si') { setDisabledMode(false); clearForSiTrabajo(); }
    else { setDisabledMode(false); }
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Escuchar cambios en las tarjetas
    document.querySelectorAll('input[name="trabaja_cards"]').forEach(r =>
      r.addEventListener('change', onChangeCards)
    );

    // Si backend ya marcó algo en #trabaja, refleja en tarjetas
    const initial = ($('#trabaja')?.value || '').toLowerCase();
    if (initial === 'si') $('#opTrabajaSi').checked = true;
    if (initial === 'no') $('#opTrabajaNo').checked = true;

    onChangeCards(); // aplicar estado inicial
  });

  // Si quieres que guardarPaso3 siga funcionando igual,
  // ya leerá #trabaja (si/no) como antes.
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formulario') || document.querySelector('.form-container');
  const steps = Array.from(document.querySelectorAll('.step'));

  if (!form || !steps.length) return;

  /* Evita duplicar la barra si ya existe */
  if (document.querySelector('.cip-stepper')) return;

  const labels = [
    'Solicitud',
    'Solicitante',
    'Laboral',
    'Vivienda',
    'Firmas',
    'Referencias',
    'Co-deudor',
    'Final'
  ];

  const stepper = document.createElement('div');
  stepper.className = 'cip-stepper';

  stepper.innerHTML = `
    <div class="cip-stepper-top">
      <div class="cip-stepper-title">Solicitud de Crédito</div>
      <div class="cip-stepper-count" id="cipStepText">Paso 1 de ${steps.length}</div>
    </div>

    <div class="cip-stepper-line">
      <div class="cip-stepper-fill" id="cipStepFill"></div>
    </div>

    <div class="cip-stepper-items">
      ${steps.map((_, index) => `
        <button type="button" class="cip-step-btn" data-step="${index}">
          <span class="num">${index + 1}</span>
          <span class="txt">${labels[index] || 'Paso'}</span>
        </button>
      `).join('')}
    </div>
  `;

  form.parentNode.insertBefore(stepper, form);

  const buttons = Array.from(stepper.querySelectorAll('.cip-step-btn'));
  const fill = document.getElementById('cipStepFill');
  const text = document.getElementById('cipStepText');

  function getActiveIndex() {
    const index = steps.findIndex(step => step.classList.contains('active'));
    return index >= 0 ? index : 0;
  }

  function updateStepper() {
    const activeIndex = getActiveIndex();
    const percent = ((activeIndex + 1) / steps.length) * 100;

    if (fill) fill.style.width = `${percent}%`;
    if (text) text.textContent = `Paso ${activeIndex + 1} de ${steps.length}`;

    buttons.forEach((btn, index) => {
      btn.classList.toggle('is-active', index === activeIndex);
      btn.classList.toggle('is-complete', index < activeIndex);
    });
  }

  window.cipGoToStep = function(index) {
    if (index < 0 || index >= steps.length) return;

    steps.forEach(step => step.classList.remove('active'));
    steps[index].classList.add('active');

    /* Compatibilidad por si tu código usa currentStep o currentTab */
    window.currentStep = index;
    window.currentTab = index;

    updateStepper();

    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  };

  buttons.forEach(btn => {
    btn.addEventListener('click', () => {
      const index = Number(btn.dataset.step);
      window.cipGoToStep(index);
    });
  });

  /* Si tus botones Guardar/Omitir cambian de paso, la barra se actualiza sola */
  const observer = new MutationObserver(updateStepper);

  steps.forEach(step => {
    observer.observe(step, {
      attributes: true,
      attributeFilter: ['class']
    });
  });

  updateStepper();
});
</script>
</body>
</html>





