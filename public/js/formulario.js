// ====== NAVEGACIÓN DE PASOS (GLOBAL, ÚNICA) ======
let currentStep = 0;
let steps = [];


function showStep(n) {
  if (!steps.length) return;
  steps.forEach((step, idx) => step.classList.toggle('active', idx === n));
}
window.showStep = showStep;

function nextPrev(n) {
  currentStep += n;
  if (currentStep < 0) currentStep = 0;
  if (!steps.length) return;
  if (currentStep >= steps.length) { alert("¡Formulario completado!"); return; }
  showStep(currentStep);
}
window.nextPrev = nextPrev;

document.addEventListener('DOMContentLoaded', () => {
  steps = Array.from(document.querySelectorAll('.step'));
  showStep(currentStep);
});

function generarFormato() {
  
  // Guardar folio desde la URL si viene como parámetro
const params = new URLSearchParams(window.location.search);
const folioDesdeURL = params.get('folio');

if (folioDesdeURL && !sessionStorage.getItem('solicitud_id')) {
  sessionStorage.setItem('solicitud_id', folioDesdeURL);
}


// Obtener el folio desde sessionStorage o variable global
const solicitudId = sessionStorage.getItem('solicitud_id') || solicitudIdGlobal;
const folioElemento = document.getElementById('res-folio');

if (solicitudId && folioElemento) {
  fetch('/app/controllers/obtener_datos/obtener_folio.php?solicitud_id=' + solicitudId)
    .then(res => res.json())
    .then(data => {
      folioElemento.textContent = data.success ? data.folio : 'Sin folio';
    })
    .catch(() => {
      folioElemento.textContent = 'Error';
    });
}


  document.getElementById('res-atendio').textContent = document.getElementById('atendio').value;
  document.getElementById('res-medio').textContent = document.getElementById('medio').value;
  document.getElementById('res-monto').textContent = Number(document.getElementById('monto').value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  });
document.getElementById('res-plazo').textContent = document.getElementById('plazo').value;

const tasaMensual = document.getElementById('tasa_mensual')?.value || '10.50';
const resTasa = document.getElementById('res-tasa-mensual');

if (resTasa) {
  resTasa.textContent = `${Number(tasaMensual || 0).toFixed(2)}%`;
}

document.getElementById('res-frecuencia').textContent = document.getElementById('frecuencia').value;

  // Datos del solicitante
  document.getElementById('res-nombres').textContent = document.getElementById('nombres').value;
  document.getElementById('res-apellido-paterno').textContent = document.getElementById('apellido_paterno').value;
  document.getElementById('res-apellido-materno').textContent = document.getElementById('apellido_materno').value;
  document.getElementById('res-correo').textContent = document.getElementById('correo').value;
  document.getElementById('res-genero').textContent = document.getElementById('genero').value;
  document.getElementById('res-fecha-nacimiento').textContent = document.getElementById('fecha_nacimiento').value;
  document.getElementById('res-estado-nacimiento').textContent = document.getElementById('estado_nacimiento').value;
  document.getElementById('res-dependientes').textContent = document.getElementById('dependientes').value;
  document.getElementById('res-nacionalidad').textContent = document.getElementById('nacionalidad').value;
  document.getElementById('res-pais-nacimiento').textContent = document.getElementById('pais_nacimiento').value;
  document.getElementById('res-fiel').textContent = document.getElementById('fiel').value;
  document.getElementById('res-rfc').textContent = document.getElementById('rfc').value;
  document.getElementById('res-curp').textContent = document.getElementById('curp').value;
  document.getElementById('res-estado-civil').textContent = document.getElementById('estado_civil').value;
  document.getElementById('res-tiempo-estado-civil').textContent = document.getElementById('tiempo_estado_civil').value;
  document.getElementById('res-escolaridad').textContent = document.getElementById('escolaridad').value;
  document.getElementById('res-profesion').textContent = document.getElementById('profesion').value;
  document.getElementById('res-direccion').textContent = document.getElementById('direccion').value;
  document.getElementById('res-entre-calles').textContent = document.getElementById('entre_calles').value;
  document.getElementById('res-colonia').textContent = document.getElementById('colonia').value;
  document.getElementById('res-cp').textContent = document.getElementById('cp').value;
  document.getElementById('res-municipio').textContent = document.getElementById('municipio').value;
  document.getElementById('res-estado').textContent = document.getElementById('estado').value;
  document.getElementById('res-pais').textContent = document.getElementById('pais').value;
  document.getElementById('res-tiempo-domicilio').textContent = document.getElementById('tiempo_domicilio').value;
  document.getElementById('res-telefono').textContent = document.getElementById('telefono').value;
  document.getElementById('res-celular').textContent = document.getElementById('celular').value;
  document.getElementById('res-mejor-hora').textContent = document.getElementById('mejor_hora').value;

  // Datos laborales
  document.getElementById('res-puesto').textContent = document.getElementById('puesto').value;
  document.getElementById('res-empresa').textContent = document.getElementById('empresa').value;
  document.getElementById('res-giro-empresa').textContent = document.getElementById('giro_empresa').value;
  document.getElementById('res-direccion-trabajo').textContent = document.getElementById('direccion_trabajo').value;
  document.getElementById('res-calles-trabajo').textContent = document.getElementById('calles_trabajo').value;
  document.getElementById('res-colonia-trabajo').textContent = document.getElementById('colonia_trabajo').value;
  document.getElementById('res-municipio-trabajo').textContent = document.getElementById('municipio_trabajo').value;
  document.getElementById('res-estado-trabajo').textContent = document.getElementById('estado_trabajo').value;
  document.getElementById('res-pais-trabajo').textContent = document.getElementById('pais_trabajo').value;
  document.getElementById('res-tiempo-empleo').textContent = document.getElementById('tiempo_empleo').value;
  document.getElementById('res-telefono-trabajo').textContent = document.getElementById('telefono_trabajo').value;
  document.getElementById('res-horario-trabajo').textContent = document.getElementById('horario_trabajo').value;
  document.getElementById('res-sueldo').textContent = document.getElementById('sueldo').value;
  document.getElementById('res-forma-pago').textContent = document.getElementById('forma_pago').value;
  document.getElementById('res-otros-ingresos').textContent = document.getElementById('otros_ingresos').value;
  document.getElementById('res-fuente-ingresos').textContent = document.getElementById('fuente_ingresos').value;
  document.getElementById('res-ubicacion-negocio').textContent = document.getElementById('ubicacion_negocio').value;
  // **** NUEVA LÍNEA AGREGADA PARA EL CAMPO DE REFERENCIA ****
document.getElementById('ref_empresa_trabajo').textContent = document.getElementById('ref_empresa_trabajo_input').value;

// Y la línea original que tenías para 'res-ubicacion-negocio' si aún la necesitas y proviene de un select
document.getElementById('res-ubicacion-negocio').textContent = document.getElementById('ubicacion_negocio').value;


// Funciones públicas
const funcionPublica = document.querySelector('input[name="funcion_publica"]:checked');
const relacionPublica = document.querySelector('input[name="relacion_funcion_publica"]:checked');
document.getElementById('res-funcion-publica').textContent = funcionPublica ? funcionPublica.value : '';
document.getElementById('res-relacion-publica').textContent = relacionPublica ? relacionPublica.value : '';

// Mostrar el tipo de vivienda seleccionado
const tipoVivienda = document.querySelector('input[name="tipo_vivienda"]:checked');
document.getElementById('res-tipo-vivienda').textContent = tipoVivienda ? tipoVivienda.value : '';
document.getElementById('res-pago-casa').textContent = document.getElementById('pago_casa')?.value || '';
document.getElementById('res-pago-servicios').textContent = document.getElementById('pago_servicios')?.value || '';
document.getElementById('res-pago-otros').textContent = document.getElementById('pago_otros')?.value || '';
document.getElementById('res-gasto-mensual').textContent = document.getElementById('gasto_mensual')?.value || '';

// Condicional según tipo de vivienda
const tipo = tipoVivienda?.value;

if (tipo === 'Propia') {
  document.getElementById('res-valor-casa').textContent = document.getElementById('valor_casa')?.value.trim() || 'N/A';
  document.getElementById('res-saldo-hipoteca').textContent = 'N/A';
  document.getElementById('res-empresa-hipoteca').textContent = 'N/A';
  document.getElementById('res-nombre-propietario').textContent = 'N/A';
  document.getElementById('res-parentesco').textContent = 'N/A';
  document.getElementById('res-telefono-propietario').textContent = 'N/A';
} else if (tipo === 'Hipotecada') {
  document.getElementById('res-valor-casa').textContent = 'N/A';
  document.getElementById('res-saldo-hipoteca').textContent = document.getElementById('saldo_hipoteca')?.value.trim() || 'N/A';
  document.getElementById('res-empresa-hipoteca').textContent = document.getElementById('empresa_hipoteca')?.value.trim() || 'N/A';
  document.getElementById('res-nombre-propietario').textContent = 'N/A';
  document.getElementById('res-parentesco').textContent = 'N/A';
  document.getElementById('res-telefono-propietario').textContent = 'N/A';
} else if (['Renta', 'Familiar', 'Huésped'].includes(tipo)) {
  document.getElementById('res-valor-casa').textContent = 'N/A';
  document.getElementById('res-saldo-hipoteca').textContent = 'N/A';
  document.getElementById('res-empresa-hipoteca').textContent = 'N/A';

  document.getElementById('res-nombre-propietario').textContent = document.getElementById('nombre_propietario')?.value.trim() || 'N/A';
  document.getElementById('res-telefono-propietario').textContent = document.getElementById('telefono_propietario')?.value.trim() || 'N/A';

  if (tipo === 'Familiar') {
    document.getElementById('res-parentesco').textContent = document.getElementById('parentesco')?.value.trim() || 'N/A';
  } else {
    document.getElementById('res-parentesco').textContent = 'N/A';
  }
} else {
  // Tipo desconocido: poner todo en N/A
  document.getElementById('res-valor-casa').textContent = 'N/A';
  document.getElementById('res-saldo-hipoteca').textContent = 'N/A';
  document.getElementById('res-empresa-hipoteca').textContent = 'N/A';
  document.getElementById('res-nombre-propietario').textContent = 'N/A';
  document.getElementById('res-parentesco').textContent = 'N/A';
  document.getElementById('res-telefono-propietario').textContent = 'N/A';
}



// Auto
function actualizarResumenAuto() {
  const poseeAuto = document.querySelector('input[name="posee_auto"]:checked');
  document.getElementById('res-posee-auto').textContent = poseeAuto ? poseeAuto.value : '';

  document.getElementById('res-auto-detalle').textContent = document.getElementById('marca_auto')?.value || '';
  document.getElementById('res-auto-valor').textContent = document.getElementById('valor_auto')?.value || '';
  document.getElementById('res-auto-empresa').textContent = document.getElementById('empresa_auto')?.value || '';
  document.getElementById('res-auto-mensualidad').textContent = document.getElementById('mensualidad_auto')?.value || '';
}

// ✅ Llamar una vez para inicializar (por ejemplo al cargar paso 4)
actualizarResumenAuto();

document.addEventListener('DOMContentLoaded', () => {
  // 1. Escuchar cambios en radio buttons de "posee_auto"
  document.querySelectorAll('input[name="posee_auto"]').forEach(radio => {
    radio.addEventListener('change', actualizarResumenAuto);
  });

  // 2. Escuchar cambios en campos del auto
  ['marca_auto', 'valor_auto', 'empresa_auto', 'mensualidad_auto'].forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.addEventListener('input', actualizarResumenAuto);
    }
  });
});


  // Mostrar formato final
  document.getElementById('formulario').classList.add('hidden');
  document.getElementById('formatoFinal').classList.remove('hidden');
  // Fecha actual
const hoy = new Date();
const fechaFormateada = hoy.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
document.getElementById('fecha-consulta').textContent = fechaFormateada;

// Referencia familiar
document.getElementById('ref_fam_nombre').textContent = document.getElementById('form_ref_fam_nombre').value;
document.getElementById('ref_fam_direccion').textContent = document.getElementById('form_ref_fam_direccion').value;
document.getElementById('ref_fam_telefono').textContent = document.getElementById('form_ref_fam_telefono').value;
document.getElementById('ref_fam_celular').textContent = document.getElementById('form_ref_fam_celular').value;
document.getElementById('ref_fam_parentesco').textContent = document.getElementById('form_ref_fam_parentesco').value;


// Referencia familiar 2
document.getElementById('ref_fam_nombre_2').textContent = document.getElementById('form_ref_fam_nombre_2').value;
document.getElementById('ref_fam_direccion_2').textContent = document.getElementById('form_ref_fam_direccion_2').value;
document.getElementById('ref_fam_telefono_2').textContent = document.getElementById('form_ref_fam_telefono_2').value;
document.getElementById('ref_fam_celular_2').textContent = document.getElementById('form_ref_fam_celular_2').value;
document.getElementById('ref_fam_parentesco_2').textContent = document.getElementById('form_ref_fam_parentesco_2').value;


// Folio de consulta
const folioConsulta = document.getElementById('folio_consulta')?.value || '';
document.getElementById('res-folio-consulta').textContent = folioConsulta;

// REFERENCIAS PERSONALES DEL SOLICITANTE
document.getElementById('ref_per_nombre').textContent = document.getElementById('form_ref_per_nombre').value;
document.getElementById('ref_per_direccion').textContent = document.getElementById('form_ref_per_direccion').value;
document.getElementById('ref_per_telefono').textContent = document.getElementById('form_ref_per_telefono').value;
document.getElementById('ref_per_celular').textContent = document.getElementById('form_ref_per_celular').value;
document.getElementById('ref_per_parentesco').textContent = document.getElementById('form_ref_per_parentesco').value;


  // JavaScript PARA LA NUEVA SECCIÓN INDEPENDIENTE
const funcionPublica2 = document.querySelector('input[name="form_funcion_publica"]:checked');
const relacionPublica2 = document.querySelector('input[name="form_relacion_publica"]:checked');
document.getElementById('res-funcion-publica-2').textContent = funcionPublica2 ? funcionPublica2.value : '';
document.getElementById('res-relacion-publica-2').textContent = relacionPublica2 ? relacionPublica2.value : '';

document.getElementById('co_nombre').textContent = document.getElementById('form_co_nombre').value;
document.getElementById('co_deudor_parentesco').textContent = document.getElementById('form_co_parentesco').value;
document.getElementById('co_apellido_paterno').textContent = document.getElementById('form_co_apellido_paterno').value;
document.getElementById('co_apellido_materno').textContent = document.getElementById('form_co_apellido_materno').value;
document.getElementById('co_correo').textContent = document.getElementById('form_co_correo').value;
document.getElementById('co_genero').textContent = document.getElementById('form_co_genero').value;
document.getElementById('co_nacimiento').textContent = document.getElementById('form_co_nacimiento').value;
document.getElementById('co_entidad').textContent = document.getElementById('form_co_entidad').value;
document.getElementById('co_dependientes').textContent = document.getElementById('form_co_dependientes').value;
document.getElementById('co_nacionalidad').textContent = document.getElementById('form_co_nacionalidad').value;
document.getElementById('co_pais_nacimiento').textContent = document.getElementById('form_co_pais_nacimiento').value;
document.getElementById('co_rfc').textContent = document.getElementById('form_co_rfc').value;
document.getElementById('co_curp').textContent = document.getElementById('form_co_curp').value;
document.getElementById('co_direccion').textContent = document.getElementById('form_co_direccion').value;
document.getElementById('co_entre_calles').textContent = document.getElementById('form_co_entre_calles').value;
document.getElementById('co_colonia').textContent = document.getElementById('form_co_colonia').value;
document.getElementById('co_cp').textContent = document.getElementById('form_co_cp').value;
document.getElementById('co_municipio').textContent = document.getElementById('form_co_municipio').value;
document.getElementById('co_estado').textContent = document.getElementById('form_co_estado').value;
document.getElementById('co_pais').textContent = document.getElementById('form_co_pais').value;
document.getElementById('co_tiempo').textContent = document.getElementById('form_co_tiempo').value;
document.getElementById('co_tel').textContent = document.getElementById('form_co_tel').value;
document.getElementById('co_cel').textContent = document.getElementById('form_co_cel').value;
document.getElementById('co_mejor_hora').textContent = document.getElementById('form_co_mejor_hora').value;


copiarFirmasParaFormato();
// Toma el tamaño REAL del destino (lo que mide en pantalla)
function _getBoxSize(el, fw=350, fh=200){
  const r = el.getBoundingClientRect();
  let w = Math.round(r.width), h = Math.round(r.height);
  if (!w || !h) { // fallback si aún no tiene layout
    w = parseInt(getComputedStyle(el).width)  || fw;
    h = parseInt(getComputedStyle(el).height) || fh;
  }
  return { w, h };
}

function pintarEnDestino(destId, dataUrl, padding=8){
  const el = document.getElementById(destId);
  if (!el) return;

  if (el.tagName === "IMG") { // <img>
    el.src = dataUrl || "";
    el.style.width = "100%";
    el.style.height = "100%";
    el.style.objectFit = "contain";
    el.style.background = "#fff";
    return;
  }

  // <canvas>
  const canvas = /** @type {HTMLCanvasElement} */ (el);
  const { w, h } = _getBoxSize(canvas);
  const dpr = Math.max(window.devicePixelRatio || 1, 1);

  canvas.style.width = w+"px";  canvas.style.height = h+"px";
  canvas.width = Math.round(w*dpr); canvas.height = Math.round(h*dpr);

  const ctx = canvas.getContext("2d");
  ctx.setTransform(1,0,0,1,0,0);
  ctx.clearRect(0,0,canvas.width,canvas.height);
  ctx.fillStyle = "#fff"; ctx.fillRect(0,0,canvas.width,canvas.height);

  if (!dataUrl) return;

  const src = dataUrl.startsWith("data:image") ? dataUrl : ("data:image/png;base64,"+dataUrl);
  const img = new Image();
  img.onload = () => {
    const natW = img.naturalWidth || img.width;
    const natH = img.naturalHeight || img.height;
    const availW = Math.max(1, w - 2*padding);
    const availH = Math.max(1, h - 2*padding);
    const scale  = Math.min(availW/natW, availH/natH);
    const drawWcss = Math.round(natW*scale), drawHcss = Math.round(natH*scale);
    const dx = Math.round((w-drawWcss)/2)*dpr, dy = Math.round((h-drawHcss)/2)*dpr;
    ctx.imageSmoothingEnabled = false;
    ctx.drawImage(img, dx, dy, drawWcss*dpr, drawHcss*dpr);
  };
  img.src = src;
}

// ---- Exporta la firma del canvas (recorta trazo y la centra a 350x200 con fondo blanco) ----
function exportarFirmaPNG(canvas, w=350, h=200, padding=10, bg="#fff", threshold=240){
  if (!canvas) return "";
  const ctx = canvas.getContext("2d");
  const W = canvas.width, H = canvas.height;
  const dpr = (canvas._meta && canvas._meta.dpr) || Math.max(window.devicePixelRatio||1,1);
  const { data } = ctx.getImageData(0,0,W,H);

  let minX=W, minY=H, maxX=0, maxY=0, found=false;
  for (let y=0;y<H;y++) for (let x=0;x<W;x++){
    const i=(y*W+x)*4, r=data[i], g=data[i+1], b=data[i+2];
    if (r<threshold||g<threshold||b<threshold){found=true;
      if(x<minX)minX=x; if(y<minY)minY=y; if(x>maxX)maxX=x; if(y>maxY)maxY=y;}
  }

  const out=document.createElement("canvas"); out.width=w; out.height=h;
  const octx=out.getContext("2d"); octx.fillStyle=bg; octx.fillRect(0,0,w,h);
  if(!found) return out.toDataURL("image/png");

  const bw=Math.max(1,maxX-minX+1), bh=Math.max(1,maxY-minY+1);
  const availW=Math.max(1,w-2*padding), availH=Math.max(1,h-2*padding);
  const scale=Math.min(availW/(bw/dpr), availH/(bh/dpr));
  const drawW=Math.max(1,Math.round((bw/dpr)*scale));
  const drawH=Math.max(1,Math.round((bh/dpr)*scale));
  const dx=Math.round((w-drawW)/2), dy=Math.round((h-drawH)/2);

  octx.imageSmoothingEnabled=false;
  octx.drawImage(canvas, minX, minY, bw, bh, dx, dy, drawW, drawH);
  return out.toDataURL("image/png");
}

// ---- Copia las 4 firmas a los recuadros del formato (usa el tamaño real del recuadro) ----
function copiarFirmasParaFormato(){
  const pairs = [
    ["firmaAutorizacion", "firmaVistaAutorizacion"], // autorización → vista
    ["firmaFormulario",   "firmaVistaSolicitante"  ], // solicitante → vista
    ["firmaCanvas1",      "firmaCanvasVista1"     ], // autorización (declaración)
    ["firmaCanvas2",      "firmaCanvasVista2"     ], // solicitante (declaración)
  ];
  pairs.forEach(([srcId, dstId]) => {
    const c = document.getElementById(srcId);
    const b64 = c ? exportarFirmaPNG(c) : "";
    pintarEnDestino(dstId, b64);
  });
}

}

let solicitudIdGlobal = null;





(function () {
  const { origin, pathname } = location;
  const i = pathname.indexOf('/public/');
  const root = (i !== -1) ? pathname.slice(0, i) : ''; // p.ej. "/Sempiternal_V1" o "" en producción

  // /public/ para assets estáticos
  window.BASE_PUBLIC = `${origin}${root}/public/`;

  // /app/ para PHP controladores
  window.BASE_APP    = `${origin}${root}/app/`;

  // /app/controllers/ y /app/controllers/obtener_datos/
  window.BASE_API    = `${BASE_APP}controllers/`;
  window.API_OBTENER = `${BASE_API}obtener_datos/`;

  console.log('[BASES]', { BASE_PUBLIC, BASE_APP, BASE_API, API_OBTENER });
})();

// Join seguro sin dobles slashes
function joinUrl(a, b='') {
  const A = (a || '').replace(/\/+$/,'');
  const B = (b || '').replace(/^\/+/,'');
  return `${A}/${B}`;
}

// --- Normaliza una ruta a URL final ---
function resolveApiUrl(path) {
  if (!path) throw new Error('resolveApiUrl: path vacío');

  // Absolutas http(s): no tocar
  if (/^https?:\/\//i.test(path)) return path;

  // Ya viene completa con alguna base → dejar
  if (path.startsWith(BASE_PUBLIC) ||
      path.startsWith(BASE_APP)    ||
      path.startsWith(BASE_API)    ||
      path.startsWith(API_OBTENER)) return path;

  // Controladores fuera de "obtener_datos"
  if (path.includes('cp_buscar.php') || path.includes('ver_firma.php')) {
    path = path.replace(/^\/+/, '').replace(/^app\/controllers\//, '');
    return joinUrl(BASE_API, path); // ✅ usar BASE_API
  }

  // Rutas relativas hacia /app/controllers/
  if (path.startsWith('/app/controllers/'))
    return joinUrl(BASE_API, path.replace(/^\/?app\/controllers\//, '')); // ✅
  if (path.startsWith('app/controllers/'))
    return joinUrl(BASE_API, path.replace(/^app\/controllers\//, ''));    // ✅

  // Rutas hacia /app/controllers/obtener_datos/
  if (path.startsWith('/obtener_datos/'))
    return joinUrl(BASE_API, path.slice(1));
  if (path.startsWith('obtener_datos/'))
    return joinUrl(BASE_API, path);

  // Por defecto, mandar a obtener_datos
  return joinUrl(API_OBTENER, path);
}


// --- Monkey patch: FETCH ---
(() => {
  const _fetch = window.fetch;
  window.fetch = function(input, init) {
    let url = (typeof input === 'string') ? input : input.url;
    const fixed = resolveApiUrl(url);
    if (fixed !== url) {
      console.info('[fetch rewrite]', url, '→', fixed);
      input = (typeof input === 'string') ? fixed : new Request(fixed, input);
    }
    return _fetch(input, init);
  };
})();

// --- Monkey patch: XHR (opcional) ---
(() => {
  const _open = XMLHttpRequest.prototype.open;
  XMLHttpRequest.prototype.open = function(method, url, async, user, pass) {
    const fixed = resolveApiUrl(url);
    if (fixed !== url) console.info('[xhr rewrite]', url, '→', fixed);
    return _open.call(this, method, fixed, async !== false, user, pass);
  };
})();


async function guardarPaso1() {
  // 1) leer campos
  const atendio       = document.getElementById('atendio')?.value.trim() || '';
  const medio         = document.getElementById('medio')?.value || '';
  const monto         = document.getElementById('monto')?.value || '';
  const plazo         = document.getElementById('plazo')?.value || '';
  const tasa_mensual  = document.getElementById('tasa_mensual')?.value || '10.5';
  const frecuencia    = document.getElementById('frecuencia')?.value || '';

  const modalidadEl = document.getElementById('contrato_modalidad');
  const modalidad   = modalidadEl ? (modalidadEl.value || '') : '';

  // Validación mínima
  if (!atendio || !medio || !monto || !plazo || !tasa_mensual || !frecuencia) {
    Swal.fire({
      icon: 'warning',
      title: 'Faltan datos',
      text: 'Completa todos los campos del paso 1.'
    });
    return;
  }

  const tasaNum = Number(tasa_mensual);

  if (!Number.isFinite(tasaNum) || tasaNum < 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Tasa inválida',
      text: 'La tasa mensual debe ser un número válido.'
    });
    return;
  }

  // ✅ Modalidades permitidas
  const MODALIDADES_VALIDAS = new Set(['P10', 'SEM_P10', 'P10_ORD', 'P40', 'P40_ORD']);

  if (!modalidad) {
    Swal.fire({
      icon: 'warning',
      title: 'Falta modalidad',
      text: 'Selecciona una modalidad.'
    });
    return;
  }

  if (!MODALIDADES_VALIDAS.has(modalidad)) {
    Swal.fire({
      icon: 'error',
      title: 'Modalidad inválida',
      text: 'La modalidad seleccionada no es válida. Vuelve a elegir.'
    });
    return;
  }

  // 2) payload
  const fd = new FormData();
  fd.append('atendio', atendio);
  fd.append('medio', medio);
  fd.append('monto', monto);
  fd.append('plazo', plazo);
  fd.append('tasa_mensual', tasa_mensual);
  fd.append('frecuencia', frecuencia);
  fd.append('contrato_modalidad', modalidad);

  const folioExistente = sessionStorage.getItem('solicitud_id');

  if (folioExistente) {
    fd.append('id', folioExistente);
    fd.append('solicitud_id', folioExistente);
    fd.append('accion', 'update');
  } else {
    fd.append('accion', 'create');
  }

  // 3) prevenir doble click
  const btn = document.activeElement;
  if (btn && btn.disabled !== undefined) btn.disabled = true;

  try {
    const res  = await fetch(`${BASE_API}guardar_solicitud.php`, {
      method: 'POST',
      body: fd
    });

    const data = await res.json().catch(() => ({}));

    const ok    = data.ok === true || data.status === 'ok' || data.success === true;
    const folio = data.solicitud_id || data.folio || data.id;

    if (ok) {
      if (folio && !sessionStorage.getItem('solicitud_id')) {
        sessionStorage.setItem('solicitud_id', String(folio));
        window.solicitudIdGlobal = String(folio);
      } else if (folio) {
        window.solicitudIdGlobal = String(folio);
      }

      await Swal.fire({
        icon: 'success',
        title: 'Guardado correctamente',
        text: data.message || 'La solicitud se guardó con éxito.',
        timer: 1200,
        showConfirmButton: false
      });

      nextPrev(1);
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error al guardar',
        text: data.message || data.error || 'No se pudo guardar la solicitud.'
      });
    }
  } catch (err) {
    console.error('❌ Error al guardar solicitud:', err);

    Swal.fire({
      icon: 'error',
      title: 'Error del servidor',
      text: 'No se pudo conectar con el servidor.'
    });
  } finally {
    if (btn && btn.disabled !== undefined) btn.disabled = false;
  }
}




function guardarPaso2() {
  if (!solicitudIdGlobal) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'Falta el ID de solicitud.'
    });
    return;
  }

  const formData = new FormData();
  formData.append('solicitud_id', solicitudIdGlobal);

  formData.append('nombres', document.getElementById('nombres').value);
  formData.append('apellido_paterno', document.getElementById('apellido_paterno').value);
  formData.append('apellido_materno', document.getElementById('apellido_materno').value);
  formData.append('correo', document.getElementById('correo').value);
  formData.append('genero', document.getElementById('genero').value);
  formData.append('fecha_nacimiento', document.getElementById('fecha_nacimiento').value);
  formData.append('estado_nacimiento', document.getElementById('estado_nacimiento').value);
  formData.append('dependientes', document.getElementById('dependientes').value);
  formData.append('nacionalidad', document.getElementById('nacionalidad').value);
  formData.append('pais_nacimiento', document.getElementById('pais_nacimiento').value);
  formData.append('fiel', document.getElementById('fiel').value);
  formData.append('rfc', document.getElementById('rfc').value);
  formData.append('curp', document.getElementById('curp').value);
  formData.append('estado_civil', document.getElementById('estado_civil').value);
  formData.append('tiempo_estado_civil', document.getElementById('tiempo_estado_civil').value);
  formData.append('escolaridad', document.getElementById('escolaridad').value);
  formData.append('profesion', document.getElementById('profesion').value);
  formData.append('direccion', document.getElementById('direccion').value);
  formData.append('entre_calles', document.getElementById('entre_calles').value);
  formData.append('colonia', document.getElementById('colonia').value);
  formData.append('cp', document.getElementById('cp').value);
  formData.append('municipio', document.getElementById('municipio').value);
  formData.append('estado', document.getElementById('estado').value);
  formData.append('pais', document.getElementById('pais').value);
  formData.append('tiempo_domicilio', document.getElementById('tiempo_domicilio').value);
  formData.append('telefono', document.getElementById('telefono').value);
  formData.append('celular', document.getElementById('celular').value);
  formData.append('mejor_hora', document.getElementById('mejor_hora').value);

  fetch(`${BASE_API}guardar_datos_personales.php`, {
    method: 'POST',
    body: formData
  })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'ok') {
        Swal.fire({
          icon: 'success',
          title: 'Datos personales guardados',
          text: data.message,
          timer: 1500,
          showConfirmButton: false
        });
        nextPrev(1); // avanzar al siguiente paso
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message
        });
      }
    })
    .catch(error => {
      console.error('❌ Error al guardar:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error de red',
        text: 'No se pudo guardar la información. Intenta nuevamente.'
      });
    });
}



function guardarPaso3() {
  if (!solicitudIdGlobal) {
    Swal.fire({
      icon: 'error',
      title: 'Falta el ID de la solicitud',
      text: 'No se puede continuar sin el identificador.'
    });
    return;
  }

  // --- Helpers ---
  const val = id => (document.getElementById(id)?.value ?? '').trim();

  // Convierte "$ 12,500.50" o "12,500,50" -> "12500.50"
  function normalizarDecimal(raw) {
    if (!raw) return '';
    let s = raw.replace(/[^\d.,-]/g, ''); // quita $ espacios etc.
    const lastComma = s.lastIndexOf(',');
    const lastDot   = s.lastIndexOf('.');
    const lastSep   = Math.max(lastComma, lastDot);
    if (lastSep !== -1) {
      const entero = s.slice(0, lastSep).replace(/[.,]/g, '');
      const dec    = s.slice(lastSep + 1).replace(/[.,]/g, '');
      s = entero + '.' + dec;
    } else {
      s = s.replace(/[.,]/g, '');
    }
    return s;
  }

  const isDecimal = s => /^\d+(\.\d{1,2})?$/.test(s);

  // --- Toma y normaliza campos numéricos ---
  const sueldoNorm        = normalizarDecimal(val('sueldo'));
  const otrosIngresosNorm = normalizarDecimal(val('otros_ingresos'));
  const telTrabajoDigits  = val('telefono_trabajo').replace(/\D/g, '');

  // --- Validaciones amigables ---
// --- Validaciones amigables ---
const errores = [];
if (sueldoNorm === '' || !isDecimal(sueldoNorm)) {
  errores.push('• <b>Sueldo</b> debe ser un número válido (puede ser 0). Ej.: 0, 12500, 12500.50');
}
if (otrosIngresosNorm !== '' && !isDecimal(otrosIngresosNorm)) {
  errores.push('• <b>Otros ingresos</b> debe ser número con hasta 2 decimales (puede ser 0).');
}
if (telTrabajoDigits && (telTrabajoDigits.length < 7 || telTrabajoDigits.length > 15)) {
  errores.push('• <b>Teléfono de trabajo</b> debe contener de 7 a 15 dígitos.');
}


  if (errores.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Revisa los campos',
      html: errores.join('<br>'),
      confirmButtonText: 'Corregir'
    });
    return;
  }

  // --- Construcción del FormData (ya limpio) ---
  const formData = new FormData();
  formData.append('solicitud_id', solicitudIdGlobal);

  formData.append('puesto', val('puesto'));
  formData.append('empresa', val('empresa'));
  formData.append('giro_empresa', val('giro_empresa'));
  formData.append('direccion_trabajo', val('direccion_trabajo'));
  formData.append('calles_trabajo', val('calles_trabajo'));
  formData.append('ref_empresa_trabajo_input', val('ref_empresa_trabajo_input'));
  formData.append('colonia_trabajo', val('colonia_trabajo'));
  formData.append('municipio_trabajo', val('municipio_trabajo'));
  formData.append('estado_trabajo', val('estado_trabajo'));
  formData.append('pais_trabajo', val('pais_trabajo'));
  formData.append('tiempo_empleo', val('tiempo_empleo'));
  formData.append('telefono_trabajo', telTrabajoDigits);
  formData.append('horario_trabajo', val('horario_trabajo'));
  formData.append('sueldo', sueldoNorm);                               // ← normalizado
  formData.append('forma_pago', val('forma_pago'));
  formData.append('otros_ingresos', otrosIngresosNorm || '0');          // ← si vacío, 0
  formData.append('fuente_ingresos', val('fuente_ingresos'));
  formData.append('ubicacion_negocio', val('ubicacion_negocio'));

  fetch(`${BASE_API}guardar_info_laboral.php`, {
    method: 'POST',
    body: formData
  })
  .then(r => r.json())
  .then(data => {
    if (data.status === 'ok') {
      Swal.fire({
        icon: 'success',
        title: '¡Guardado!',
        text: data.message || 'Información laboral guardada correctamente.',
        confirmButtonText: 'Continuar'
      }).then(() => nextPrev(1));
    } else {
      Swal.fire({
        icon: 'error',
        title: 'No pudimos guardar',
        html: data.user_msg || data.message || 'Intenta de nuevo más tarde.'
      });
    }
  })
  .catch(err => {
    console.error('Error:', err);
    Swal.fire({
      icon: 'error',
      title: 'Error del servidor',
      text: 'Ocurrió un error al guardar la información laboral.'
    });
  });
}



function guardarPaso4() {
  if (!solicitudIdGlobal) {
    alert("Error: Falta el ID de solicitud");
    return;
  }

  const formData = new FormData();
  formData.append('solicitud_id', solicitudIdGlobal);

  // Función para obtener valor o N/A
  const getValor = (id) => {
    const valor = document.getElementById(id)?.value?.trim();
    return valor === '' ? 'N/A' : valor;
  };

  // Radio button de tipo de vivienda
  const tipoVivienda = document.querySelector('input[name="tipo_vivienda"]:checked');
  formData.append('tipo_vivienda', tipoVivienda ? tipoVivienda.value : 'N/A');

  formData.append('pago_casa', getValor('pago_casa'));
  formData.append('pago_servicios', getValor('pago_servicios'));
  formData.append('pago_otros', getValor('pago_otros'));
  formData.append('gasto_mensual', getValor('gasto_mensual'));
  formData.append('valor_casa', getValor('valor_casa'));
  formData.append('saldo_hipoteca', getValor('saldo_hipoteca'));
  formData.append('empresa_hipoteca', getValor('empresa_hipoteca'));
  formData.append('nombre_propietario', getValor('nombre_propietario'));
  formData.append('parentesco_propietario', getValor('parentesco'));// ✅ ya usa N/A si está vacío
  formData.append('telefono_propietario', getValor('telefono_propietario'));

  // Radio button: ¿Posee auto?
  const poseeAuto = document.querySelector('input[name="posee_auto"]:checked');
  formData.append('posee_auto', poseeAuto ? poseeAuto.value : 'N/A');

  formData.append('marca_auto', getValor('marca_auto'));
  formData.append('valor_auto', getValor('valor_auto'));
  formData.append('empresa_auto', getValor('empresa_auto'));
  formData.append('mensualidad_auto', getValor('mensualidad_auto'));

  fetch(`${BASE_API}guardar_info_adicional.php`, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('✅ ' + data.message);
      nextPrev(1); // Avanza al siguiente paso
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Ocurrió un error al guardar la información adicional.');
  });
}


function guardarPaso5() {
  const solicitud_id = solicitudIdGlobal || sessionStorage.getItem('solicitud_id');

  if (!solicitud_id) {
    Swal.fire({
      icon: 'error',
      title: 'ID faltante',
      text: 'No se encontró el ID de solicitud. Verifica que esté almacenado.'
    });
    return;
  }

  const funcion_publica = document.querySelector('input[name="funcion_publica"]:checked')?.value || '';
  const relacion_funcion_publica = document.querySelector('input[name="relacion_funcion_publica"]:checked')?.value || '';
  const folio_consulta = document.getElementById('folio_consulta').value;
  const lugar = document.getElementById('lugar')?.value || '';
  const fecha = document.getElementById('fecha')?.value || '';

  const firma1 = document.getElementById('firmaCanvas1').toDataURL();
  const firma2 = document.getElementById('firmaCanvas2').toDataURL();

  const formData = new FormData();
  formData.append("solicitud_id", solicitud_id);
  formData.append("funcion_publica", funcion_publica);
  formData.append("relacion_funcion_publica", relacion_funcion_publica);
  formData.append("folio_consulta", folio_consulta);
  formData.append("firma_base64_1", firma1);
  formData.append("firma_base64_2", firma2);
  formData.append("lugar", lugar);
  formData.append("fecha", fecha);

  fetch(`${BASE_API}guardar_firma_declaracion.php`, {
    method: 'POST',
    body: formData
  })
    .then(response => {
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('application/json')) {
        throw new Error('La respuesta del servidor no es JSON. Verifica que PHP no esté mostrando errores o HTML.');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'ok') {
        Swal.fire({
          icon: 'success',
          title: '¡Guardado!',
          text: data.message || 'Firma guardada correctamente.'
        }).then(() => nextPrev(1));
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data.message || 'No se pudo guardar la firma.'
        });
        console.error(data);
      }
    })
    .catch(error => {
      console.error('Error de red o servidor:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error de red',
        text: error.message
      });
    });
}
// ---- SHIM DE COMPATIBILIDAD ----
// Si en algún script viejo llaman a actualizarLugarYFecha(), lo redirigimos
function actualizarLugarYFecha() {
  try {
    if (document.getElementById('lugar-funcionario') || document.getElementById('fecha-funcionario')) {
      // Paso 8
      if (window.paso8?.actualizarLugarYFecha) window.paso8.actualizarLugarYFecha();
    }
    if (document.getElementById('res-lugar') || document.getElementById('res-fecha')) {
      // Paso 5
      if (window.paso5?.actualizarLugarYFecha) window.paso5.actualizarLugarYFecha();
    }
  } catch (e) { console.warn('actualizarLugarYFecha shim:', e); }
}



function guardarPaso6() {
  const solicitud_id = window.solicitudIdGlobal || sessionStorage.getItem('solicitud_id');
  if (!solicitud_id) {
    Swal.fire('Error', 'Falta el ID de solicitud.', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('solicitud_id', solicitud_id);

  // Referencias Familiares
  for (let i = 1; i <= 2; i++) {
    const suf = i === 1 ? '' : `_${i}`;
    formData.append(`form_ref_fam_nombre${suf}`,     document.getElementById(`form_ref_fam_nombre${suf}`)?.value || '');
    formData.append(`form_ref_fam_direccion${suf}`,  document.getElementById(`form_ref_fam_direccion${suf}`)?.value || '');
    formData.append(`form_ref_fam_telefono${suf}`,   document.getElementById(`form_ref_fam_telefono${suf}`)?.value || '');
    formData.append(`form_ref_fam_celular${suf}`,    document.getElementById(`form_ref_fam_celular${suf}`)?.value || '');

    // ✅ NUEVO: correo de referencia familiar (form_ref_fam_correo, form_ref_fam_correo_2)
    formData.append(`form_ref_fam_correo${suf}`,     document.getElementById(`form_ref_fam_correo${suf}`)?.value || '');

    formData.append(`form_ref_fam_parentesco${suf}`, document.getElementById(`form_ref_fam_parentesco${suf}`)?.value || '');
  }

  // Referencias Personales
  for (let i = 1; i <= 2; i++) {
    const suf = i === 1 ? '' : `_${i}`;
    formData.append(`form_ref_per_nombre${suf}`,     document.getElementById(`form_ref_per_nombre${suf}`)?.value || '');
    formData.append(`form_ref_per_direccion${suf}`,  document.getElementById(`form_ref_per_direccion${suf}`)?.value || '');
    formData.append(`form_ref_per_telefono${suf}`,   document.getElementById(`form_ref_per_telefono${suf}`)?.value || '');
    formData.append(`form_ref_per_celular${suf}`,    document.getElementById(`form_ref_per_celular${suf}`)?.value || '');

    // ✅ NUEVO: correo de referencia personal (form_ref_per_correo, form_ref_per_correo_2)
    formData.append(`form_ref_per_correo${suf}`,     document.getElementById(`form_ref_per_correo${suf}`)?.value || '');

    formData.append(`form_ref_per_parentesco${suf}`, document.getElementById(`form_ref_per_parentesco${suf}`)?.value || '');
  }

  // Mostrar SweetAlert de carga
  Swal.fire({
    title: 'Guardando...',
    text: 'Por favor espera un momento',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

  fetch(`${BASE_API}guardar_referencias.php`, {
    method: 'POST',
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error('Respuesta del servidor no válida');
      return response.json();
    })
    .then(data => {
      if (data.status === 'ok') {
        Swal.fire({
          icon: 'success',
          title: '¡Guardado!',
          text: 'Las referencias han sido guardadas correctamente.',
          timer: 1500,
          showConfirmButton: false
        }).then(() => {
          nextPrev(1);
        });
      } else {
        Swal.fire('Error al guardar', data.error || 'Ocurrió un error desconocido.', 'error');
      }
    })
    .catch(error => {
      console.error('Error en la solicitud:', error);
      Swal.fire('Error de red', error.message, 'error');
    });
}



function guardarPaso7() {
  if (!solicitudIdGlobal) {
    Swal.fire('Error', 'Falta el ID de solicitud', 'error');
    return;
  }

  const formData = new FormData();
  formData.append('solicitud_id', solicitudIdGlobal);

  formData.append('form_co_nombre', document.getElementById('form_co_nombre')?.value || '');
  formData.append('form_co_parentesco', document.getElementById('form_co_parentesco')?.value || '');
  formData.append('form_co_apellido_paterno', document.getElementById('form_co_apellido_paterno')?.value || '');
  formData.append('form_co_apellido_materno', document.getElementById('form_co_apellido_materno')?.value || '');
  formData.append('form_co_correo', document.getElementById('form_co_correo')?.value || '');
  formData.append('form_co_genero', document.getElementById('form_co_genero')?.value || '');
  formData.append('form_co_nacimiento', document.getElementById('form_co_nacimiento')?.value || '');
  formData.append('form_co_entidad', document.getElementById('form_co_entidad')?.value || '');
  formData.append('form_co_dependientes', document.getElementById('form_co_dependientes')?.value || '');
  formData.append('form_co_nacionalidad', document.getElementById('form_co_nacionalidad')?.value || '');
  formData.append('form_co_pais_nacimiento', document.getElementById('form_co_pais_nacimiento')?.value || '');
  formData.append('form_co_rfc', document.getElementById('form_co_rfc')?.value || '');
  formData.append('form_co_curp', document.getElementById('form_co_curp')?.value || '');
  formData.append('form_co_direccion', document.getElementById('form_co_direccion')?.value || '');
  formData.append('form_co_entre_calles', document.getElementById('form_co_entre_calles')?.value || '');
  formData.append('form_co_colonia', document.getElementById('form_co_colonia')?.value || '');
  formData.append('form_co_cp', document.getElementById('form_co_cp')?.value || '');
  formData.append('form_co_municipio', document.getElementById('form_co_municipio')?.value || '');
  formData.append('form_co_estado', document.getElementById('form_co_estado')?.value || '');
  formData.append('form_co_pais', document.getElementById('form_co_pais')?.value || '');
  formData.append('form_co_tiempo', document.getElementById('form_co_tiempo')?.value || '');
  formData.append('form_co_tel', document.getElementById('form_co_tel')?.value || '');
  formData.append('form_co_cel', document.getElementById('form_co_cel')?.value || '');
  formData.append('form_co_mejor_hora', document.getElementById('form_co_mejor_hora')?.value || '');
console.log('✅ Género seleccionado:', document.getElementById('form_co_genero')?.value);

  fetch(`${BASE_API}guardar_codeudor.php`, {
    method: 'POST',
    body: formData
  })
    .then(response => {
      if (!response.ok) throw new Error('Error en la respuesta del servidor');
      return response.json();
    })
    .then(data => {
      if (data.status === 'ok') {
        Swal.fire({
          icon: 'success',
          title: 'Guardado',
          text: 'Los datos del co-deudor se guardaron correctamente.',
          timer: 1800,
          showConfirmButton: false
        });
        nextPrev(1);
      } else {
        Swal.fire('Error al guardar', data.error || 'Respuesta desconocida', 'error');
        console.error(data);
      }
    })
    .catch(error => {
      console.error('Error en la solicitud:', error);
      Swal.fire('Error', error.message, 'error');
    });
}

async function guardarPaso8() {
  try {
    const solicitudId = window.solicitudIdGlobal || sessionStorage.getItem('solicitud_id');
    if (!solicitudId) {
      await Swal.fire({ icon: 'error', title: 'Falta el folio', text: 'No encontré el ID de solicitud.' });
      return false;
    }

    // Radios
    const desempenia = document.querySelector('input[name="form_funcion_publica"]:checked')?.value ?? '';
    const relacion   = document.querySelector('input[name="form_relacion_publica"]:checked')?.value ?? '';

    // Firmas
    const canvasAut  = document.getElementById("firmaAutorizacion");
    const canvasForm = document.getElementById("firmaFormulario");
    const firmaAutorizacion = (canvasAut?.width && canvasAut?.height) ? canvasAut.toDataURL("image/png") : "";
    const firmaFormulario   = (canvasForm?.width && canvasForm?.height) ? canvasForm.toDataURL("image/png") : "";

    // === LUGAR/FECHA SOLO DEL PASO 8 ===
    // 1) Intento con tu helper
    let lugar = "", fechaISO = "";
    try {
      const pick = window.paso8?.leer?.();
      lugar    = pick?.lugar ?? "";
      fechaISO = pick?.fechaISO ?? "";
    } catch {}

    // 2) Fallback directo por si el helper no estaba cargado aún / devolvió vacío
    if (!lugar) {
      lugar = (document.getElementById('lugar_funcionario')?.value || "").trim();
    }
    if (!fechaISO) {
      const raw = (document.getElementById('fecha_funcionario')?.value
                || document.getElementById('fecha')?.value || "")
                  .replace(/\u00A0/g, ' ')
                  .trim();
      // ---- toISO robusto ----
      fechaISO = (function toISO(s) {
        if (!s) return "";
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s; // YYYY-MM-DD
        let m = s.match(/^\s*(\d{1,2})\D+(\d{1,2})\D+(\d{4})\s*$/); // DD sep MM sep YYYY
        if (m) return `${m[3]}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`;
        const digits = s.replace(/\D/g,'');
        if (digits.length === 8) return `${digits.slice(4,8)}-${digits.slice(2,4)}-${digits.slice(0,2)}`;
        return "";
      })(raw);
      // log útil
      console.table({ paso: 8, fechaRaw: raw, fechaISO });
    }

    if (!lugar || !fechaISO) {
      await Swal.fire({ icon: 'warning', title: 'Campos faltantes', text: 'Completa Lugar y una Fecha válida.' });
      return false;
    }

    const formData = new FormData();
    formData.append("solicitud_id", solicitudId);
    formData.append("desempenia_funcion_publica", desempenia);
    formData.append("relacion_funcion_publica",   relacion);
    formData.append("lugar",  lugar);
    formData.append("fecha",  fechaISO);           // <-- ISO garantizado
    formData.append("firma_autorizacion",  firmaAutorizacion);
    formData.append("firma_formulario",    firmaFormulario);

    const resp = await fetch(`${BASE_API}guardar_funcionarios_firma.php`, { method: "POST", body: formData });
    const text = await resp.text();

    let json;
    try { json = JSON.parse(text); }
    catch {
      console.error("Respuesta no JSON:", text);
      await Swal.fire("Error", "Respuesta no válida del servidor", "error");
      return false;
    }

    if (json.status !== "ok") {
      await Swal.fire("Error", json.message || "No se pudo guardar.", "error");
      return false;
    }

    console.log("Paso 8 guardado:", json);
    return true;

  } catch (err) {
    console.error("Error al guardar paso 8:", err);
    await Swal.fire("Error","Ocurrió un error al guardar.","error");
    return false;
  }
}


// ---- SHIM DE COMPATIBILIDAD ----
// Si en algún script viejo llaman a actualizarLugarYFecha(), lo redirigimos
function actualizarLugarYFecha() {
  try {
    if (document.getElementById('lugar-funcionario') || document.getElementById('fecha-funcionario')) {
      // Paso 8
      if (window.paso8?.actualizarLugarYFecha) window.paso8.actualizarLugarYFecha();
    }
    if (document.getElementById('res-lugar') || document.getElementById('res-fecha')) {
      // Paso 5
      if (window.paso5?.actualizarLugarYFecha) window.paso5.actualizarLugarYFecha();
    }
  } catch (e) { console.warn('actualizarLugarYFecha shim:', e); }
}



async function guardarYGenerar() {
  const solicitudId = sessionStorage.getItem('solicitud_id') || window.solicitudIdGlobal;

  if (!solicitudId) {
    await Swal.fire("⚠️", "Primero debes completar el paso 1 para iniciar la solicitud.", "warning");
    return;
  }

  // 1) Guardar Paso 8 y ESPERAR
  const ok = await guardarPaso8();   // ← asegúrate que guardarPaso8() retorne true/false
  if (!ok) return;

  // 2) Pinta Lugar/Fecha en el formato antes de mostrarlo
  actualizarLugarYFecha();

  // 3) Generar (o recuperar) el folio
  try {
    const res = await fetch(`${BASE_API}generar_folio.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: new URLSearchParams({ solicitud_id: solicitudId })
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch (e) {
      console.error("❌ JSON inválido:", text);
      await Swal.fire("Error", "Respuesta inesperada del servidor", "error");
      return;
    }

    if (data.status === 'ok' || data.status === 'ya_generado') {
      // guardar folio “bonito” y pintarlo si existe el span
      if (data.folio) {
        sessionStorage.setItem('folio_bonito', data.folio);
        const folioEl = document.getElementById('folioTexto');
        if (folioEl && (!folioEl.textContent.trim() || folioEl.textContent.trim() === '—')) {
          folioEl.textContent = data.folio;
        }
      }

      // 4) Fecha de consulta (del día de impresión)
      const hoy = new Date();
      const fechaFormateada = hoy.toLocaleDateString('es-MX', { year:'numeric', month:'2-digit', day:'2-digit' });
      const fc = document.getElementById('fecha-consulta');
      if (fc) fc.textContent = fechaFormateada;

      // 5) Mostrar el formato (ya con lugar/fecha y folio)
      generarFormato();

    } else if (data.status === 'incompleto') {
      await Swal.fire("🚧 Faltan pasos por completar", (data.faltan || []).join('\n'), "warning");
    } else {
      await Swal.fire("Error", data.message || "Ocurrió un error al generar el folio.", "error");
    }
  } catch (err) {
    console.error("❌ Error en generar_folio.php:", err);
    await Swal.fire("Error del servidor", "No se pudo conectar al servidor", "error");
  }
}



function omitir() {
  nextPrev(1); // Avanza al siguiente paso del formulario multipaso
}

function toggleCamposAuto(valor) {
  const autoFields = document.getElementById('auto-fields');

  if (!autoFields) {
    console.warn('⚠️ No se encontró el contenedor de campos del auto.');
    return;
  }

  if (valor === 'Sí') {
    autoFields.style.display = 'block';
  } else {
    autoFields.style.display = 'none';
  }
}

function toggleAutoFields() {
  const poseeAuto = document.querySelector('input[name="posee_auto"]:checked');
  const autoFields = document.getElementById('auto-fields');

  if (poseeAuto && poseeAuto.value === 'Sí') {
    autoFields.style.display = 'block';

    // Limpiar si estaban como "N/A"
    document.getElementById('marca_auto').value = '';
    document.getElementById('valor_auto').value = '';
    document.getElementById('empresa_auto').value = '';
    document.getElementById('mensualidad_auto').value = '';
  } else {
    autoFields.style.display = 'none';

    // Colocar N/A si no tiene auto
    document.getElementById('marca_auto').value = 'N/A';
    document.getElementById('valor_auto').value = 'N/A';
    document.getElementById('empresa_auto').value = 'N/A';
    document.getElementById('mensualidad_auto').value = 'N/A';
  }
}






document.querySelectorAll('input[name="tipo_vivienda"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const tipo = this.value;

    // Ocultar todos los grupos primero
    document.getElementById('grupo-propia').style.display = 'none';
    document.getElementById('grupo-hipoteca').style.display = 'none';
    document.getElementById('grupo-propietario').style.display = 'none';

    if (tipo === 'Propia') {
      document.getElementById('grupo-propia').style.display = 'block';
    } else if (tipo === 'Hipoteca') {
      document.getElementById('grupo-hipoteca').style.display = 'block';
    } else if (['Renta', 'Familiar', 'Huesped'].includes(tipo)) {
      document.getElementById('grupo-propietario').style.display = 'block';
    }
  });
});

function borrarFirmaAutorizacion() {
  const canvas = document.getElementById('firmaAutorizacion');
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}


// ✅ 1. Obtener el folio desde la URL (si viene desde "Continuar") y guardarlo
const params = new URLSearchParams(window.location.search);
const folioDesdeURL = params.get('folio');
if (folioDesdeURL) {
  sessionStorage.setItem('solicitud_id', folioDesdeURL);
  solicitudIdGlobal = folioDesdeURL;
}

// ✅ 2. Esperar que cargue el DOM para ejecutar todo
document.addEventListener('DOMContentLoaded', () => {
  showStep(currentStep); // Mostrar paso actual

  const folio = sessionStorage.getItem('solicitud_id');
  if (!folio) {
    console.warn("⚠️ No se encontró folio en sessionStorage.");
    return;
  }

  // ✅ 3. Cargar datos de la tabla "solicitudes"
fetch(`${BASE_API}obtener_datos/get_solicitud_general.php?folio=${folio}`)
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      const d = data.datos;

document.getElementById('atendio').value    = d.atendido_por    || '';
document.getElementById('medio').value      = d.medio           || '';
document.getElementById('monto').value      = d.monto           || '';
document.getElementById('plazo').value      = d.plazo           || '';

const tasaInput = document.getElementById('tasa_mensual');
if (tasaInput) {
  tasaInput.value = d.tasa_mensual || '10.50';
}

document.getElementById('frecuencia').value = d.frecuencia_pago || '';

      // --- modalidad ---
      const sel = document.getElementById('contrato_modalidad');
      if (sel) {
        const raw = (d.contrato_modalidad ?? '').toString().trim().toUpperCase();

        let val = '';

        if (raw === 'SEM_P10') {
          val = 'SEM_P10';
        } else if (raw === 'P10_ORD') {
          val = 'P10_ORD';
        } else if (raw === 'P40_ORD') {
          val = 'P40_ORD';
        } else if (raw === 'P40') {
          val = 'P40';
        } else if (raw === 'P10') {
          val = 'P10';
        }

        sel.value = sel.querySelector(`option[value="${val}"]`) ? val : '';
      }

    } else {
      console.warn('⚠️ No se encontraron datos de la tabla solicitudes.');
    }
  })
  .catch(err => console.error('❌ Error al cargar datos de solicitudes:', err));

  // ✅ 4. Cargar datos personales
// ⚠️ usa esta versión: espera a que se llene el select antes de fijar la colonia
fetch(`${BASE_API}obtener_datos/get_solicitud_por_id.php?folio=${folio}`)   // ó ?id=
  .then(res => res.json())
  .then(async data => {
    // 1) Validación de la respuesta según tu PHP
    if (!data || data.ok !== true) {
      console.warn('⚠️ No se encontró la solicitud/folio.', data);
      return;
    }

    // 2) El cliente viene en data.cliente (puede ser null si aún no capturaste)
    const d = data.cliente || {};
    // (si necesitas algo de la solicitud: const s = data.solicitud || {};)

    // 3) Rellenar campos de forma segura
    const setVal = (id, val) => {
      const el = document.getElementById(id);
      if (el) el.value = (val ?? '');
    };

    setVal('nombres', d.nombres);
    setVal('apellido_paterno', d.apellido_paterno);
    setVal('apellido_materno', d.apellido_materno);
    setVal('correo', d.correo);
    setVal('genero', d.genero);
    setVal('fecha_nacimiento', d.fecha_nacimiento);
    setVal('estado_nacimiento', d.estado_nacimiento);
    setVal('dependientes', d.dependientes);
    setVal('nacionalidad', d.nacionalidad);
    setVal('pais_nacimiento', d.pais_nacimiento);
    setVal('fiel', d.fiel);
    setVal('rfc', d.rfc);
    setVal('curp', d.curp);
    setVal('estado_civil', d.estado_civil);
    setVal('tiempo_estado_civil', d.tiempo_estado_civil);
    setVal('escolaridad', d.escolaridad);
    setVal('profesion', d.profesion);
    setVal('direccion', d.direccion);
    setVal('entre_calles', d.entre_calles);
    setVal('municipio', d.municipio);
    setVal('estado', d.estado);
    setVal('pais', d.pais);
    setVal('tiempo_domicilio', d.tiempo_domicilio);
    setVal('telefono', d.telefono);
    setVal('celular', d.celular);
    setVal('mejor_hora', d.mejor_hora);

    // Guarda el id de la solicitud para el resto de pasos si lo usas
    window.solicitudIdGlobal = (d.solicitud_id ?? (data.solicitud?.id ?? null));

    // --- CP y colonias ---
    const cpEl = document.getElementById('cp');
    const selCol = document.getElementById('colonia');
    const coloniaGuardada = (d.colonia || '').trim();
    if (cpEl) cpEl.value = d.cp || '';

    try {
      if (typeof buscarPorCP === 'function') {
        await buscarPorCP(true); // llena el select de colonias para ese CP
      }
    } catch (e) {
      console.error('buscarPorCP falló:', e);
    }

    if (selCol && coloniaGuardada) {
      const match = Array.from(selCol.options || []).find(o =>
        (o.value || '').trim().toLowerCase() === coloniaGuardada.toLowerCase() ||
        (o.text  || '').trim().toLowerCase() === coloniaGuardada.toLowerCase()
      );
      if (match) {
        selCol.value = match.value;
        selCol.dispatchEvent(new Event('change', { bubbles:true }));
      } else if (selCol.options.length > 1 && selCol.options[1].value) {
        selCol.selectedIndex = 1;
      }
    }

    if (typeof window.revalidarIdentidad === 'function') {
      window.revalidarIdentidad();
    }
  })
  .catch(err => console.error('❌ Error al cargar datos personales:', err));

cargarInfoLaboral(folio);
cargarInfoAdicional(folio);
cargarFirmaDeclaracion(folio); 
cargarFirmaCanvas('firmaCanvas1', folio, 'firma_base64');
cargarFirmaCanvas('firmaCanvas2', folio, 'firma_base64_2');
cargarReferenciasSolicitante(folio);
cargarCodeudor(folio);
cargarPaso8Funcionario(folio); 
actualizarLugarYFecha();
});

function cargarInfoLaboral(folio) {
  fetch(`${BASE_API}obtener_datos/get_info_laboral.php?folio=${folio}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const d = data.datos;
        document.getElementById('puesto').value = d.puesto || '';
        document.getElementById('empresa').value = d.empresa || '';
        document.getElementById('giro_empresa').value = d.giro_empresa || '';
        document.getElementById('direccion_trabajo').value = d.direccion_trabajo || '';
        document.getElementById('calles_trabajo').value = d.calles_trabajo || '';
        document.getElementById('ref_empresa_trabajo_input').value = d.referencia_trabajo || '';
        document.getElementById('colonia_trabajo').value = d.colonia_trabajo || '';
        document.getElementById('municipio_trabajo').value = d.municipio_trabajo || '';
        document.getElementById('estado_trabajo').value = d.estado_trabajo || '';
        document.getElementById('pais_trabajo').value = d.pais_trabajo || '';
        document.getElementById('tiempo_empleo').value = d.tiempo_empleo || '';
        document.getElementById('telefono_trabajo').value = d.telefono_trabajo || '';
        document.getElementById('horario_trabajo').value = d.horario_trabajo || '';
        document.getElementById('sueldo').value = d.sueldo || '';
        document.getElementById('forma_pago').value = d.forma_pago || '';
        document.getElementById('otros_ingresos').value = d.otros_ingresos || '';
        document.getElementById('fuente_ingresos').value = d.fuente_ingresos || '';
        document.getElementById('ubicacion_negocio').value = d.ubicacion_negocio || '';
      } else {
        console.warn('⚠️ No se encontró información laboral.');
      }
    })
    .catch(err => console.error('❌ Error al obtener información laboral:', err));
}



function cargarInfoAdicional(folio) {
  fetch(`${BASE_API}obtener_datos/get_info_adicional.php?folio=${folio}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const d = data.datos;

        // ✅ Radio tipo_vivienda
        document.querySelector(`input[name="tipo_vivienda"][value="${d.tipo_vivienda}"]`)?.click();

        // ✅ Campo parentesco
        const campoParentesco = document.getElementById('parentesco');
        if (campoParentesco) {
          campoParentesco.value = d.parentesco_propietario || 'N/A';
        }

        // ✅ Radio posee_auto
        const radioAuto = document.querySelector(`input[name="posee_auto"][value="${d.posee_auto}"]`);
        if (radioAuto) {
          radioAuto.checked = true;
          toggleCamposAuto(d.posee_auto);  // ✅ aquí dentro, cuando ya existe d
        } else {
          console.warn('⚠️ Opción de "posee_auto" no encontrada para:', d.posee_auto);
        }

        // ✅ Lista de inputs a llenar
        const campos = [
          ['pago_casa', d.pago_casa],
          ['pago_servicios', d.pago_servicios],
          ['pago_otros', d.pago_otros],
          ['gasto_mensual', d.gasto_mensual],
          ['valor_casa', d.valor_casa],
          ['saldo_hipoteca', d.saldo_hipoteca],
          ['empresa_hipoteca', d.empresa_hipoteca],
          ['nombre_propietario', d.nombre_propietario],
          ['telefono_propietario', d.telefono_propietario],
          ['marca_auto', d.marca_auto],
          ['valor_auto', d.valor_auto],
          ['empresa_auto', d.empresa_auto],
          ['mensualidad_auto', d.mensualidad_auto]
        ];

        campos.forEach(([id, valor]) => {
          const input = document.getElementById(id);
          if (input) {
            input.value = valor ?? '';
          } else {
            console.warn(`⚠️ Campo no encontrado: ${id}`);
          }
        });

        // ✅ Actualizar resumen (formato final)
        document.getElementById('res-valor-casa').textContent = d.valor_casa || 'N/A';
        document.getElementById('res-saldo-hipoteca').textContent = d.saldo_hipoteca || 'N/A';
        document.getElementById('res-empresa-hipoteca').textContent = d.empresa_hipoteca || 'N/A';
        document.getElementById('res-nombre-propietario').textContent = d.nombre_propietario || 'N/A';
        document.getElementById('res-parentesco').textContent = d.parentesco_propietario || 'N/A';
        document.getElementById('res-telefono-propietario').textContent = d.telefono_propietario || 'N/A';

        document.getElementById('res-posee-auto').textContent = d.posee_auto || 'N/A';
        document.getElementById('res-auto-detalle').textContent = d.marca_auto || 'N/A';
        document.getElementById('res-auto-valor').textContent = d.valor_auto || 'N/A';
        document.getElementById('res-auto-empresa').textContent = d.empresa_auto || 'N/A';
        document.getElementById('res-auto-mensualidad').textContent = d.mensualidad_auto || 'N/A';

      } else {
        console.warn('⚠️ No se encontró información adicional.');
      }
    })
    .catch(err => console.error('❌ Error al obtener información adicional:', err));
}



function cargarReferenciasSolicitante(folio) {
  fetch(`${BASE_API}obtener_datos/get_referencias.php?folio=${folio}`)
    .then(res => res.json())
    .then(data => {
      if (data.success && data.referencias) {
        const referencias = data.referencias;

        const familiares = referencias.filter(r => r.tipo === "Familiar");
        const personales = referencias.filter(r => r.tipo === "Personal");

        const safeSet = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.value = (val ?? '').toString();
        };

        // helper para email (email > correo > mail)
        const pickEmail = (obj = {}) =>
          (obj.email || obj.correo || obj.mail || '').toString().trim();

        // Familiares
        safeSet('form_ref_fam_nombre',        familiares[0]?.nombre_completo);
        safeSet('form_ref_fam_direccion',     familiares[0]?.direccion);
        safeSet('form_ref_fam_telefono',      familiares[0]?.telefono);
        safeSet('form_ref_fam_celular',       familiares[0]?.celular);
        safeSet('form_ref_fam_parentesco',    familiares[0]?.parentesco);
        safeSet('form_ref_fam_correo',        pickEmail(familiares[0]));   // ← correo 1

        safeSet('form_ref_fam_nombre_2',      familiares[1]?.nombre_completo);
        safeSet('form_ref_fam_direccion_2',   familiares[1]?.direccion);
        safeSet('form_ref_fam_telefono_2',    familiares[1]?.telefono);
        safeSet('form_ref_fam_celular_2',     familiares[1]?.celular);
        safeSet('form_ref_fam_parentesco_2',  familiares[1]?.parentesco);
        safeSet('form_ref_fam_correo_2',      pickEmail(familiares[1]));   // ← correo 2

        // Personales
        safeSet('form_ref_per_nombre',        personales[0]?.nombre_completo);
        safeSet('form_ref_per_direccion',     personales[0]?.direccion);
        safeSet('form_ref_per_telefono',      personales[0]?.telefono);
        safeSet('form_ref_per_celular',       personales[0]?.celular);
        safeSet('form_ref_per_parentesco',    personales[0]?.parentesco);
        safeSet('form_ref_per_correo',        pickEmail(personales[0]));   // ← correo 1

        safeSet('form_ref_per_nombre_2',      personales[1]?.nombre_completo);
        safeSet('form_ref_per_direccion_2',   personales[1]?.direccion);
        safeSet('form_ref_per_telefono_2',    personales[1]?.telefono);
        safeSet('form_ref_per_celular_2',     personales[1]?.celular);
        safeSet('form_ref_per_parentesco_2',  personales[1]?.parentesco);
        safeSet('form_ref_per_correo_2',      pickEmail(personales[1]));   // ← correo 2
      } else {
        console.warn('⚠️ No se encontraron referencias para este folio.');
      }
    })
    .catch(err => console.error('❌ Error al obtener referencias:', err));
}





function cargarPaso8Funcionario(folio) {
  console.log("🚀 Ejecutando cargarPaso8Funcionario para folio:", folio);

  fetch(`${BASE_API}obtener_datos/get_funcionarios_firma.php?folio=${encodeURIComponent(folio)}`)
    .then(r => r.json())
    .then(data => {
      console.log("🔍 Datos recibidos de funcionarios_firma:", data);

      if (!(data.success && data.datos)) {
        console.warn("⚠️ No se encontraron firmas del funcionario.");
        return;
      }

      const d = data.datos;

      // ----- Radios -----
      const vFuncion  = d.desempenia_funcion_publica?.trim();
      const vRelacion = d.relacion_funcion_publica?.trim();
      document.querySelector(`input[name="form_funcion_publica"][value="${vFuncion}"]`)?.click();
      document.querySelector(`input[name="form_relacion_publica"][value="${vRelacion}"]`)?.click();

      // ----- Helper fecha -----
      const cruda = (d.fecha ?? d.fecha_firma ?? "").toString().trim(); // admite 'fecha' o 'fecha_firma'
      console.log("📅 Fecha recibida (cruda):", cruda);

      // Normaliza a YYYY-MM-DD para input date y a formato largo para span
      const fechaYYYYMMDD = (() => {
        if (!cruda) return "";
        // casos: 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS', 'YYYY/MM/DD', '2025-08-07T15:36:18'
        const soloFecha = cruda.split("T")[0].split(" ")[0]; // quita hora si viene
        // valida que sea YYYY-MM-DD
        const m = soloFecha.match(/^(\d{4})[-/](\d{2})[-/](\d{2})$/);
        if (!m) return "";
        return `${m[1]}-${m[2]}-${m[3]}`;
      })();

      const fechaBonita = (() => {
        if (!fechaYYYYMMDD) return "";
        const [Y, M, D] = fechaYYYYMMDD.split("-").map(Number);
        const dt = new Date(Y, M - 1, D);
        if (isNaN(dt.getTime())) return "";
        return dt.toLocaleDateString("es-MX", { day: "numeric", month: "long", year: "numeric" });
      })();

      // ----- Firmas en canvas -----
      const pintarEnCanvas = (canvasId, dataUrl) => {
        if (!(dataUrl && dataUrl.startsWith("data:image"))) return;
        const cv = document.getElementById(canvasId);
        if (!cv) return;
        const ctx = cv.getContext("2d");
        const img = new Image();
        img.onload = () => {
          // limpiar y ajustar al tamaño actual del canvas
          ctx.clearRect(0, 0, cv.width, cv.height);
          ctx.drawImage(img, 0, 0, cv.width, cv.height);
        };
        img.src = dataUrl;
      };

      pintarEnCanvas("firmaAutorizacion", d.firma);
      pintarEnCanvas("firmaFormulario",  d.firma_formulario);

      // ----- Spans de solo lectura -----
      const spanLugar = document.getElementById("lugar-funcionario");
      const spanFecha = document.getElementById("fecha-funcionario");
      if (spanLugar) spanLugar.textContent = (d.lugar?.trim() || "Sin lugar");
      if (spanFecha) spanFecha.textContent = (fechaBonita || "Sin fecha");

      // ----- Inputs para edición -----
      const inputLugar = document.getElementById("campoLugar");
      const inputFecha = document.getElementById("campoFecha");
      if (inputLugar) inputLugar.value = d.lugar || "";
      if (inputFecha) inputFecha.value = fechaYYYYMMDD || ""; // <- formato correcto para <input type="date">
    })
    .catch(err => console.error("❌ Error al obtener firmas del funcionario:", err));
}




function cargarCodeudor(folio) {
  fetch(`${BASE_API}obtener_datos/get_codeudor.php?folio=${folio}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const d = data.datos;

        document.getElementById('form_co_nombre').value = d.nombre || '';
        document.getElementById('form_co_apellido_paterno').value = d.apellido_paterno || '';
        document.getElementById('form_co_apellido_materno').value = d.apellido_materno || '';
        document.getElementById('form_co_parentesco').value = d.parentesco || '';
        document.getElementById('form_co_correo').value = d.correo || '';
        document.getElementById('form_co_genero').value = d.genero || '';
        document.getElementById('form_co_nacimiento').value = d.fecha_nacimiento || '';
        document.getElementById('form_co_entidad').value = d.entidad_federativa || '';
        document.getElementById('form_co_dependientes').value = d.dependientes || '';
        document.getElementById('form_co_nacionalidad').value = d.nacionalidad || '';
        document.getElementById('form_co_pais_nacimiento').value = d.pais_nacimiento || '';
        document.getElementById('form_co_rfc').value = d.rfc || '';
        document.getElementById('form_co_curp').value = d.curp || '';
        document.getElementById('form_co_direccion').value = d.direccion_actual || '';
        document.getElementById('form_co_entre_calles').value = d.entre_calles || '';
        document.getElementById('form_co_colonia').value = d.colonia || '';
        document.getElementById('form_co_cp').value = d.codigo_postal || '';
        document.getElementById('form_co_municipio').value = d.municipio || '';
        document.getElementById('form_co_estado').value = d.estado || '';
        document.getElementById('form_co_pais').value = d.pais || '';
        document.getElementById('form_co_tiempo').value = d.tiempo_domicilio || '';
        document.getElementById('form_co_tel').value = d.telefono || '';
        document.getElementById('form_co_cel').value = d.celular || '';
        document.getElementById('form_co_mejor_hora').value = d.horario_contacto || '';

      } else {
        console.warn('⚠️ No se encontró información del codeudor.');
      }
    })
    .catch(err => console.error('❌ Error al cargar codeudor:', err));
}

// --- base: prepara el canvas con DPR (nítido) y fondo blanco ---
function prepararCanvasFirma(canvas, w = 350, h = 200) {
  canvas.style.width  = w + "px";
  canvas.style.height = h + "px";
  const dpr = Math.max(window.devicePixelRatio || 1, 1);
  canvas.width  = Math.round(w * dpr);
  canvas.height = Math.round(h * dpr);

  const ctx = canvas.getContext("2d");
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

  // fondo blanco
  ctx.fillStyle = "#fff";
  ctx.fillRect(0, 0, w, h);

  // trazo por si luego firmas encima
  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  ctx.lineWidth = 2;
  ctx.strokeStyle = "#000";

  // guarda meta
  canvas._firmaMeta = { w, h, dpr };
  return ctx;
}

// --- helper: dibuja la imagen con "contain" + centrado + padding ---
function drawImageContain(ctx, canvas, img, padding = 10) {
  const { w=350, h=200 } = canvas._firmaMeta || {};
  // limpiar y fondo blanco
  ctx.save();
  ctx.setTransform(1,0,0,1,0,0);
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.restore();

  ctx.fillStyle = "#fff";
  ctx.fillRect(0, 0, w, h);

  // cálculo contain
  const availW = Math.max(1, w - 2*padding);
  const availH = Math.max(1, h - 2*padding);
  const scale  = Math.min(availW / img.naturalWidth, availH / img.naturalHeight);

  const drawW = Math.max(1, Math.round(img.naturalWidth  * scale));
  const drawH = Math.max(1, Math.round(img.naturalHeight * scale));
  const dx = Math.round((w - drawW) / 2);
  const dy = Math.round((h - drawH) / 2);

  // nitidez (sin blur)
  ctx.imageSmoothingEnabled = false;
  ctx.drawImage(img, dx, dy, drawW, drawH);
}

// ======================= TUS FUNCIONES AJUSTADAS =======================

function cargarFirmaCanvas(canvasId, folio, campo) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) {
    console.warn(`⚠️ Canvas con ID "${canvasId}" no encontrado.`);
    return;
  }

  // asegúrate de que el canvas tenga DPR correcto y fondo blanco
  const ctx = prepararCanvasFirma(canvas, 350, 200);

  const img = new Image();
  img.onload = function () {
    drawImageContain(ctx, canvas, img, 10); // padding de 10px
  };
  img.onerror = function () {
    console.warn(`⚠️ No se pudo cargar la firma (${campo}) en el canvas "${canvasId}"`);
  };
  img.src = `${BASE_API}ver_firma.php?folio=${folio}&campo=${campo}`;
}

function cargarFirmaCanvasPersonalizado(canvasId, folio, campo, phpFile) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) {
    console.warn(`⚠️ Canvas con ID "${canvasId}" no encontrado.`);
    return;
  }

  const ctx = prepararCanvasFirma(canvas, 350, 200);

  const img = new Image();
  img.onload = function () {
    drawImageContain(ctx, canvas, img, 10);
  };
  img.onerror = function () {
    console.warn(`⚠️ No se pudo cargar la firma desde ${phpFile}, campo ${campo}`);
  };
  img.src = `${BASE_API}controllers/${phpFile}.php?folio=${folio}&campo=${campo}`;
}



function cargarFirmaAutorizacion(base64) {
  const canvas = document.getElementById("firmaAutorizacion");
  const ctx = canvas.getContext("2d");

  // Limpia el canvas
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Si hay firma base64, dibujarla
  if (base64 && base64.startsWith("data:image")) {
    const img = new Image();
    img.onload = function () {
      ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
    };
    img.src = base64;
  }
}

//ultimas firmas

function cargarFirmaDeclaracion(folio) {
  fetch(`${API_OBTENER}get_firma_declaracion.php?folio=${folio}`)

    .then(res => res.json())
    .then(data => {
      if (!data.success || !data.datos) {
        console.warn("⚠️ No se encontró información de firma_declaracion.");
        return;
      }

      const d = data.datos;
      console.log("🟢 Datos recibidos de firma_declaracion:", d);
          // ✅ Lugar y Fecha
      const inputLugar = document.getElementById('lugar');
      if (inputLugar) inputLugar.value = d.lugar || '';

      const inputFecha = document.getElementById('fecha');
      if (inputFecha) inputFecha.value = d.fecha || '';

      // ✅ Normalización para comparación sin tildes
      const normalizar = (str) =>
        str?.normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim().toLowerCase();

      // ✅ Marcar los radios
      document.querySelectorAll('input[name="funcion_publica"]').forEach(r => {
        if (normalizar(r.value) === normalizar(d.funcion_publica)) r.checked = true;
      });

      document.querySelectorAll('input[name="relacion_funcion_publica"]').forEach(r => {
        if (normalizar(r.value) === normalizar(d.relacion_funcion_publica)) r.checked = true;
      });

      // ✅ Folio
      const inputFolio = document.getElementById('folio_consulta');
      if (inputFolio) inputFolio.value = d.folio_consulta || '';

      // ✅ Firmas - IDs corregidos
      const canvas1 = document.getElementById('firmaVistaSolicitante');
      const canvas2 = document.getElementById('firmaVistaAutorizacion');
      const ctx1 = canvas1?.getContext('2d');
      const ctx2 = canvas2?.getContext('2d');

      ctx1?.clearRect(0, 0, canvas1.width, canvas1.height);
      ctx2?.clearRect(0, 0, canvas2.width, canvas2.height);

      if (ctx1 && d.firma_base64?.includes('data:image')) {
        const img1 = new Image();
        img1.onload = () => ctx1.drawImage(img1, 0, 0, canvas1.width, canvas1.height);
        img1.src = d.firma_base64;
      }

      if (ctx2 && d.firma_base64_2?.includes('data:image')) {
        const img2 = new Image();
        img2.onload = () => ctx2.drawImage(img2, 0, 0, canvas2.width, canvas2.height);
        img2.src = d.firma_base64_2;
      }
    })
    .catch(err => console.error("❌ Error al obtener firma_declaracion:", err));
}




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


setTimeout(() => {
  const canvas = document.getElementById("firmaAutorizacion");
  const ctx = canvas?.getContext("2d");

  if (!canvas || !ctx || !sessionStorage.getItem('firma_autorizacion')) return;

  const img = new Image();
  img.onload = () => ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
  img.src = sessionStorage.getItem('firma_autorizacion');
}, 500);


// Fallback: define el formateador si no existe
if (typeof window.formatearYYYYMMDDaLargoMX !== 'function') {
  window.formatearYYYYMMDDaLargoMX = function (str) {
    if (!str) return null;
    // Acepta 'YYYY-MM-DD' y corta posibles 'T...'
    const s = String(str).trim().split('T')[0].split(' ')[0];
    const m = s.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!m) return s; // si no matchea, regresa tal cual
    const meses = [
      "enero","febrero","marzo","abril","mayo","junio",
      "julio","agosto","septiembre","octubre","noviembre","diciembre"
    ];
    const yyyy = m[1], mm = parseInt(m[2],10), dd = m[3];
    return `${dd} de ${meses[mm-1]} de ${yyyy}`;
  };
}



// ---------- utils de fecha ----------
function toISO(s) {
  if (!s) return "";
  s = String(s).replace(/\u00A0/g,' ').trim();
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;                         // YYYY-MM-DD
  let m = s.match(/^\s*(\d{1,2})\D+(\d{1,2})\D+(\d{4})\s*$/);          // DD/MM/YYYY, DD-MM-YYYY, etc.
  if (m) return `${m[3]}-${m[2].padStart(2,'0')}-${m[1].padStart(2,'0')}`;
  const d = s.replace(/\D/g,'');                                       // 14082025
  if (d.length===8) return `${d.slice(4,8)}-${d.slice(2,4)}-${d.slice(0,2)}`;
  return "";
}
function fechaLargaES(iso) {
  if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return "N/A";
  const [y,m,d] = iso.split('-').map(n=>parseInt(n,10));
  const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
  return `${d} de ${meses[m-1]} de ${y}`;
}

// ---------- PASO 5  (inputs: #lugar, #fecha  → spans: #res-lugar, #res-fecha) ----------
(function(){
  const IN_LUGAR = 'lugar', IN_FECHA = 'fecha', OUT_LUGAR = 'res-lugar', OUT_FECHA = 'res-fecha';

  function leerYPintarPaso5() {
    const lugar = document.getElementById(IN_LUGAR)?.value?.trim() || "";
    const raw   = document.getElementById(IN_FECHA)?.value?.trim() || "";
    const iso   = toISO(raw);
    const spanL = document.getElementById(OUT_LUGAR);
    const spanF = document.getElementById(OUT_FECHA);
    if (spanL) spanL.textContent = lugar || "N/A";
    if (spanF) spanF.textContent = iso ? fechaLargaES(iso) : "N/A";
    return { lugar, fechaISO: iso };
  }

  document.addEventListener('DOMContentLoaded', () => {
    leerYPintarPaso5();
    // repintados por si llega AJAX después
    setTimeout(leerYPintarPaso5, 250);
    setTimeout(leerYPintarPaso5, 700);
    // en vivo
    ['input','change'].forEach(evt => {
      document.getElementById(IN_LUGAR)?.addEventListener(evt, leerYPintarPaso5);
      document.getElementById(IN_FECHA)?.addEventListener(evt, leerYPintarPaso5);
    });
  });

  // helper público
  window.paso5 = { leer: leerYPintarPaso5, actualizarLugarYFecha: leerYPintarPaso5 };
})();

// ---------- PASO 8  (inputs: #campoLugar, #campoFecha  → spans: #lugar-funcionario, #fecha-funcionario) ----------
(function(){
  const IN_LUGAR = 'campoLugar', IN_FECHA = 'campoFecha', OUT_LUGAR = 'lugar-funcionario', OUT_FECHA = 'fecha-funcionario';

  function leerYPintarPaso8() {
    const lugar = document.getElementById(IN_LUGAR)?.value?.trim() || "";
    const raw   = document.getElementById(IN_FECHA)?.value?.trim() || "";
    const iso   = toISO(raw);
    const spanL = document.getElementById(OUT_LUGAR);
    const spanF = document.getElementById(OUT_FECHA);
    if (spanL) spanL.textContent = lugar || "N/A";
    if (spanF) spanF.textContent = iso ? fechaLargaES(iso) : "N/A";
    return { lugar, fechaISO: iso };
  }

  document.addEventListener('DOMContentLoaded', () => {
    leerYPintarPaso8();
    // repintados por si llega AJAX después
    setTimeout(leerYPintarPaso8, 250);
    setTimeout(leerYPintarPaso8, 700);
    // en vivo
    ['input','change'].forEach(evt => {
      document.getElementById(IN_LUGAR)?.addEventListener(evt, leerYPintarPaso8);
      document.getElementById(IN_FECHA)?.addEventListener(evt, leerYPintarPaso8);
    });
  });

  // helper público
  window.paso8 = { leer: leerYPintarPaso8, actualizarLugarYFecha: leerYPintarPaso8 };
})();






// ============= Base: preparar + dibujar en canvas (PC + tablet) priemras 2 firmas =============
function prepararCanvasFirma(canvas, w = 350, h = 200) {
  if (!canvas) return null;
  canvas.style.width  = w + "px";
  canvas.style.height = h + "px";
  const dpr = Math.max(window.devicePixelRatio || 1, 1);
  canvas.width  = Math.round(w * dpr);
  canvas.height = Math.round(h * dpr);
  const ctx = canvas.getContext("2d");
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

  // Fondo blanco para que no sea transparente
  ctx.fillStyle = "#fff";
  ctx.fillRect(0, 0, w, h);

  ctx.lineCap = "round";
  ctx.lineJoin = "round";
  ctx.lineWidth = 2;
  ctx.strokeStyle = "#000";

  canvas._firmaMeta = { w, h, dpr };
  return ctx;
}

function habilitarFirmaEnCanvas(canvasId, { onChange } = {}) {
  const canvas = document.getElementById(canvasId);
  const ctx = prepararCanvasFirma(canvas, 350, 200);
  if (!canvas || !ctx) return;

  let dibujando = false;
  canvas.style.touchAction = "none";
  canvas.style.userSelect = "none";

  function getPos(evt) {
    const r = canvas.getBoundingClientRect();
    return { x: evt.clientX - r.left, y: evt.clientY - r.top };
  }

  function start(e){ e.preventDefault(); dibujando = true; const {x,y}=getPos(e); ctx.beginPath(); ctx.moveTo(x,y); }
  function move(e){ if(!dibujando) return; e.preventDefault(); const {x,y}=getPos(e); ctx.lineTo(x,y); ctx.stroke(); if(onChange) onChange(obtenerFirmaNormalizada(canvas)); }
  function end(e){ if(!dibujando) return; e.preventDefault(); dibujando = false; ctx.closePath(); if(onChange) onChange(obtenerFirmaNormalizada(canvas)); }

  canvas.addEventListener("pointerdown", start, { passive:false });
  canvas.addEventListener("pointermove",  move,  { passive:false });
  canvas.addEventListener("pointerup",    end,   { passive:false });
  canvas.addEventListener("pointerleave", end,   { passive:false });
  canvas.addEventListener("pointercancel",end,   { passive:false });

  canvas._limpiarFirma = function() {
    const { w, h, dpr } = canvas._firmaMeta || { w:350, h:200, dpr:1 };
    ctx.setTransform(1,0,0,1,0,0);
    ctx.clearRect(0,0,canvas.width,canvas.height);
    ctx.setTransform(dpr,0,0,dpr,0,0);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0,0,w,h);
    ctx.lineCap = "round"; ctx.lineJoin="round"; ctx.lineWidth=2; ctx.strokeStyle="#000";
    if (onChange) onChange("");
  };
}

// ============= Normalizador: recorta trazo y lo ajusta a 350x200 =============
function obtenerFirmaNormalizada(canvas, opts = {}) {
  const { w=350, h=200, padding=10, bg="#fff", threshold=240 } = opts;
  const ctx = canvas.getContext("2d");
  // Leemos en coordenadas CSS (ya estamos con transform dpr aplicado)
  const imgData = ctx.getImageData(0, 0, w, h);
  const data = imgData.data;

  // Buscar bbox de los píxeles "no blancos"
  let minX=w, minY=h, maxX=0, maxY=0, found=false;
  for (let y=0; y<h; y++){
    for (let x=0; x<w; x++){
      const i = (y*w + x)*4;
      const r=data[i], g=data[i+1], b=data[i+2];
      const notWhite = (r < threshold || g < threshold || b < threshold); // trazo oscuro
      if (notWhite){
        found=true;
        if (x<minX) minX=x;
        if (y<minY) minY=y;
        if (x>maxX) maxX=x;
        if (y>maxY) maxY=y;
      }
    }
  }

  // Nuevo lienzo de salida 350x200, con fondo
  const out = document.createElement("canvas");
  out.width = w; out.height = h;
  const octx = out.getContext("2d");
  octx.fillStyle = bg; octx.fillRect(0,0,w,h);

  if (!found){
    // No hay trazo: devuelve lienzo en blanco
    return out.toDataURL("image/png");
  }

  // Dimensiones del recorte
  const bw = Math.max(1, maxX - minX + 1);
  const bh = Math.max(1, maxY - minY + 1);

  // Área disponible (resta padding por lados)
  const availW = Math.max(1, w - 2*padding);
  const availH = Math.max(1, h - 2*padding);

  // Escala máxima manteniendo proporción para que quepa dentro del cuadro
  const scale = Math.min(availW / bw, availH / bh);

  const drawW = Math.max(1, Math.round(bw * scale));
  const drawH = Math.max(1, Math.round(bh * scale));

  // Centrar
  const dx = Math.round((w - drawW) / 2);
  const dy = Math.round((h - drawH) / 2);

  // Dibujar la porción recortada escalada y centrada
  octx.imageSmoothingEnabled = false; // firma se ve nítida
  octx.drawImage(canvas,
    minX, minY, bw, bh,   // src recortado
    dx, dy, drawW, drawH  // destino
  );

  return out.toDataURL("image/png");
}

// ============= Vista previa (opcional) con mismo tamaño 350x200 =============
function actualizarPreview(imgId, dataUrl) {
  const img = document.getElementById(imgId);
  if (!img) return;
  img.src = dataUrl || "";
  img.style.width  = "350px";
  img.style.height = "200px";
  img.style.objectFit = "contain";
  img.style.background = "#fff";
  img.style.border = "1px solid #ccc";
}

// ============= Borrar firma compatible con tu botón =============
window.borrarFirma = window.borrarFirma || function (canvasId, vistas = []) {
  const c = document.getElementById(canvasId);
  if (c && typeof c._limpiarFirma === "function") c._limpiarFirma();
  vistas.forEach(id => actualizarPreview(id, ""));
};

// ============= Inicializa SOLO las dos firmas =============
document.addEventListener("DOMContentLoaded", () => {
  habilitarFirmaEnCanvas("firmaCanvas1", {
    onChange: (dataUrl) => actualizarPreview("firmaCanvasVista1", dataUrl)
  });

  habilitarFirmaEnCanvas("firmaCanvas2", {
    onChange: (dataUrl) => actualizarPreview("firmaCanvasVista2", dataUrl)
  });
});

// ============= (Opcional) para guardar: obtén la imagen lista =============
function obtenerPNGparaGuardar(canvasId){
  const c = document.getElementById(canvasId);
  if (!c) return "";
  return obtenerFirmaNormalizada(c); // 350x200, centrada y con margen
}


// ============= Ultimas firmas del formato=============
// ============ PREPARAR (sin setTransform) ============
function prepararCanvas(canvas, w = 350, h = 200) {
  const dpr = Math.max(window.devicePixelRatio || 1, 1);

  // Tamaño visible
  canvas.style.width  = w + "px";
  canvas.style.height = h + "px";

  // Buffer interno nítido
  canvas.width  = Math.round(w * dpr);
  canvas.height = Math.round(h * dpr);

  const ctx = canvas.getContext("2d");
  // 🔑 Importante: NO escalamos el contexto. Lo dejamos en identidad.
  ctx.setTransform(1, 0, 0, 1, 0, 0);

  // Fondo blanco en pixeles de buffer
  ctx.fillStyle = "#fff";
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  // Estilo de trazo escalado para retina
  ctx.lineCap   = "round";
  ctx.lineJoin  = "round";
  ctx.lineWidth = 2 * dpr;      // ← grosor en buffer
  ctx.strokeStyle = "#000";

  canvas._meta = { w, h, dpr, ctx };
  return ctx;
}

// ============ HABILITAR FIRMA (convierte coords a buffer) ============
function habilitarFirma(canvasId) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || canvas._enabled) return;

  prepararCanvas(canvas);
  const { ctx, dpr } = canvas._meta;

  let dibujando = false;
  canvas.style.touchAction = "none";
  canvas.style.userSelect  = "none";

  // Posición en px CSS -> convertir a px de buffer multiplicando por dpr
  function getCssPos(e) {
    if (typeof e.offsetX === "number" && typeof e.offsetY === "number") {
      return { x: e.offsetX, y: e.offsetY };
    }
    const rect = canvas.getBoundingClientRect();
    const cs = getComputedStyle(canvas);
    const bx = parseFloat(cs.borderLeftWidth) || 0;
    const by = parseFloat(cs.borderTopWidth)  || 0;
    return { x: e.clientX - rect.left - bx, y: e.clientY - rect.top - by };
  }
  function toBuffer(p){ return { x: Math.round(p.x * dpr), y: Math.round(p.y * dpr) }; }

  function start(e){
    e.preventDefault(); dibujando = true;
    if (e.pointerId != null && canvas.setPointerCapture) canvas.setPointerCapture(e.pointerId);
    const p = toBuffer(getCssPos(e));
    ctx.beginPath(); ctx.moveTo(p.x, p.y);
  }
  function move(e){
    if (!dibujando) return;
    e.preventDefault();
    const p = toBuffer(getCssPos(e));
    ctx.lineTo(p.x, p.y); ctx.stroke();
  }
  function end(e){
    if (!dibujando) return;
    e.preventDefault(); dibujando = false;
    if (e.pointerId != null && canvas.releasePointerCapture) canvas.releasePointerCapture(e.pointerId);
    ctx.closePath();
  }

  canvas.addEventListener("pointerdown", start, { passive:false });
  canvas.addEventListener("pointermove",  move,  { passive:false });
  canvas.addEventListener("pointerup",    end,   { passive:false });
  canvas.addEventListener("pointerleave", end,   { passive:false });
  canvas.addEventListener("pointercancel",end,   { passive:false });

  // Limpiar
  canvas._limpiar = function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = "#fff";
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.lineCap   = "round";
    ctx.lineJoin  = "round";
    ctx.lineWidth = 2 * dpr;
    ctx.strokeStyle = "#000";
  };

  canvas._enabled = true;
}

// ============ Inicializa tus dos canvases ============
document.addEventListener("DOMContentLoaded", () => {
  habilitarFirma("firmaAutorizacion");
  habilitarFirma("firmaFormulario");
});

// ============ Botones borrar ============
function borrarFirmaAutorizacion(){
  const c = document.getElementById("firmaAutorizacion");
  if (c && c._limpiar) c._limpiar();
}
function borrarFirmaFormulario(){
  const c = document.getElementById("firmaFormulario");
  if (c && c._limpiar) c._limpiar();
}



// crear.js - Manejo de Código Postal y Autocompletado de Direcciones

// ====== refs a tus campos ======
const inpColonia    = document.getElementById('colonia');
const inpCP         = document.getElementById('cp');
const inpMunicipio  = document.getElementById('municipio');
const inpEstado     = document.getElementById('estado');
const inpPais       = document.getElementById('pais');
const selEstadoNac  = document.getElementById('estado_nacimiento');

// ====== helper: avisos ======
function mostrarAviso(msg, tipo = 'ok') {
  let el = document.getElementById('cp-aviso');
  if (!el) {
    el = document.createElement('div');
    el.id = 'cp-aviso';
    el.style.fontSize = '12px';
    el.style.marginTop = '4px';
    el.style.lineHeight = '1.2';
    inpCP.parentElement.appendChild(el);
  }
  el.textContent = msg;
  el.style.color = (tipo === 'ok') ? '#198754' : '#cc8b00';
}

// ====== helper: limpiar cuando cambia el CP ======
function limpiarCamposDependientes() {
  if (inpMunicipio) inpMunicipio.value = '';
  if (inpEstado)    inpEstado.value    = '';
  if (inpColonia) {
    if (inpColonia.tagName.toLowerCase() === 'select') {
      inpColonia.innerHTML = '';
    } else {
      inpColonia.value = '';
    }
  }
}

// ====== normalizador + mapa de estados largos ======
const norm = s => (s || '')
  .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
  .toLowerCase().trim();

const mapaEstados = {
  'mexico': 'Estado de México',
  'coahuila de zaragoza': 'Coahuila',
  'michoacan de ocampo': 'Michoacán',
  'veracruz de ignacio de la llave': 'Veracruz',
  'queretaro de arteaga': 'Querétaro',
  'distrito federal': 'Ciudad de México'
};

// ====== eventos (con forzado en blur/change) ======
let timer;
let ultimoCPConsultado = '';

inpCP.addEventListener('input', () => {
  clearTimeout(timer);
  timer = setTimeout(() => buscarPorCP(false), 250); // no forzar mientras escribe
});

inpCP.addEventListener('blur',   () => buscarPorCP(true)); // forzar al salir
inpCP.addEventListener('change', () => buscarPorCP(true)); // forzar si autocompleta

// ====== función principal ======
async function buscarPorCP(force = false) {
  const raw = (inpCP?.value || '');
  // Normaliza: solo dígitos y máximo 5
  const cp = raw.replace(/\D/g, '').slice(0, 5);
  if (inpCP && inpCP.value !== cp) inpCP.value = cp;

  // Validación rápida
  if (cp.length !== 5) {
    if (cp === '') ultimoCPConsultado = ''; // permitir reconsultar luego
    limpiarCamposDependientes();
    return;
  }

  // Evita repetir misma consulta a menos que se fuerce
  if (!force && cp === ultimoCPConsultado) return;
  ultimoCPConsultado = cp;

  try {
    // Limpia UI para evitar “información vieja”
    limpiarCamposDependientes();

    // BASE_PUBLIC debe existir; normalizamos para que termine en '/public/'
    let base = (typeof BASE_PUBLIC === 'string' && BASE_PUBLIC) ? BASE_PUBLIC : '/';
    if (!base.endsWith('/')) base += '/';
    const idx = base.indexOf('/public/');
    base = (idx !== -1) ? base.slice(0, idx + '/public/'.length) : base;

    const url = `${BASE_API}cp_buscar.php?cp=${encodeURIComponent(cp)}&t=${Date.now()}`;
    console.debug('buscarPorCP →', url);

    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status} ${url}`);

    // Asegura JSON aunque el servidor no fije bien el content-type
    const ct = res.headers.get('content-type') || '';
    const data = ct.includes('application/json') ? await res.json() : JSON.parse(await res.text());

    if (data && data.success) {
      // Estado “bonito” según mapa
      const estadoResp   = data.estado || '';
      const estadoBonito = mapaEstados[norm(estadoResp)] || estadoResp;

      // Autollenado
      if (inpMunicipio) inpMunicipio.value = data.municipio || '';
      if (inpEstado)    inpEstado.value    = estadoBonito || '';
      if (inpPais)      inpPais.value      = 'México';

      // Seleccionar estado de nacimiento si existe el <select>
// seleccionar estado nacimiento (robusto)
if (selEstadoNac && estadoBonito) {
  // Usa [] si no hay options todavía (evita "undefined is not iterable")
  const opts = Array.from(selEstadoNac.options || []);

  const match =
    opts.find(o =>
      norm(o.value) === norm(estadoBonito) ||
      norm(o.textContent) === norm(estadoBonito)
    )
    || opts.find(o =>
      norm(o.value) === norm(estadoResp) ||
      norm(o.textContent) === norm(estadoResp)
    );

  if (match) selEstadoNac.value = match.value;
}


      // --- Colonias: deduplica, ordena y conserva selección previa ---
// --- COLONIAS: deduplica, ordena y selecciona ---
if (inpColonia) {
  const lista = Array.isArray(data.colonias) ? data.colonias : [];

  // Normaliza y deduplica (sin acentos / case-insensitive)
  const seen = new Set();
  const unicas = [];
  for (const c of lista) {
    const original = String(c ?? '').trim();
    if (!original) continue;
    const key = original.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    if (!seen.has(key)) { seen.add(key); unicas.push(original); }
  }

  // Orden alfabético español
  unicas.sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));

  // Siempre intenta dejar algo seleccionado
  const prev = inpColonia.value || '';
  const AUTOFILL_COLONIA_PRIMERA = true;

  if (inpColonia.tagName.toLowerCase() === 'select') {
    inpColonia.innerHTML = ''; // limpia

    if (unicas.length === 0) {
      // Sin colonias: una opción informativa
      inpColonia.add(new Option('— Sin colonias para este CP —', ''));
    } else {
      // Agrega todas
      for (const col of unicas) {
        inpColonia.add(new Option(col, col, false, false));
      }

      // Preferir la selección anterior si sigue existiendo
      if (prev && unicas.includes(prev)) {
        inpColonia.value = prev;
      } else if (AUTOFILL_COLONIA_PRIMERA) {
        // O la primera de la lista
        inpColonia.selectedIndex = 0;
      }
    }

    // Dispara change por si alguien escucha el select
    inpColonia.dispatchEvent(new Event('change', { bubbles: true }));
  } else {
    // Si fuera <input>, rellena con la primera disponible
    if (!inpColonia.value.trim()) inpColonia.value = unicas[0] || '';
  }
}


      mostrarAviso('Datos del CP encontrados y completados.', 'ok');
    } else {
      mostrarAviso('CP no encontrado. Captura manual.', 'warn');
    }
  } catch (err) {
    console.error('Error consultando CP:', err);
    mostrarAviso('No fue posible verificar el CP. Captura manual.', 'warn');
  }
}
// ===============================================================
// === GENERADORES RFC / CURP (y helpers) ========================
// ===============================================================
const sinAcentos = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
const limpiaNombre = s => sinAcentos(s).toUpperCase().replace(/[^A-ZÑ\s]/g,'').replace(/\s+/g,' ').trim();

const STOP_NOMBRES    = new Set(['DA','DAS','DE','DEL','DER','DI','DIE','DD','EL','LA','LOS','LAS','LE','LES','MAC','MC','VAN','VON','Y']);
const NOMBRES_COMUNES = new Set(['JOSE','J','MARIA','MA','MA.']);
const ENTIDAD = {
  "AGUASCALIENTES":"AS","BAJA CALIFORNIA":"BC","BAJA CALIFORNIA SUR":"BS","CAMPECHE":"CC","COAHUILA":"CL",
  "COLIMA":"CM","CHIAPAS":"CS","CHIHUAHUA":"CH","CIUDAD DE MEXICO":"DF","DISTRITO FEDERAL":"DF","DURANGO":"DG",
  "GUANAJUATO":"GT","GUERRERO":"GR","HIDALGO":"HG","JALISCO":"JC","MEXICO":"MC","ESTADO DE MEXICO":"MC",
  "MICHOACAN":"MN","MORELOS":"MS","NAYARIT":"NT","NUEVO LEON":"NL","OAXACA":"OC","PUEBLA":"PL",
  "QUERETARO":"QT","QUINTANA ROO":"QR","SAN LUIS POTOSI":"SP","SINALOA":"SL","SONORA":"SR","TABASCO":"TC",
  "TAMAULIPAS":"TS","TLAXCALA":"TL","VERACRUZ":"VZ","YUCATAN":"YN","ZACATECAS":"ZS","NACIDO EN EL EXTRANJERO":"NE"
};
function limpiaInconveniente(cuatro){
  const malas=new Set(['BUEI','BUEY','CACA','CACO','CAGA','CAGO','CAKA','CAKO','COGE','COGI','COJA','COJE','COJI','COJO','CULO','FETO','GUEY','JOTO','KACA','KACO','KAGA','KAGO','KOGE','KOGI','KOJA','KOJE','KOJI','KOJO','KULO','MAME','MAMO','MEAR','MEAS','MEON','MION','COÑO','PENE','PUTA','PUTO','QULO','RATA','RUIN']);
  return malas.has(cuatro)?(cuatro[0]+'X'+cuatro.slice(2)):cuatro;
}
const primeraVocalInterna      = s => (s||'').toUpperCase().slice(1).match(/[AEIOU]/)?.[0] || 'X';
const primeraConsonanteInterna = s => (s||'').toUpperCase().slice(1).match(/[BCDFGHJKLMNÑPQRSTVWXYZ]/)?.[0] || 'X';
const filtra = txt => (txt||'').split(' ').filter(w=>w && !STOP_NOMBRES.has(w)).join(' ').trim() || (txt||'');

// ======================= RFC (PF) ==============================
function rfcPersona(nombre, apPat, apMat, fechaYMD){
  nombre=limpiaNombre(nombre); apPat=limpiaNombre(apPat); apMat=limpiaNombre(apMat);
  nombre=filtra(nombre); apPat=filtra(apPat); apMat=filtra(apMat);

  let arr=nombre.split(' ').filter(Boolean);
  if(arr.length>1 && NOMBRES_COMUNES.has(arr[0])) arr=arr.slice(1);
  const nom = arr.join(' ') || nombre;

  const a1  = apPat?apPat[0]:'X';
  const a1v = apPat?primeraVocalInterna(apPat):'X';
  const a2  = apMat?apMat[0]:'X';
  const n1  = nom?nom[0]:'X';
  const base4 = limpiaInconveniente((a1+a1v+a2+n1).padEnd(4,'X'));

  const f=(fechaYMD||'').replace(/-/g,'');
  const yy=f.slice(2,4), mm=f.slice(4,6), dd=f.slice(6,8);

  const mapa='0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ Ñ';
  const tabla={}; for(let i=0;i<mapa.length;i++) tabla[mapa[i]]=i;
  const cadena=(apPat+' '+apMat+' '+nom).replace(/\s+/g,' ');
  let suma=0, prev=0;
  for(const ch of (' '+sinAcentos(cadena).toUpperCase())){const v=tabla[ch]??0; suma+=prev*v; prev=v;}
  const base=suma%1000, hc1=Math.floor(base/34), hc2=base%34;
  const dic34='0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ';
  const homoclave=dic34[hc1]+dic34[hc2];

  const val={' ':0,'0':0,'1':1,'2':2,'3':3,'4':4,'5':5,'6':6,'7':7,'8':8,'9':9,
    'A':10,'B':11,'C':12,'D':13,'E':14,'F':15,'G':16,'H':17,'I':18,'J':19,'K':20,'L':21,'M':22,
    'N':23,'&':24,'O':25,'P':26,'Q':27,'R':28,'S':29,'T':30,'U':31,'V':32,'W':33,'X':34,'Y':35,'Z':36,'Ñ':37};
  const parcial=base4+yy+mm+dd+homoclave;
  let sumaVer=0, peso=13; for(const ch of parcial) sumaVer+=(val[ch]??0)*(peso--);
  let dv=11-(sumaVer%11); dv=(dv===10)?'A':(dv===11?'0':String(dv));
  return parcial+dv;
}

// ============== CURP probable (SOLO 16) =======================
function curpProbable16(nombre, apPat, apMat, fechaYMD, generoHM, estadoTexto){
  nombre = limpiaNombre(nombre); 
  apPat  = limpiaNombre(apPat); 
  apMat  = limpiaNombre(apMat);

  generoHM = (generoHM||'').toUpperCase().startsWith('M') ? 'M' : 'H';
  const ent = ENTIDAD[limpiaNombre(estadoTexto)] || 'NE';

  nombre = filtra(nombre); apPat = filtra(apPat); apMat = filtra(apMat);

  let arr = nombre.split(' ').filter(Boolean);
  if (arr.length > 1 && NOMBRES_COMUNES.has(arr[0])) arr = arr.slice(1);
  const nom = arr.join(' ') || nombre;

  const a1  = apPat ? apPat[0] : 'X';
  const a1v = apPat ? primeraVocalInterna(apPat) : 'X';
  const a2  = apMat ? apMat[0] : 'X';
  const n1  = nom   ? nom[0]   : 'X';
  const cab4 = limpiaInconveniente((a1+a1v+a2+n1).replace(/Ñ/g,'X').padEnd(4,'X'));

  const f  = (fechaYMD||'').replace(/-/g,'');
  const yy = f.slice(2,4), mm = f.slice(4,6), dd = f.slice(6,8);
  const c1 = primeraConsonanteInterna(apPat).replace('Ñ','X');
  const c2 = primeraConsonanteInterna(apMat).replace('Ñ','X');
  const c3 = primeraConsonanteInterna(nom).replace('Ñ','X');

  return (cab4 + yy + mm + dd + generoHM + ent + c1 + c2 + c3).toUpperCase(); // 16
}

// ===============================================================
// === VALIDACIÓN RFC / CURP (pintado; sin SweetAlert) ===========
// ===============================================================
// ==== Normalizadores (RFC 13, CURP 18) ====
const _toUpper = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase();
const normalizaRFCInput  = v => _toUpper(v).replace(/[^A-Z0-9Ñ&]/g,'').slice(0,13);
const normalizaCURPInput = v => _toUpper(v).replace(/[^A-Z0-9]/g,'').slice(0,18);

// ==== Validadores (solo formato, sin DV) ====
function rfcEsValido(rfc){
  rfc = _toUpper(rfc||'').trim();
  if(!rfc) return { ok:false, reason:'empty' };
  const re13 = /^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/; // 13 exactos
  return { ok: re13.test(rfc), reason: re13.test(rfc) ? null : 'format' };
}

function curpEsValida18(curp){
  curp = _toUpper(curp||'').trim();
  if(!curp) return { ok:false, reason:'empty' };
  const re18 = /^[A-Z][AEIOU][A-Z]{2}\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])[HM](AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[0-9A-Z]\d$/;
  return { ok: re18.test(curp), reason: re18.test(curp) ? null : 'format' };
}

// --- UI helpers (sin SweetAlert) ---
function _pintaValidez(el, ok){
  if(!el) return;
  const empty = !String(el.value||'').trim();
  el.classList.remove('is-valid','is-invalid');
  if(empty){
    el.style.borderColor='#ced4da';
    el.style.boxShadow='none';
    return;
  }
  if(ok){
    el.classList.add('is-valid');
    el.style.borderColor='#28a745';
    el.style.boxShadow='0 0 0 2px rgba(40,167,69,.15)';
  }else{
    el.classList.add('is-invalid');
    el.style.borderColor='#dc3545';
    el.style.boxShadow='0 0 0 2px rgba(220,53,69,.15)';
  }
}

// Hints visibles debajo de inputs
function ensureHint(inputId, hintId){
  let el=document.getElementById(hintId);
  if(!el){
    el=document.createElement('div');
    el.id=hintId;
    el.className='form-text id-hint text-muted';
    el.style.marginTop='6px';
    el.style.fontWeight='600';
    el.style.fontSize='.98rem';
    const input=document.getElementById(inputId);
    input?.insertAdjacentElement('afterend', el);
  }
  return el;
}
function setHint(el, msg, tone='muted'){
  if(!el) return;
  el.textContent=msg||'';
  el.style.display = msg ? 'block' : 'none';
  el.className='form-text id-hint ' + (
    tone==='ok'?'text-success': tone==='warn'?'text-warning': tone==='error'?'text-danger':'text-muted'
  );
}

// Valida y muestra hints (RFC 13 / CURP 18)
function validarCamposIdentidad(){
  const rfcEl=document.getElementById('rfc');
  const curpEl=document.getElementById('curp');
  if(!rfcEl || !curpEl) return;

  // Normaliza en vivo (por si lo llamas manualmente)
  rfcEl.value  = normalizaRFCInput(rfcEl.value);
  curpEl.value = normalizaCURPInput(curpEl.value);

  const rfcV=(rfcEl.value||'').trim().toUpperCase();
  const curpV=(curpEl.value||'').trim().toUpperCase();

  const vR=rfcEsValido(rfcV);
  const vC=curpEsValida18(curpV);

  _pintaValidez(rfcEl, vR.ok);
  _pintaValidez(curpEl, vC.ok);

  const rfcHint=ensureHint('rfc','rfc-hint');
  const curpHint=ensureHint('curp','curp-hint');

  // RFC (13 requerido)
  if(rfcV.length===0) setHint(rfcHint,'','muted');
  else if(rfcV.length<13) setHint(rfcHint,`Faltan ${13-rfcV.length} caracteres para completar el RFC (13).`,'warn');
  else if(vR.ok) setHint(rfcHint,'RFC completo.','ok');
  else setHint(rfcHint,'Formato de RFC inválido (13).','error');

  // CURP (18 requerido)
  if(curpV.length===0) setHint(curpHint,'','muted');
  else if(curpV.length<18) setHint(curpHint,`Faltan ${18-curpV.length} caracteres para completar la CURP (18).`,'warn');
  else if(vC.ok) setHint(curpHint,'CURP completa.','ok');
  else setHint(curpHint,'Formato de CURP inválido (18).','error');
}


// ===============================================================
// ========== AUTOLLENADO RFC / CURP (siempre recalcula) =========
// ===============================================================
function normalizaFecha(fechaStr){
  const s=(fechaStr||'').trim();
  if(/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;             // yyyy-mm-dd
  const m=s.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);         // dd/mm/yyyy
  return m?`${m[3]}-${m[2]}-${m[1]}`:'';
}

function actualizarIdentificadores(){
  const nombre = document.getElementById('nombres')?.value || '';
  const apPat  = document.getElementById('apellido_paterno')?.value || '';
  const apMat  = document.getElementById('apellido_materno')?.value || '';
  const fechaR = document.getElementById('fecha_nacimiento')?.value || '';
  const genero = document.getElementById('genero')?.value || '';

  const edoSel   = document.getElementById('estado_nacimiento');
  const edoTexto = edoSel ? (edoSel.options?.[edoSel.selectedIndex]?.text || edoSel.value || '') : '';

  const rfcEl  = document.getElementById('rfc');
  const curpEl = document.getElementById('curp');

  const fecha = normalizaFecha(fechaR);

  if (fecha && nombre && apPat) {
    // RFC completo (13) como ya lo tenías
    const r = (rfcPersona(nombre, apPat, apMat, fecha) || '').toUpperCase();
    if (rfcEl) rfcEl.value = r;

    // CURP: genera base16 y respeta los 2 últimos que haya escrito el usuario
    if (genero && edoTexto && curpEl) {
      const base16  = (curpProbable16(nombre, apPat, apMat, fecha, genero, edoTexto) || '').toUpperCase();
      const actual  = (curpEl.value || '').toUpperCase();

      // Si el valor actual ya comienza con base16, conserva el sufijo escrito por el usuario
      let sufijo = actual.startsWith(base16) ? actual.slice(16) : '';
      sufijo = sufijo.replace(/[^A-Z0-9]/g,'').slice(0,2);   // máximo 2

      curpEl.value = base16 + sufijo; // ahora puedes llegar a 18
    }
  }

  validarCamposIdentidad();
}

// Listeners / límites
['nombres','apellido_paterno','apellido_materno','fecha_nacimiento','genero','estado_nacimiento'].forEach(id=>{
  const el = document.getElementById(id);
  if(!el) return;
  el.addEventListener('input',  actualizarIdentificadores);
  el.addEventListener('change', actualizarIdentificadores);
});

document.addEventListener('DOMContentLoaded', ()=>{
  const rfcEl  = document.getElementById('rfc');
  const curpEl = document.getElementById('curp');

  if (rfcEl) {
    rfcEl.setAttribute('maxlength','13');
    rfcEl.addEventListener('input', ()=>{
      const n = normalizaRFCInput ? normalizaRFCInput(rfcEl.value) : (rfcEl.value||'').toUpperCase().replace(/[^A-Z0-9Ñ&]/g,'').slice(0,13);
      if (rfcEl.value !== n) rfcEl.value = n;
      validarCamposIdentidad();
    });
    rfcEl.addEventListener('change', validarCamposIdentidad);
    rfcEl.addEventListener('blur',   validarCamposIdentidad);
  }

  if (curpEl) {
    curpEl.setAttribute('maxlength','18');     // ✅ permitir 18
    curpEl.removeAttribute('readonly');        // por si estaba bloqueado
    curpEl.disabled = false;

    const clamp = ()=>{
      const n = (typeof normalizaCURPInput === 'function')
        ? normalizaCURPInput(curpEl.value)     // asegúrate que esta func recorte a 18
        : (curpEl.value||'').toUpperCase().replace(/[^A-Z0-9]/g,'').slice(0,18);
      if (curpEl.value !== n) curpEl.value = n;
      validarCamposIdentidad();
    };
    ['input','change','blur'].forEach(ev=> curpEl.addEventListener(ev, clamp));
  }

  actualizarIdentificadores();
});

// Exponer helpers si los necesitas en otros módulos
window.rfcPersona = rfcPersona;
window.curpProbable16 = curpProbable16;
window.revalidarIdentidad = () => validarCamposIdentidad();
