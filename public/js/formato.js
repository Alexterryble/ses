// ==== helpers (una sola vez) ====
function extraerYYYYMMDD(str) {
  const m = String(str || '').match(/\b\d{4}-\d{2}-\d{2}\b/);
  return m ? m[0] : '';
}
function formatearYYYYMMDDaLargoMX(str) {
  const ymd = extraerYYYYMMDD(str);
  if (!ymd) return null;
  const m = ymd.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
  return `${m[3]} de ${meses[parseInt(m[2],10)-1]} de ${m[1]}`;
}
function setText(id, value, fallback='N/A'){
  const el = document.getElementById(id);
  if (el) el.textContent = (value ?? '').toString().trim() || fallback;
}

/* === Normalizador de escolaridad === */
const MAP_ESC = {
  // textos comunes
  primaria: 'Primaria',
  secundaria: 'Secundaria',
  prepa: 'Bachillerato',
  bachillerato: 'Bachillerato',
  tecnico: 'Técnico',
  tecnólogo: 'Técnico/Tecnólogo',
  tecnologo: 'Técnico/Tecnólogo',
  lic: 'Licenciatura',
  licenciatura: 'Licenciatura',
  maestria: 'Maestría',
  maestría: 'Maestría',
  doctorado: 'Doctorado',
  // abreviaturas típicas
  pri: 'Primaria',
  sec: 'Secundaria',
  bach: 'Bachillerato',
  tsu: 'Técnico',
  ing: 'Licenciatura',
  mtr: 'Maestría',
  msc: 'Maestría',
  phd: 'Doctorado',
  dr: 'Doctorado',
  // posibles códigos numéricos
  '1': 'Primaria',
  '2': 'Secundaria',
  '3': 'Bachillerato',
  '4': 'Licenciatura',
  '5': 'Maestría',
  '6': 'Doctorado'
};
function normalizaEscolaridad(v){
  if (v == null) return '';
  const k = String(v).trim().toLowerCase()
    .normalize('NFD').replace(/\p{Diacritic}/gu,''); // quita acentos
  return MAP_ESC[k] || String(v).trim(); // si no está en el mapa, deja el original
}
function getNivelEstudios(dp){
  return (
    dp?.nivel_estudios ??
    dp?.escolaridad ??
    dp?.nivel_maximo ??
    dp?.nivelMaximo ??
    dp?.grado_estudios ??
    dp?.grado ??
    ''
  );
}

document.addEventListener('DOMContentLoaded', () => {
  // 1) Lee del SessionStorage y NORMALIZA el nivel
  const raw = JSON.parse(sessionStorage.getItem('solicitud_completa'));
  const d = (raw && raw.datos) ? raw.datos : raw;  // << aquí nace `d`
  if (!d) { console.error("❌ No se encontraron datos (ni raw.datos)."); return; }

  // --- FOLIO en la esquina ---
  const sol = d.solicitudes || {};
  const qs  = new URLSearchParams(location.search);
  const folio =
    sol.folio ??
    sol.folio_solicitud ??
    sol.folioSolicitud ??
    sol.id_folio ??
    d.folio ??
    raw?.folio ??
    sessionStorage.getItem('folio') ??
    qs.get('folio') ??
    qs.get('solicitud_id') ??
    '';
  // pinta; si no hay folio, deja '—'
  setText('folioTexto', (folio || '').toString().trim(), '—');

  // 2) Firmas (tu helper intacto)
  const cargarFirmaCanvas = (canvasId, base64) => {
    const canvas = document.getElementById(canvasId);
    if (!canvas || !base64) return;
    const ctx = canvas.getContext('2d');
    const img = new Image();
    img.onload = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      const scale = Math.min(canvas.width / img.width, canvas.height / img.height);
      const x = (canvas.width - img.width * scale) / 2;
      const y = (canvas.height - img.height * scale) / 2;
      ctx.drawImage(img, 0, 0, img.width, img.height, x, y, img.width * scale, img.height * scale);
    };
    img.src = base64;
  };

  // 3) CABECERA (desde tabla `solicitudes`)
  const ia    = d.info_adicional || {};
  const ffTop = d.funcionarios_firma || {};
  const cita0 = (Array.isArray(d.citas) && d.citas[0]) ? d.citas[0] : {};

  const atendio = sol.atendido_por
               ?? ffTop.nombre_funcionario
               ?? cita0.nombre_funcionario
               ?? 'N/A';

  const medio = sol.medio
             ?? ia.como_se_entero
             ?? ia.medio
             ?? 'N/A';

  const montoFmt = (() => {
    const n = Number(String(sol.monto ?? '').replace(/[^\d.]/g,''));
    return Number.isFinite(n) ? n.toLocaleString('es-MX') : (sol.monto ?? '0');
  })();

  const plazo = (sol.plazo ?? ia.plazo_meses ?? ia.plazo ?? '0');
  const frecuencia = (sol.frecuencia_pago ?? ia.frecuencia_pago ?? ia.frecuencia ?? 'N/A');

  setText('res-atendio', atendio);
  setText('res-medio', medio);
  setText('res-monto', montoFmt || '0'); // tu HTML ya pone $ y M.N.
  setText('res-plazo', plazo || '0');
  setText('res-frecuencia', frecuencia || 'N/A');

  // 4) DATOS PERSONALES
if (d.datos_personales) {
  const dp = d.datos_personales;

  console.log('dp completo:', dp);
  console.log('cp posible:', dp.codigo_postal, dp.cp, dp.cod_postal);

  setText('res-nombres', dp.nombres);
  setText('res-apellido-paterno', dp.apellido_paterno);
  setText('res-apellido-materno', dp.apellido_materno);
  setText('res-fecha-nacimiento', dp.fecha_nacimiento);
  setText('res-genero', dp.genero);
  setText('res-estado-nacimiento', dp.estado_nacimiento);
  setText('res-dependientes', dp.dependientes);
  setText('res-nacionalidad', dp.nacionalidad);
  setText('res-pais-nacimiento', dp.pais_nacimiento);
  setText('res-fiel', dp.fiel);
  setText('res-rfc', dp.rfc);
  setText('res-curp', dp.curp);
  setText('res-estado-civil', dp.estado_civil);
  setText('res-tiempo-estado-civil', dp.tiempo_estado_civil);

  // *** escolaridad ***
  const nivel = normalizaEscolaridad(getNivelEstudios(dp));
  setText('res-escolaridad', nivel);

  setText('res-profesion', dp.profesion);
  setText('res-direccion', dp.direccion);
  setText('res-entre-calles', dp.entre_calles);
  setText('res-colonia', dp.colonia);

  // 👉 CP: probamos varios nombres de campo
  const cp =
    dp.codigo_postal ||
    dp.cp ||
    dp.cod_postal ||
    dp.codigoPostal ||
    '';
  setText('res-cp', cp);

  setText('res-municipio', dp.municipio);
  setText('res-estado', dp.estado);
  setText('res-pais', dp.pais);
  setText('res-tiempo-domicilio', dp.tiempo_domicilio);
  setText('res-telefono', dp.telefono);
  setText('res-celular', dp.celular);
  setText('res-correo', dp.correo);
  setText('res-mejor-hora', dp.mejor_hora || dp.horario_contacto);
}


  // 5) INFORMACIÓN LABORAL
  if (d.info_laboral) {
    const lab = d.info_laboral;
    setText('res-puesto', lab.puesto);
    setText('res-empresa', lab.empresa);
    setText('res-giro-empresa', lab.giro_empresa);
    setText('res-direccion-trabajo', lab.direccion_trabajo);
    setText('res-calles-trabajo', lab.calles_trabajo);
    setText('ref_empresa_trabajo', lab.referencia_trabajo);
    setText('res-colonia-trabajo', lab.colonia_trabajo);
    setText('res-municipio-trabajo', lab.municipio_trabajo);
    setText('res-estado-trabajo', lab.estado_trabajo);
    setText('res-pais-trabajo', lab.pais_trabajo);
    setText('res-tiempo-empleo', lab.tiempo_empleo);
    setText('res-telefono-trabajo', lab.telefono_trabajo);
    setText('res-horario-trabajo', lab.horario_trabajo);
    setText('res-sueldo', lab.sueldo);
    setText('res-forma-pago', lab.forma_pago);
    setText('res-otros-ingresos', lab.otros_ingresos);
    setText('res-fuente-ingresos', lab.fuente_ingresos);
    setText('res-ubicacion-negocio', lab.ubicacion_negocio);
  }

  // 6) INFORMACIÓN ADICIONAL
  if (d.info_adicional) {
    const ad = d.info_adicional;
    setText('res-tipo-vivienda', ad.tipo_vivienda);
    setText('res-pago-casa', ad.pago_casa);
    setText('res-pago-servicios', ad.pago_servicios);
    setText('res-pago-otros', ad.pago_otros);
    setText('res-gasto-mensual', ad.gasto_mensual);
    setText('res-valor-casa', ad.valor_casa);
    setText('res-saldo-hipoteca', ad.saldo_hipoteca);
    setText('res-empresa-hipoteca', ad.empresa_hipoteca);
    setText('res-nombre-propietario', ad.nombre_propietario);
    setText('res-parentesco-propietario', ad.parentesco_propietario);
    setText('res-telefono-propietario', ad.telefono_propietario);
    setText('res-posee-auto', ad.posee_auto);
    setText('res-auto-detalle', ad.marca_auto);
    setText('res-auto-valor', ad.valor_auto);
    setText('res-auto-empresa', ad.empresa_auto);
    setText('res-auto-mensualidad', ad.mensualidad_auto);
  }

  // 7) REFERENCIAS
  if (d.referencias) {
    const fam = d.referencias.filter(r => r.tipo === 'Familiar');
    const per = d.referencias.filter(r => r.tipo === 'Personal');
    if (fam[0]) { setText('ref_fam_nombre', fam[0].nombre_completo); setText('ref_fam_direccion', fam[0].direccion); setText('ref_fam_telefono', fam[0].telefono); setText('ref_fam_celular', fam[0].celular); setText('ref_fam_parentesco', fam[0].parentesco); }
    if (fam[1]) { setText('ref_fam_nombre_2', fam[1].nombre_completo); setText('ref_fam_direccion_2', fam[1].direccion); setText('ref_fam_telefono_2', fam[1].telefono); setText('ref_fam_celular_2', fam[1].celular); setText('ref_fam_parentesco_2', fam[1].parentesco); }
    if (per[0]) { setText('ref_per_nombre', per[0].nombre_completo); setText('ref_per_direccion', per[0].direccion); setText('ref_per_telefono', per[0].telefono); setText('ref_per_celular', per[0].celular); setText('ref_per_parentesco', per[0].parentesco); }
    if (per[1]) { setText('ref_per_nombre_2', per[1].nombre_completo); setText('ref_per_direccion_2', per[1].direccion); setText('ref_per_telefono_2', per[1].telefono); setText('ref_per_celular_2', per[1].celular); setText('ref_per_parentesco_2', per[1].parentesco); }
  }

  // 8) CO-DEUDOR
  if (d.codeudores && d.codeudores.length > 0) {
    const co = d.codeudores[0];
    setText('co_deudor_parentesco', co.parentesco);
    setText('co_nombre', co.nombre);
    setText('co_apellido_paterno', co.apellido_paterno);
    setText('co_apellido_materno', co.apellido_materno);
    setText('co_dependientes', co.dependientes);
    setText('co_genero', co.genero);
    setText('co_nacimiento', co.fecha_nacimiento);
    setText('co_entidad', co.entidad_federativa);
    setText('co_nacionalidad', co.nacionalidad);
    setText('co_pais_nacimiento', co.pais_nacimiento);
    setText('co_rfc', co.rfc);
    setText('co_curp', co.curp);
    setText('co_direccion', co.direccion_actual);
    setText('co_entre_calles', co.entre_calles);
    setText('co_colonia', co.colonia);
    setText('co_cp', co.codigo_postal);
    setText('co_municipio', co.municipio);
    setText('co_estado', co.estado);
    setText('co_pais', co.pais);
    setText('co_tiempo', co.tiempo_domicilio);
    setText('co_tel', co.telefono);
    setText('co_cel', co.celular);
    setText('co_mejor_hora', co.horario_contacto);
    setText('co_correo', co.correo);
  } else {
    console.warn("⚠️ No se encontró co-deudor en los datos.");
  }

  // 9) FIRMAS DEL SOLICITANTE (declaración)
  if (d.firma_declaracion) {
    const f = d.firma_declaracion;
    if (f.firma_formulario)  cargarFirmaCanvas('firmaCanvasVista1', f.firma_formulario);
    if (f.firma_autorizacion) cargarFirmaCanvas('firmaCanvasVista2', f.firma_autorizacion);
    setText('res-funcion-publica',  f.funcion_publica || 'No');
    setText('res-relacion-publica', f.relacion_funcion_publica || 'No');
    setText('res-lugar', (f.lugar || 'N/A'));
    setText('res-fecha', formatearYYYYMMDDaLargoMX(f.fecha) || 'N/A');
  } else {
    setText('res-lugar','N/A'); setText('res-fecha','N/A');
  }

  // 10) FIRMAS FUNCIONARIO
  if (d.funcionarios_firma) {
    const ff = d.funcionarios_firma;
    setText('res-funcion-publica-2',  ff.desempenia_funcion_publica || 'No');
    setText('res-relacion-publica-2', ff.relacion_funcion_publica || 'No');
    if (ff.firma)            cargarFirmaCanvas('firmaVistaSolicitante',  ff.firma);
    if (ff.firma_formulario) cargarFirmaCanvas('firmaVistaAutorizacion', ff.firma_formulario);
    const lugarFinal = (ff.lugar || document.getElementById('lugar_funcionario')?.value || '');
    const fechaFinal = (ff.fecha_firma || ff.fecha || document.getElementById('fecha_funcionario')?.value || '');
    setText('lugar-funcionario', lugarFinal);
    setText('fecha-funcionario', formatearYYYYMMDDaLargoMX(fechaFinal));
  }
});

function imprimirFormato() {
  window.print();
}
function descargarFormato() {
  window.print();
}

/* Refuerzo extra (opcional) */
const bImp = document.getElementById('btnImprimir');
const bPdf = document.getElementById('btnDescargarPDF');
window.addEventListener('beforeprint', () => {
  if (bImp) bImp.style.display = 'none';
  if (bPdf) bPdf.style.display = 'none';
});
window.addEventListener('afterprint', () => {
  if (bImp) bImp.style.display = 'inline-block';
  if (bPdf) bPdf.style.display = 'inline-block';
});