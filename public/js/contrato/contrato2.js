
// ========= Helpers básicos =========
const $ = (id) => document.getElementById(id);
const pad2 = n => String(n).padStart(2,'0');

let modalidadSolicitud = '';

function nombreModalidadContrato(mod) {
  const m = String(mod || '').replace(/\s+/g, '').toUpperCase();

  switch (m) {
    case 'SEM_P10': return 'SEM PERSONAL 10';
    case 'P10':     return 'UNIPERSONAL 10';
    case 'P40':     return '40 RETROACTIVO';
    case 'P10_ORD': return 'PERSONAL 10 ORDINARIO';
    case 'P40_ORD': return 'PERSONAL 40 ORDINARIO';
    default:        return 'CRÉDITO';
  }
}

function aplicarNombreModalidadContrato() {
  const el = document.getElementById('out_modalidad');
  if (el) el.textContent = nombreModalidadContrato(modalidadSolicitud);
}

function getPublicBase(){
  const b = document.querySelector('base')?.href;
  if (b) return b.replace(/\/+$/, '/');

  const { origin, pathname } = window.location;
  const p = pathname.replace(/\/+$/, '/').toLowerCase();

  if (p.includes('/hp/public/')) {
    return `${origin}/hp/public/`;
  }

  if (p.includes('/sempiternal/public/')) {
    return `${origin}/sempiternal/public/`;
  }

  return `${origin}/`;
}
const PUBLIC = getPublicBase();

function getSolicitudId(){
  const qs = new URLSearchParams(location.search);
  return (
    qs.get('solicitud_id') ||
    sessionStorage.getItem('solicitud_id') ||
    qs.get('id') ||
    qs.get('folio') ||
    null
  );
}

// ========= Asesor: selección robusta en #sel_persona =========
// Saca el texto "humano" del objeto que venga del backend (sin [object Object])
function getAsesorTexto(asesor){
  if (!asesor) return '';
  // campos comunes
  const cands = [
    asesor.texto, asesor.nombre, asesor.razon_social, asesor.full_name, asesor.completo,
    asesor.display, asesor.label,
    // a veces viene anidado
    asesor.persona?.texto, asesor.persona?.nombre
  ].filter(Boolean);
  const txt = (cands[0] ?? '').toString().trim();
  return txt;
}

// Intenta seleccionar en el select por id y/o por texto. Si no existe, agrega "temporal".
function seleccionarAsesorEnSelect(asesor){
  const sel = $('sel_persona');
  if (!sel) return;

  // 1) Por ID (value exacto)
  if (asesor && asesor.id != null){
    const idStr = String(asesor.id);
    for (const opt of sel.options){
      if (String(opt.value) === idStr){ sel.value = idStr; break; }
    }
  }

  // 2) Por texto visible (normalizado)
  const norm = s => String(s||'').normalize('NFD').replace(/\p{Diacritic}/gu,'')
                     .toUpperCase().replace(/\s+/g,' ').trim();

  const texto = getAsesorTexto(asesor);
  if (texto){
    const target = norm(texto);
    let found = false;
    for (const opt of sel.options){
      if (norm(opt.text) === target){ sel.value = opt.value; found = true; break; }
    }
    if (!found){
      // Agrega opción temporal SOLO con string (nunca un objeto)
      const opt = document.createElement('option');
      opt.value = '';
      opt.text  = `${texto} (de solicitud)`;
      sel.insertBefore(opt, sel.firstChild);
      sel.value = opt.value;
    }
  }

  // Dispara eventos para que aplicarSeleccion() reaccione
  sel.dispatchEvent(new Event('change', { bubbles:true }));
  sel.dispatchEvent(new Event('input',  { bubbles:true }));
}

// ========= Cargar solicitud y pintar campos =========
async function cargarSolicitud(){
  const sid = getSolicitudId();
  if (!sid) return;

  const isNum = /^\d+$/.test(String(sid));
  const url = `${PUBLIC}app/controllers/obtener_datos/get_solicitud_por_id.php?${isNum ? 'solicitud_id' : 'folio'}=${encodeURIComponent(sid)}`;

  try{
    const res  = await fetch(url, { cache: 'no-store' });
    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch { console.warn('Respuesta no-JSON:', text.slice(0,200)); return; }

    if (!res.ok || !data.ok){
      console.warn('API', data);
      return;
    }

    const sol = data.solicitud || {};
    const cli = data.cliente   || {};
    const asesorObj = data.asesor || { texto: sol.atendido_por || '' };

    modalidadSolicitud = String(sol.contrato_modalidad || '').toUpperCase();
    datosAsesor = asesorObj;

    // ====== ASESOR ======
    const visor = $('asesor_nombre_view');
    if (visor) visor.value = getAsesorTexto(asesorObj) || '(sin asesor)';
    seleccionarAsesorEnSelect(asesorObj); // esto dispara aplicarSeleccion()

    // ====== MONTO ======
    const monto = sol.monto ?? sol.monto_solicitado ?? sol.monto_prestamo;
    if ($('m_monto') && monto != null){
      $('m_monto').value = Number(monto).toFixed(2);
      if (typeof window.aplicarMonto === 'function') window.aplicarMonto();
    }

    // ====== PLAZO ======
    const plazo = sol.plazo_meses ?? sol.plazo;
    if ($('plazo_meses') && plazo != null){
      $('plazo_meses').value = String(plazo);
      $('plazo_meses').dispatchEvent(new Event('change', { bubbles:true }));
      if (typeof window.updateMesesText === 'function') window.updateMesesText();
      if (typeof window.recalcularDevolucion === 'function') window.recalcularDevolucion();
    }

    // ====== MÉTODO ENTREGA (si lo mandas) ======
    if (sol.metodo_entrega && $('metodo_entrega')){
      $('metodo_entrega').value = sol.metodo_entrega;
      $('metodo_entrega').dispatchEvent(new Event('change', { bubbles:true }));
    }

    // ====== CLIENTE ======
    const nombre =
      (cli.nombre && cli.nombre.trim()) ||
      [cli.nombres, cli.apellido_paterno, cli.apellido_materno].filter(Boolean).join(' ').trim() ||
      (cli.razon_social || '').trim();

    if (nombre && $('m_prest_nombre')) $('m_prest_nombre').value = nombre;
    if (cli.rfc    && $('m_prest_rfc')) $('m_prest_rfc').value    = String(cli.rfc).toUpperCase();
    if ($('m_prest_email'))             $('m_prest_email').value  = cli.correo   || cli.email || '';
    if ($('m_prest_tel'))               $('m_prest_tel').value    = cli.telefono || cli.tel   || '';

    // Dirección multilinea robusta
    if ($('m_prest_dir')){
      if (cli.direccion_multilinea){
        $('m_prest_dir').value = cli.direccion_multilinea;
      }else{
        const lines = [];
        if (cli.direccion)    lines.push(cli.direccion);
        const l3 = [
          cli.colonia ? `Col. ${cli.colonia}` : '',
          cli.municipio || '',
          cli.estado || ''
        ].filter(Boolean).join(', ').replace(/,\s*,/g, ', ').trim();
        if (l3) lines.push(l3);
        if (cli.cp) lines.push(`C.P. ${cli.cp}`);
        $('m_prest_dir').value = lines.join('\n');
      }
    }

    if (typeof window.updateMesesText === 'function') window.updateMesesText();
const plazoMeses = document.getElementById('plazo_meses');
if (plazoMeses) plazoMeses.addEventListener('change', updateMesesText);

  }catch(err){
    console.error('Error leyendo solicitud:', err);
  }
}

// Lánzalo al cargar (si ya tienes otro DOMContentLoaded, puedes dejar sólo esta línea)
document.addEventListener('DOMContentLoaded', cargarSolicitud);




// Variable global para guardar los datos del asesor/prestamista
let datosAsesor = null;

// ========= Helpers básicos =========
const getById = (id) => document.getElementById(id);


function getPublicBase(){
  const b = document.querySelector('base')?.href;
  if (b) return b.replace(/\/+$/, '/');

  const { origin, pathname } = window.location;
  const p = pathname.replace(/\/+$/, '/').toLowerCase();

  if (p.includes('/hp/public/')) {
    return `${origin}/hp/public/`;
  }

  if (p.includes('/sempiternal/public/')) {
    return `${origin}/sempiternal/public/`;
  }

  return `${origin}/`;
}

// ========= Asesor: selección robusta en #sel_persona =========
// Saca el texto "humano" del objeto que venga del backend (sin [object Object])
function getAsesorTexto(asesor) {
  if (!asesor) return '';
  const cands = [
    asesor.texto, asesor.nombre, asesor.razon_social, asesor.full_name, asesor.completo,
    asesor.display, asesor.label,
    asesor.persona?.texto, asesor.persona?.nombre
  ].filter(Boolean);
  const txt = (cands[0] ?? '').toString().trim();
  return txt;
}

// Intenta seleccionar en el select por id y/o por texto. Si no existe, agrega "temporal".
function seleccionarAsesorEnSelect(asesor) {
  const sel = getById('sel_persona');
  if (!sel) return;

  // 1) Por ID (value exacto)
  if (asesor && asesor.id != null) {
    const idStr = String(asesor.id);
    for (const opt of sel.options) {
      if (String(opt.value) === idStr) {
        sel.value = idStr;
        break;
      }
    }
  }

  // 2) Por texto visible (normalizado)
  const norm = s => String(s || '').normalize('NFD').replace(/\p{Diacritic}/gu, '').toUpperCase().replace(/\s+/g, ' ').trim();
  const texto = getAsesorTexto(asesor);
  if (texto) {
    const target = norm(texto);
    let found = false;
    for (const opt of sel.options) {
      if (norm(opt.text) === target) {
        sel.value = opt.value;
        found = true;
        break;
      }
    }
    if (!found) {
      // Agrega opción temporal SOLO con string (nunca un objeto)
      const opt = document.createElement('option');
      opt.value = '';
      opt.text = `${texto} (de solicitud)`;
      sel.insertBefore(opt, sel.firstChild);
      sel.value = opt.value;
    }
  }

  // Dispara eventos para que aplicarSeleccion() reaccione
  sel.dispatchEvent(new Event('change', {
    bubbles: true
  }));
  sel.dispatchEvent(new Event('input', {
    bubbles: true
  }));
}

// ========= Cargar solicitud y pintar campos =========
async function cargarSolicitud() {
  const PUBLIC = getPublicBase();
  const sid = getSolicitudId();
  if (!sid) return;

  const isNum = /^\d+$/.test(String(sid));
  const url = `${PUBLIC}app/controllers/obtener_datos/get_solicitud_por_id.php?${isNum ? 'solicitud_id' : 'folio'}=${encodeURIComponent(sid)}`;

  try {
    const res = await fetch(url, {
      cache: 'no-store'
    });
    const text = await res.text();
    let data;
    try {
      data = JSON.parse(text);
    } catch {
      console.warn('Respuesta no-JSON:', text.slice(0, 200));
      return;
    }

    if (!res.ok || !data.ok) {
      console.warn('API', data);
      return;
    }

    const sol = data.solicitud || {};
    const cli = data.cliente || {};
    const asesorObj = data.asesor || {
      texto: sol.atendido_por || ''
    };
    modalidadSolicitud = String(sol.contrato_modalidad || '').toUpperCase();

    // Guarda los datos del asesor en la variable global
    datosAsesor = asesorObj;

    // ====== ASESOR ======
    const visor = getById('asesor_nombre_view');
    if (visor) visor.value = getAsesorTexto(asesorObj) || '(sin asesor)';
    seleccionarAsesorEnSelect(asesorObj);

    // ====== MONTO ======
    const monto = sol.monto ?? sol.monto_solicitado ?? sol.monto_prestamo;
    if (getById('m_monto') && monto != null) {
      getById('m_monto').value = Number(monto).toFixed(2);
      if (typeof window.aplicarMonto === 'function') window.aplicarMonto();
    }

    // ====== PLAZO ======
    const plazo = sol.plazo_meses ?? sol.plazo;
    if (getById('plazo_meses') && plazo != null) {
      getById('plazo_meses').value = String(plazo);
      getById('plazo_meses').dispatchEvent(new Event('change', {
        bubbles: true
      }));
      if (typeof window.updateMesesText === 'function') window.updateMesesText();
      if (typeof window.recalcularDevolucion === 'function') window.recalcularDevolucion();
    }

    // ====== MÉTODO ENTREGA (si lo mandas) ======
    if (sol.metodo_entrega && getById('metodo_entrega')) {
      getById('metodo_entrega').value = sol.metodo_entrega;
      getById('metodo_entrega').dispatchEvent(new Event('change', {
        bubbles: true
      }));
    }

    // ====== CLIENTE ======
    const nombre =
      (cli.nombre && cli.nombre.trim()) ||
      [cli.nombres, cli.apellido_paterno, cli.apellido_materno].filter(Boolean).join(' ').trim() ||
      (cli.razon_social || '').trim();

    if (nombre && getById('m_prest_nombre')) getById('m_prest_nombre').value = nombre;
    if (cli.rfc && getById('m_prest_rfc')) getById('m_prest_rfc').value = String(cli.rfc).toUpperCase();
    if (getById('m_prest_email')) getById('m_prest_email').value = cli.correo || cli.email || '';
    if (getById('m_prest_tel')) getById('m_prest_tel').value = cli.telefono || cli.tel || '';

    // Dirección multilinea robusta
    if (getById('m_prest_dir')) {
      if (cli.direccion_multilinea) {
        getById('m_prest_dir').value = cli.direccion_multilinea;
      } else {
        const lines = [];
        if (cli.direccion) lines.push(cli.direccion);
        const l3 = [
          cli.colonia ? `Col. ${cli.colonia}` : '',
          cli.municipio || '',
          cli.estado || ''
        ].filter(Boolean).join(', ').replace(/,\s*,/g, ', ').trim();
        if (l3) lines.push(l3);
        if (cli.cp) lines.push(`C.P. ${cli.cp}`);
        getById('m_prest_dir').value = lines.join('\n');
      }
    }
    // Propaga nombre del producto/modalidad
aplicarNombreModalidadContrato();
    // Propaga todos los datos cargados al contrato
    propagarTodo();

  } catch (err) {
    console.error('Error leyendo solicitud:', err);
  }
}

// ===== util =====
const id = (x) => document.getElementById(x);

// Propaga todos los campos al contrato
function propagarTodo() {
  window.aplicarSeleccion?.();
  window.aplicarPrestatarioManual?.();
  window.aplicarMonto?.();
  window.setFechasHoy?.();
  window.recalcularDevolucion?.();
  window.updateMesesText?.();
  window.actualizarMetodoEntrega?.();
  window.aplicarDatosDelPrestador?.();
  window.aplicarDatosBancarios?.();
}

// Muestra las páginas del contrato y recalibra los pads de firma
function mostrarContrato() {
  document.querySelectorAll('.page').forEach(p => p.classList.add('show'));
  requestAnimationFrame(() => {
    document.querySelectorAll('.sig-pad canvas').forEach(cv => {
      const r = cv.getBoundingClientRect();
      if (!r.width || !r.height) return;
      const dpr = Math.max(window.devicePixelRatio || 1, 1);
      const ctx = cv.getContext('2d');
      cv.width = Math.round(r.width * dpr);
      cv.height = Math.round(r.height * dpr);
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, r.width, r.height);
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.strokeStyle = '#000';
    });
    window.dispatchEvent(new Event('resize'));
  });
}

// Validación mínima del formulario
function camposOk() {
  const req = k => document.getElementById(k)?.value.trim();
  const campos = ['m_prest_nombre', 'm_prest_rfc', 'm_prest_dir', 'm_prest_email', 'm_prest_tel', 'm_monto'];
  return campos.every(k => !!req(k));
}

// fallback por si no tienes definida generar()
if (typeof window.generar !== 'function') {
  window.generar = function() {};
}

// --- FUNCIONES PARA RELLENAR LOS DATOS ---
function aplicarPrestatarioManual() {
  const nombre = document.getElementById('m_prest_nombre').value;
  const rfc = document.getElementById('m_prest_rfc').value;
  const direccion = document.getElementById('m_prest_dir').value;
  const email = document.getElementById('m_prest_email').value;
  const tel = document.getElementById('m_prest_tel').value;

  const prestatarioNombrePrincipal = document.getElementById('out_prestatario');
  if (prestatarioNombrePrincipal) {
    prestatarioNombrePrincipal.textContent = nombre;
  }

  const rfcPrincipal = document.getElementById('out_rfc_prestatario');
  if (rfcPrincipal) {
    rfcPrincipal.textContent = rfc;
  }

  const dirPrincipal = document.getElementById('out_dir_prestatario');
  if (dirPrincipal) {
    dirPrincipal.textContent = direccion;
  }

  const prestatarioNombreNotif = document.getElementById('out_prestatario_nombre_ntf');
  if (prestatarioNombreNotif) {
    prestatarioNombreNotif.textContent = nombre;
  }

  const prestatarioEmail = document.getElementById('out_prestatario_email');
  if (prestatarioEmail) {
    prestatarioEmail.textContent = email;
  }

  const prestatarioTel = document.getElementById('out_prestatario_tel');
  if (prestatarioTel) {
    prestatarioTel.textContent = tel;
  }

  const firmaPrestatario = document.getElementById('firma_prestatario_nombre');
  if (firmaPrestatario) {
    firmaPrestatario.textContent = nombre;
  }
}


// ===== Utilidad: número -> letras (MX) =====
function numeroALetrasMX(valor){
  const n = Number(String(valor).replace(/[^0-9.-]/g,'')); // limpia $ y comas
  if (!isFinite(n)) return '';

  const entero   = Math.floor(Math.abs(n));
  const centavos = Math.round((Math.abs(n) - entero) * 100);

  const UN = ['','uno','dos','tres','cuatro','cinco','seis','siete','ocho','nueve'];
  const D10_15 = ['diez','once','doce','trece','catorce','quince'];
  const DE = ['','diez','veinte','treinta','cuarenta','cincuenta','sesenta','setenta','ochenta','noventa'];
  const CE = ['','cien','doscientos','trescientos','cuatrocientos','quinientos','seiscientos','setecientos','ochocientos','novecientos'];

  function centenas(num){
    if (num === 0) return '';
    if (num === 100) return 'cien';
    const c = Math.floor(num/100), d = Math.floor((num%100)/10), u = num%10;
    const pref = c ? (c===1? 'ciento' : CE[c]) : '';
    if (d === 0) return [pref, UN[u]].filter(Boolean).join(' ').trim();
    if (d === 1){
      if (u <= 5) return [pref, D10_15[u]].filter(Boolean).join(' ').trim();
      return [pref, 'dieci' + UN[u]].filter(Boolean).join(' ').trim();
    }
    if (d === 2){
      return [pref, u===0 ? 'veinte' : 'veinti' + UN[u]].filter(Boolean).join(' ').trim();
    }
    return [pref, DE[d] + (u ? ' y ' + UN[u] : '')].filter(Boolean).join(' ').trim();
  }

  function seccion(num, div, sing, plur){
    const cant = Math.floor(num/div);
    const resto = num - cant*div;
    let t = '';
    if (cant > 0){
      if (div === 1_000_000){
        t = (cant===1? 'un '+sing : numero(cant)+' '+plur);
      } else { // miles
        t = (cant===1? sing : numero(cant)+' '+plur);
      }
    }
    return {t, resto};
  }

  function numero(num){
    if (num === 0) return 'cero';
    let parts = [];
    let sec = seccion(num, 1_000_000, 'millón', 'millones');
    if (sec.t) parts.push(sec.t);
    num = sec.resto;

    sec = seccion(num, 1000, 'mil', 'mil');
    if (sec.t) parts.push(sec.t);
    num = sec.resto;

    if (num>0) parts.push(centenas(num));
    return parts.join(' ').replace(/\s+/g,' ').trim();
  }

  // texto principal
  const base = numero(entero)
    .replace(/\buno\b(?=\s|$)/g,'un')   // "un" antes de sustantivo
    .replace(/\bun mil\b/g,'mil');      // "mil" en lugar de "un mil"

  const moneda = (entero===1? 'peso' : 'pesos');
  const cents  = String(centavos).padStart(2,'0') + '/100';

  return `${base} ${moneda} ${cents} M.N.`.toUpperCase();
}


// ===== Tu función actualizada =====
function aplicarMonto() {
  const input = document.getElementById('m_monto');
  if (!input) return;

  // Permite $ y comas en el input
  const bruto = (input.value || '').toString();
  const monto = Number(bruto.replace(/[^0-9.,]/g,'').replace(/,/g,''));
  if (!isFinite(monto)) return;

  // Formato numérico y letras
  const montoFormateado = monto.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const montoEnLetras   = numeroALetrasMX(monto) || '';

  // Pinta en el contrato
  document.querySelectorAll('.out_monto_num').forEach(el => el.textContent = `$ ${montoFormateado}`);
  document.querySelectorAll('.out_monto_letra').forEach(el => el.textContent = `(${montoEnLetras})`);
}

function actualizarMetodoEntrega() {
  const metodo = document.getElementById('metodo_entrega').value;
  const outMetodoEntrega = document.getElementById('out_metodo_entrega');
  if (outMetodoEntrega) {
    outMetodoEntrega.textContent = metodo;
  }
}

function setFechasHoy() {
  const hoy = new Date();
  const fechaStr = hoy.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });

  const fechaElements = ['out_fecha', 'out_fecha_entrega', 'out_fecha_actual'];
  fechaElements.forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.textContent = fechaStr;
    }
  });
}

function recalcularDevolucion() {
  const plazo = document.getElementById('plazo_meses').value;
  const fechaActual = new Date();
  fechaActual.setMonth(fechaActual.getMonth() + parseInt(plazo, 10));
  const fechaDevolucionStr = fechaActual.toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  });
  const outFechaDevol = document.getElementById('out_fecha_devol');
  if (outFechaDevol) {
    outFechaDevol.textContent = fechaDevolucionStr;
  }
}


// Pinta los meses de plazo en las salidas del contrato
// Pinta los meses de plazo en las salidas del contrato
window.updateMesesText = function updateMesesText() {
  // lee del input/select principal
  const el = document.getElementById('plazo_meses');
  let plazo = el ? parseInt(el.value, 10) : NaN;

  // fallback: por si en algunos casos tienes el plazo en datos ya cargados
  if (!Number.isFinite(plazo)) {
    const fromSession = (window.solicitudDatos?.plazo ?? window.solicitudDatos?.plazo_meses);
    plazo = parseInt(fromSession, 10);
  }

  // helpers
  const setTxt = (id, val) => {
    const node = document.getElementById(id);
    if (node) node.textContent = (val ?? '').toString();
  };

  if (Number.isFinite(plazo)) {
    const p = plazo;
    setTxt('out_meses_interes',    p);
    setTxt('out_meses_financiado', p);
    setTxt('out_min_meses',        p);      // mínimo = plazo
    setTxt('out_max_meses',        p + 2);  // máximo = plazo + 2
  } else {
    // limpiar si no hay valor válido
    setTxt('out_meses_interes',    '');
    setTxt('out_meses_financiado', '');
    setTxt('out_min_meses',        '');
    setTxt('out_max_meses',        '');
  }
};



// --- FUNCIÓN PARA LLENAR LOS DATOS DEL PRESTADOR ---
// --- FUNCIÓN PARA LLENAR LOS DATOS DEL PRESTADOR ---
function aplicarDatosDelPrestador() {
  if (!datosAsesor) return;

  const nombreAsesor    = datosAsesor.nombre || datosAsesor.texto || 'No disponible';
  const rolAsesor       = datosAsesor.rol || 'Representante legal';
  const emailAsesor     = datosAsesor.email || datosAsesor.correo || 'No disponible';
  const telAsesor       = datosAsesor.telefono
                       ?? datosAsesor.tel
                       ?? datosAsesor.telefono1
                       ?? datosAsesor.telefono_contacto
                       ?? 'No disponible';
  const rfcAsesor       = (datosAsesor.rfc || 'No disponible').toString().toUpperCase().trim();
  const direccionAsesor = (datosAsesor.direccion || 'No disponible').toString().trim();

  const datosPrestador = {
    razon_social: datosAsesor.razon_social || 'CIP FINANCIAL SA DE CV',
    rfc: rfcAsesor,
    direccion: direccionAsesor,
    rep_nombre: nombreAsesor,
    rol: rolAsesor,
    rep_rfc: rfcAsesor,
    rep_direccion: direccionAsesor,
    ntf_nombre: nombreAsesor,
    ntf_email: emailAsesor,
    ntf_tel: String(telAsesor),
    firma_nombre: nombreAsesor
  };

  // Empresa
  const outPrestador = document.getElementById('out_prestador');
  if (outPrestador) outPrestador.textContent = datosPrestador.razon_social;

  const outRfcPrestador = document.getElementById('out_rfc_prestador');
  if (outRfcPrestador) outRfcPrestador.textContent = datosPrestador.rfc;

  const outDirPrestador = document.getElementById('out_dir_prestador');
  if (outDirPrestador) outDirPrestador.textContent = datosPrestador.direccion;

  // Representante
  const outRepNombre = document.getElementById('out_rep_nombre');
  if (outRepNombre) outRepNombre.textContent = datosPrestador.rep_nombre;

  const tituloRol = document.getElementById('titulo_rol');
  if (tituloRol) tituloRol.textContent = datosPrestador.rol;

  // ✅ NUEVOS DESTINOS que pediste
  const outRepRfc = document.getElementById('out_rep_rfc');
  if (outRepRfc) outRepRfc.textContent = datosPrestador.rep_rfc;

  const outRepDir = document.getElementById('out_rep_dir');
  if (outRepDir) outRepDir.textContent = datosPrestador.rep_direccion;

  // Compatibilidad con IDs que ya tenías en otras páginas
  const outRfcRepresentante = document.getElementById('out_rfc_representante');
  if (outRfcRepresentante) outRfcRepresentante.textContent = datosPrestador.rep_rfc;

  const outDireccionRepresentante = document.getElementById('out_direccion_representante');
  if (outDireccionRepresentante) outDireccionRepresentante.textContent = datosPrestador.rep_direccion;

  // Contacto
  const outPrestadorNombreNtf = document.getElementById('out_prestador_nombre_ntf');
  if (outPrestadorNombreNtf) outPrestadorNombreNtf.textContent = datosPrestador.ntf_nombre;

  const outPrestEmail = document.getElementById('out_prest_email');
  if (outPrestEmail) outPrestEmail.textContent = datosPrestador.ntf_email;

  const outPrestTel = document.getElementById('out_prest_tel');
  if (outPrestTel) outPrestTel.textContent = datosPrestador.ntf_tel;
}


// --- FUNCIÓN PARA LOS DATOS BANCARIOS ESTÁTICOS ---
// Helpers (pueden ir arriba del archivo)
const formatClabe  = s => String(s || '').replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim();
const formatCuenta = s => String(s || '').replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim();

// Mapa simple de bancos por código CLABE (3 dígitos). Agrega/edita según necesites.
const CLABE_BANKS = {
  '002': 'Citibanamex',
  '012': 'BBVA',
  '014': 'Santander',
  '021': 'HSBC',
  '030': 'Banco del Bajío',
  '036': 'Inbursa',
  '044': 'Scotiabank',
  '058': 'Banregio',
  '059': 'Invex',
  '060': 'Bansi',
  '062': 'Afirme',
  '072': 'Banorte',
  '127': 'Banco Azteca',
  '137': 'BanCoppel'
  // agrega más si lo requieres
};

function getBancoPorClabe(clabe) {
  const soloDigitos = String(clabe || '').replace(/\D/g,'');
  if (soloDigitos.length < 3) return '';
  const code = soloDigitos.slice(0, 3);
  return CLABE_BANKS[code] || '';
}

// === Rellena los datos bancarios del prestador/asesor ===
function aplicarDatosBancarios() {
  const clabeRaw  = (datosAsesor?.clabe  ?? '').toString().trim();
  const cuentaRaw = (datosAsesor?.cuenta ?? '').toString().trim();

  const bancoDetectado = getBancoPorClabe(clabeRaw);

  const outBanco  = document.getElementById('out_banco');
  const outCuenta = document.getElementById('out_cuenta');
  const outClabe  = document.getElementById('out_clabe');

  if (outBanco)  outBanco.textContent  = bancoDetectado || '—';
  if (outCuenta) outCuenta.textContent = cuentaRaw ? formatCuenta(cuentaRaw) : '—';
  if (outClabe)  outClabe.textContent  = clabeRaw  ? formatClabe(clabeRaw)   : '—';
}


function aplicarSeleccion() {
  const selectAsesor = document.getElementById('sel_persona');
  if (!selectAsesor) return;
  const asesorNombre = selectAsesor.options[selectAsesor.selectedIndex].text;
  const camposAsesor = ['out_rep_nombre', 'out_prest_nombre_ntf', 'out_nombre_simple'];
  camposAsesor.forEach(id => {
    const elemento = document.getElementById(id);
    if (elemento) {
      elemento.textContent = asesorNombre;
    }
  });
  const tituloRol = document.getElementById('titulo_rol');
  if (tituloRol) {
    tituloRol.textContent = "Representante legal";
  }
}

// ---- Wiring de botones y eventos ----
document.addEventListener('DOMContentLoaded', () => {
  const btnGen = getById('btn_generar');
  const btnGuardar = getById('btn_guardar');
  const btnImprimir = getById('btn_imprimir');

  // Dispara el proceso de carga y llenado al iniciar
  cargarSolicitud();

  btnGen?.addEventListener('click', (e) => {
    e.preventDefault();
    if (!camposOk()) {
      console.warn('Por favor, completa todos los campos del formulario.');
      return;
    }
    propagarTodo();
    window.generar?.();
    mostrarContrato();
    if (btnGuardar) btnGuardar.style.display = '';
    btnGen.textContent = 'Contrato generado (firme para guardar)';
  });

  btnImprimir?.addEventListener('click', (e) => {
    e.preventDefault();
    propagarTodo();
    window.generar?.();
    mostrarContrato();
    window.print();
  });

  window.addEventListener('beforeprint', () => {
    propagarTodo();
    window.generar?.();
  });

  // Agrega listeners para que los cambios en el formulario se reflejen en el contrato
  const plazoMeses = document.getElementById('plazo_meses');
  if (plazoMeses) plazoMeses.addEventListener('change', recalcularDevolucion);

  const metodoEntrega = document.getElementById('metodo_entrega');
  if (metodoEntrega) metodoEntrega.addEventListener('change', actualizarMetodoEntrega);

  const selPersona = document.getElementById('sel_persona');
  if (selPersona) selPersona.addEventListener('change', aplicarSeleccion);

  const mPrestNombre = document.getElementById('m_prest_nombre');
  if (mPrestNombre) mPrestNombre.addEventListener('input', aplicarPrestatarioManual);

  const mMonto = document.getElementById('m_monto');
  if (mMonto) mMonto.addEventListener('input', aplicarMonto);
});
// ===== Firma digital en canvas con Pointer Events =====
document.addEventListener('DOMContentLoaded', () => {

  const pads = document.querySelectorAll('.sig-pad');

  pads.forEach(pad => {
    const canvas     = pad.querySelector('canvas');
    const clearBtn   = pad.querySelector('.sig-clear');
    const saveBtn    = pad.querySelector('.sig-save');
    const outputDiv  = pad.querySelector('.sig-output');

    const ctx = canvas.getContext('2d', { willReadFrequently: true });

    // ===== Escalado correcto por DPR =====
    function resizeCanvasToDPR() {
      const dpr  = Math.max(window.devicePixelRatio || 1, 1);
      const rect = canvas.getBoundingClientRect(); // tamaño CSS visible
      // Tamaño interno en píxeles reales
      canvas.width  = Math.round(rect.width * dpr);
      canvas.height = Math.round(rect.height * dpr);
      // Normaliza el sistema de coordenadas a unidades CSS
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      // Fondo blanco
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, rect.width, rect.height);
      // Estilo por defecto
      ctx.lineCap   = 'round';
      ctx.lineJoin  = 'round';
      ctx.strokeStyle = '#000';
    }

    // Llamada inicial + al redimensionar/orientación
    const initResize = () => { resizeCanvasToDPR(); };
    initResize();
    window.addEventListener('resize', initResize);
    window.addEventListener('orientationchange', initResize);

    // ===== Dibujo con Pointer Events (pluma/dedo/mouse) =====
    let drawing = false;
    let last = { x: 0, y: 0 };

    // Convierte evento -> coords en unidades CSS del canvas
    const getPoint = (e) => {
      const rect = canvas.getBoundingClientRect();
      return {
        x: (e.clientX - rect.left),
        y: (e.clientY - rect.top),
        p: (typeof e.pressure === 'number' && e.pressure > 0 ? e.pressure : 1) // presión (1 si no hay)
      };
    };

    // Sencillo suavizado por interpolación lineal
    function drawTo(pt, pressure) {
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(pt.x, pt.y);

      // Grosor base (ajústalo). Multiplicamos por presión si hay pluma.
      const base = 2.2;
      ctx.lineWidth = base * pressure;
      ctx.stroke();

      last = pt;
    }

    function pointerDown(e) {
      if (e.pointerType === 'touch') e.preventDefault(); // evita scroll
      canvas.setPointerCapture(e.pointerId);
      drawing = true;
      last = getPoint(e);
    }

    function pointerMove(e) {
      if (!drawing) return;
      const pt = getPoint(e);
      drawTo(pt, pt.p);
    }

    function pointerUp(e) {
      drawing = false;
      try { canvas.releasePointerCapture(e.pointerId); } catch(_) {}
    }

    // Eventos de puntero (unifican mouse/touch/pen)
    canvas.addEventListener('pointerdown', pointerDown);
    canvas.addEventListener('pointermove', pointerMove);
    canvas.addEventListener('pointerup', pointerUp);
    canvas.addEventListener('pointercancel', pointerUp);
    canvas.addEventListener('pointerleave', pointerUp);

    // ===== Botones =====
    clearBtn?.addEventListener('click', () => {
      resizeCanvasToDPR();           // limpia respetando DPR
      if (outputDiv) outputDiv.innerHTML = '';
    });

    saveBtn?.addEventListener('click', () => {
      // Exporta exactamente lo que ves (ya está escalado correctamente)
      const dataURL = canvas.toDataURL('image/png');
      if (outputDiv) {
        outputDiv.innerHTML = `<img src="${dataURL}" alt="Firma" style="max-width:100%;height:auto;">`;
      }
      // Si necesitas enviar al backend:
      // fetch('guardar_firma.php', { method:'POST', body: JSON.stringify({firma_base64: dataURL}) })
    });

  });
});
// ===== Mostrar/ocultar campos de transferencia =====
document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('metodo_entrega');
  const caja = document.getElementById('transferencia_fields');
  if (!sel || !caja) return;
  const sync = () => caja.classList.toggle('hidden', sel.value !== 'transferencia');
  sel.addEventListener('change', sync);
  sync();
});
// ===== Formato y validación de CLABE/Cuenta, detección de banco =====
(function(){
  // ===== Utilidades =====
  const formatClabe  = (typeof window.formatClabe  === 'function')
    ? window.formatClabe
    : s => String(s||'').replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim();

  const formatCuenta = (typeof window.formatCuenta === 'function')
    ? window.formatCuenta
    : s => String(s||'').replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim();

  const CLABE_BANKS = (window.CLABE_BANKS) || {
    '002':'Citibanamex','012':'BBVA','014':'Santander','021':'HSBC',
    '030':'Banco del Bajío','036':'Inbursa','044':'Scotiabank',
    '058':'Banregio','059':'Invex','060':'Bansi','062':'Afirme',
    '072':'Banorte','127':'Banco Azteca','137':'BanCoppel'
  };

  const BIN_BANKS = (window.BIN_BANKS) || {
    '4555':'BBVA','5579':'BBVA','5489':'Citibanamex','4391':'Citibanamex',
    '4023':'Santander','5204':'Santander','4893':'Banorte','5493':'Banorte',
    '4766':'HSBC','5573':'Scotiabank','4988':'Banco Azteca','4029':'BanCoppel'
  };

  function esClabeValida(clabe){
    const s = String(clabe||'').replace(/\D/g,'');
    if (s.length !== 18) return false;
    const pesos = [3,7,1];
    let suma = 0;
    for (let i=0;i<17;i++){
      suma += ((+s[i]) * pesos[i % 3]) % 10;
    }
    const digito = (10 - (suma % 10)) % 10;
    return digito === +s[17];
  }

  function detectarBanco(numero){
    const raw = String(numero||'').replace(/\D/g,'');
    if (!raw) return '';
    if (raw.length === 18){
      const code = raw.slice(0,3);
      return CLABE_BANKS[code] || '';
    }
    const bin6 = raw.slice(0,6);
    const bin4 = raw.slice(0,4);
    return BIN_BANKS[bin6] || BIN_BANKS[bin4] || '';
  }

  function actualizarOutMetodo(){
    const selMetodo  = document.getElementById('metodo_entrega');
    const out        = document.getElementById('out_metodo_entrega');
    const inputBanco = document.getElementById('m_prest_banco');
    const inputNum   = document.getElementById('m_prest_clabe');
    if (!selMetodo || !out) return;

    const metodo = selMetodo.value || '';
    if (metodo === 'transferencia') {
      const rawNum   = (inputNum?.value || '').replace(/\D/g,'');
      const formNum  = rawNum.length === 18 ? formatClabe(rawNum) : formatCuenta(rawNum);
      const etiqueta = rawNum.length === 18 ? 'CLABE' : (rawNum ? 'Cuenta/Tarjeta' : '');
      const bancoInput  = (inputBanco?.value || '').trim();
      const bancoDetect = detectarBanco(rawNum);
      const banco = bancoInput || bancoDetect;

      // Construcción sin puntos y con spans para poder estilarlos
      const partes = ['Transferencia'];
      if (banco) partes.push(`<span class="bank">${banco}</span>`);
      if (etiqueta && formNum) {
        partes.push(`<span class="acct-label">${etiqueta}:</span> <span class="acct-number">${formNum}</span>`);
      }
      let html = partes.join(' ');
      if (rawNum.length === 18 && !esClabeValida(rawNum)) {
        html += ' <span class="acct-label">(CLABE no válida)</span>';
      }

      out.innerHTML = html || 'Transferencia';
      return;
    }

    if (metodo === 'efectivo') { out.textContent = 'Efectivo'; return; }
    if (metodo === 'cheque')   { out.textContent = 'Cheque nominativo'; return; }
    out.textContent = '—';
  }

  document.addEventListener('DOMContentLoaded', () => {
    const selMetodo  = document.getElementById('metodo_entrega');
    const inputBanco = document.getElementById('m_prest_banco');
    const inputNum   = document.getElementById('m_prest_clabe');

    // Si el usuario escribe en "Banco", ya no lo sobreescribimos
    inputBanco?.addEventListener('input', () => {
      inputBanco.dataset.userEdited = '1';
    });

    function syncFormatoYBanco(){
      if (!inputNum) return;
      const raw = (inputNum.value || '').replace(/\D/g,'');
      let banco = '';

      if (raw.length === 18){
        inputNum.value = formatClabe(raw);
        banco = detectarBanco(raw);
        if (esClabeValida(raw)){
          inputNum.classList.remove('is-invalid');
          inputNum.classList.add('is-valid');
        } else {
          inputNum.classList.remove('is-valid');
          inputNum.classList.add('is-invalid');
        }
      } else {
        inputNum.value = formatCuenta(raw);
        inputNum.classList.remove('is-valid','is-invalid');
        banco = detectarBanco(raw);
      }

      // === Autollenar el cuadro "Banco (opcional)" ===
      if (inputBanco && inputBanco.dataset.userEdited !== '1') {
        inputBanco.value = banco || '';
      }

      // Actualiza el texto del contrato
      actualizarOutMetodo();
    }

    selMetodo?.addEventListener('change', actualizarOutMetodo);
    inputNum?.addEventListener('input',  syncFormatoYBanco);
    inputNum?.addEventListener('blur',   syncFormatoYBanco);
    inputBanco?.addEventListener('blur', actualizarOutMetodo);

    // Primera pasada
    syncFormatoYBanco();
  });

  // Si tienes propagarTodo(), engancha la actualización
  if (typeof window.propagarTodo === 'function') {
    const _orig = window.propagarTodo;
    window.propagarTodo = function(){
      _orig?.();
      try { document.getElementById('m_prest_clabe') && document.getElementById('m_prest_clabe').dispatchEvent(new Event('blur')); } catch(_){}
      try { actualizarOutMetodo(); } catch(_){}
    };
  }
})();

(function(){
  // Muestra/oculta cualquier nodo con .mod-only según data-mod="10"|"40"
  function aplicarVisibilidadPorModalidad(mod){
    document.querySelectorAll('.mod-only').forEach(el=>{
      el.classList.toggle('hidden', el.dataset.mod !== mod);
    });
  }

function syncModalidad() {
  const out = document.getElementById('out_modalidad');
  const sel = document.getElementById('tipo_contrato');

  const modalidad = String(modalidadSolicitud || '').replace(/\s+/g, '').toUpperCase();

  // Texto visible del título del contrato
  const nombre = nombreModalidadContrato(modalidad);
  if (out) out.textContent = nombre;

  // Modalidad simple para bloques visuales (10 o 40)
  let modSimple = '40';

  if (['P10', 'SEM_P10', 'P10_ORD'].includes(modalidad)) {
    modSimple = '10';
  } else if (['P40', 'P40_ORD'].includes(modalidad)) {
    modSimple = '40';
  }

  // Sincroniza el select visual si existe
  if (sel) {
    sel.value = modSimple;
  }

  // Mostrar/ocultar bloques por modalidad
  aplicarVisibilidadPorModalidad(modSimple);

  // Persistencia opcional
  try {
    sessionStorage.setItem('tipo_contrato', modSimple);
  } catch (_) {}
}
  document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('tipo_contrato');

    // Restaura si ya hay valor guardado
    try {
      const saved = sessionStorage.getItem('tipo_contrato');
      if (sel && (saved === '10' || saved === '40')) sel.value = saved;
    } catch(_){}

    sel?.addEventListener('change', syncModalidad);
    syncModalidad(); // primera pasada
  });

  // Si tienes una función global que propaga todo, la enganchamos
  if (typeof window.propagarTodo === 'function') {
    const _orig = window.propagarTodo;
    window.propagarTodo = function(){
      _orig?.();
      try {
        const sel = document.getElementById('tipo_contrato');
        if (sel){
          // sincroniza por si el UI llenó datos antes
          sel.dispatchEvent(new Event('change'));
        }
      } catch(_){}
    };
  }
})();


(function(){
  // Util: debounce simple para no recalcular 20 veces
  function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; }

  function numerarPaginas(){
    const pages = document.querySelectorAll('.page');
    if (!pages.length) return;
    const total = pages.length;
    pages.forEach((p, i) => {
      let slot = p.querySelector('.page-num');
      if (!slot) {
        slot = document.createElement('div');
        slot.className = 'page-num';
        p.appendChild(slot);
      }
      // Formato: "Página X de Y"
      slot.textContent = `Página ${i + 1} de ${total}`;
    });
  }

  const safeNumerar = debounce(numerarPaginas, 50);

  // 1) Cuando el DOM está listo
  document.addEventListener('DOMContentLoaded', safeNumerar);

  // 2) Cuando todo cargó (por si .page llega tarde)
  window.addEventListener('load', safeNumerar);

  // 3) Antes de imprimir (asegura numerado actualizado)
  window.addEventListener('beforeprint', numerarPaginas);

  // 4) Si tienes botones que muestran el contrato, vuelve a numerar
  document.getElementById('btn_generar')?.addEventListener('click', safeNumerar);
  document.getElementById('btn_imprimir')?.addEventListener('click', safeNumerar);

  // 5) Observa cambios en el DOM (cuando agregas .show o insertas páginas)
  const mo = new MutationObserver(safeNumerar);
  mo.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });

  // 6) Si existen funciones globales, engánchate
  if (typeof window.mostrarContrato === 'function') {
    const _m = window.mostrarContrato;
    window.mostrarContrato = function(){ _m?.(); safeNumerar(); };
  }
  if (typeof window.propagarTodo === 'function') {
    const _p = window.propagarTodo;
    window.propagarTodo = function(){ _p?.(); safeNumerar(); };
  }
})();


// ===== Guardar/recuperar firmas en el servidor =====
(function () {
  /* ================= Helpers ================= */
function getPublicBase(){
  const b = document.querySelector('base')?.href;
  if (b) return b.replace(/\/+$/, '/');

  const { origin, pathname } = window.location;
  const p = pathname.replace(/\/+$/, '/').toLowerCase();

  if (p.includes('/hp/public/')) {
    return `${origin}/hp/public/`;
  }

  if (p.includes('/sempiternal/public/')) {
    return `${origin}/sempiternal/public/`;
  }

  return `${origin}/`;
}
  const PUBLIC = getPublicBase();
  const FIRMAS_ENDPOINTS = [
    `${PUBLIC}app/controllers/contratos/firmas.php`,
  ];

  const norm = (s) =>
    (s || '')
      .toString()
      .trim()
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ');

  async function fetchFirmas(solicitud_id) {
    const qs = `?solicitud_id=${encodeURIComponent(solicitud_id)}&_=${Date.now()}`;
    let err = null;
    for (const base of FIRMAS_ENDPOINTS) {
      try {
        const res = await fetch(base + qs, { cache: 'no-store' });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data && data.ok) return data;
        err = data?.error || res.statusText || 'Error desconocido';
      } catch (e) {
        err = e.message || e;
      }
    }
    throw new Error(err || 'No se pudo contactar el endpoint de firmas.');
  }

// ===== POST robusto a /app/controllers/contratos/firmas.php =====
async function postFirmas(payload) {
  const url = `${PUBLIC}app/controllers/contratos/guardar.php`;

  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'Accept': 'application/json'
    },
    body: JSON.stringify(payload),
    credentials: 'same-origin',
    cache: 'no-store',
  });

  // Leemos el cuerpo como texto SIEMPRE, para poder mostrar qué llegó si no es JSON
  const raw = await res.text();
  let data = null;

  try {
    data = raw ? JSON.parse(raw) : null;
  } catch {
    // No es JSON — lanzamos con detalle para que lo veas en consola/alert
    throw new Error(`Respuesta inválida del servidor (HTTP ${res.status}).\n\nBody:\n${raw.slice(0,800)}`);
  }

  if (!res.ok || !data?.ok) {
    // El PHP usa jexit({ok:false,error:"..."}) -> mostramos eso si viene
    const msg = data?.error || `HTTP ${res.status}`;
    throw new Error(msg);
  }

  return data;
}


  /* ============== Canvas utils (pintar/leer) ============== */
  function ensureCanvasSize(canvas) {
    const dpr = window.devicePixelRatio || 1;
    const cssW = canvas.clientWidth || 400;
    const cssH = canvas.clientHeight || 180;
    const w = Math.max(1, Math.round(cssW * dpr));
    const h = Math.max(1, Math.round(cssH * dpr));
    if (canvas.width !== w || canvas.height !== h) {
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0); // escala para nitidez
      ctx.clearRect(0, 0, cssW, cssH);
    }
  }

  function drawOnCanvasFromDataURL(canvas, dataURL) {
    if (!canvas || !dataURL) return;
    ensureCanvasSize(canvas);
    const ctx = canvas.getContext('2d');
    const cssW = canvas.clientWidth || 400;
    const cssH = canvas.clientHeight || 180;
    const img = new Image();
    img.onload = () => {
      ctx.clearRect(0, 0, cssW, cssH);
      const ratio = Math.min(cssW / img.width, cssH / img.height);
      const dw = img.width * ratio;
      const dh = img.height * ratio;
      const dx = (cssW - dw) / 2;
      const dy = (cssH - dh) / 2;
      ctx.drawImage(img, dx, dy, dw, dh);
    };
    img.src = dataURL;
  }

  function canvasHasInk(canvas) {
    const ctx = canvas.getContext('2d');
    const cssW = canvas.clientWidth || 400;
    const cssH = canvas.clientHeight || 180;
    const dpr = window.devicePixelRatio || 1;
    const img = ctx.getImageData(0, 0, Math.round(cssW * dpr), Math.round(cssH * dpr)).data;
    for (let i = 3; i < img.length; i += 4) if (img[i] !== 0) return true; // canal alfa
    return false;
  }

  // ⬇️ Preferimos lo que hay en el CANVAS (más fiel al cuadro). Si no hay, usamos la miniatura.
  function dataURLFromPad(wrap) {
    const cv = wrap.querySelector('canvas');
    if (cv) {
      ensureCanvasSize(cv);
      if (canvasHasInk(cv)) return cv.toDataURL('image/png');
    }
    const img = wrap.querySelector('.sig-output img');
    if (img?.src?.startsWith('data:')) return img.src;
    return null;
  }

  /* ============== Inicialización de pads (dibujo + botones) ============== */
  function setupPads() {
    document.querySelectorAll('.sig-pad').forEach((wrap) => {
      const canvas = wrap.querySelector('canvas');
      const btnClear = wrap.querySelector('.sig-clear');
      const btnSave  = wrap.querySelector('.sig-save');
      const out = wrap.querySelector('.sig-output');

      if (!canvas) return;
      ensureCanvasSize(canvas);
      const ctx = canvas.getContext('2d');
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.strokeStyle = '#111';

      let drawing = false;
      let last = null;

      const getPos = (e) => {
        const rect = canvas.getBoundingClientRect();
        const p = e.touches ? e.touches[0] : e;
        const x = p.clientX - rect.left;
        const y = p.clientY - rect.top;
        return { x, y };
      };

      const start = (e) => {
        e.preventDefault();
        ensureCanvasSize(canvas);
        drawing = true;
        last = getPos(e);
      };
      const move = (e) => {
        if (!drawing) return;
        const p = getPos(e);
        ctx.beginPath();
        ctx.moveTo(last.x, last.y);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        last = p;
      };
      const end = () => { drawing = false; last = null; };

      canvas.addEventListener('mousedown', start);
      canvas.addEventListener('mousemove', move);
      window.addEventListener('mouseup', end);
      canvas.addEventListener('touchstart', start, { passive: false });
      canvas.addEventListener('touchmove',  move,  { passive: false });
      window.addEventListener('touchend',   end);

      if (btnClear) {
        btnClear.addEventListener('click', () => {
          ensureCanvasSize(canvas);
          ctx.clearRect(0, 0, canvas.clientWidth || 400, canvas.clientHeight || 180);
          if (out) out.innerHTML = ''; // limpia miniatura también
        });
      }

      if (btnSave) {
        btnSave.addEventListener('click', () => {
          // 1) Captura lo que hay en el canvas
          const dataURL = canvasHasInk(canvas) ? canvas.toDataURL('image/png') : null;
          if (!dataURL) return;

          // 2) Redibuja la firma en el canvas con escalado/centrado (asegura ajuste al cuadro)
          drawOnCanvasFromDataURL(canvas, dataURL);

          // 3) (Opcional) Miniatura para impresión/export
          if (out) {
            out.innerHTML = '';
            const img = new Image();
            img.src = dataURL;
            img.style.maxWidth = '100%';
            img.style.maxHeight = '80px';
            // Si tu CSS oculta .sig-output en pantalla, no pasa nada; en print se mostrará.
            out.appendChild(img);
          }
        });
      }
    });
  }



(function () {
  const $ = (id) => document.getElementById(id);

  window.__setParentescoUI = function (texto) {
    const sel  = $('bene_extra_parentesco');
    const wrap = $('bene_extra_parentesco_otro_wrap'); // contenedor del input "otro"
    const inp  = $('bene_extra_parentesco_otro');

    if (!sel) return;
    const val = (texto || '').trim();
    if (!val) { 
      // si viene vacío, sólo aseguramos que no se muestre el input "otro"
      if (wrap) wrap.style.display = 'none';
      if (inp)  inp.value = '';
      return; 
    }

    // ¿Existe una opción (case-insensitive) que coincida con el texto?
    const optMatch = Array.from(sel.options).find(o => 
      (o.value || '').toUpperCase() === val.toUpperCase()
    );

    if (optMatch) {
      // Selecciona opción existente y oculta el input "otro"
      sel.value = optMatch.value;
      if (wrap) wrap.style.display = 'none';
      if (inp)  inp.value = '';
      return;
    }

    // Si no existe: selecciona OTRO, muestra input y rellena el texto
    // Asegura opción OTRO
    const hasOtro = Array.from(sel.options).some(o => (o.value || '').toUpperCase() === 'OTRO');
    if (!hasOtro) sel.append(new Option('OTRO', 'OTRO'));

    sel.value = 'OTRO';
    if (wrap) wrap.style.display = '';
    if (inp)  inp.value = val;

    // Si ya tienes el helper de opción dinámica, intenta usarlo (no pasa nada si no existe)
    if (typeof window.upsertDynamicOptionFromInput === 'function') {
      window.upsertDynamicOptionFromInput();
    }
  };
})();


  
/* ============== Cargar desde BD y pintar ============== */
/* ================== Helper: pintar "Forma de Entrega" ================== */
/* ================== Helper: pintar "Forma de Entrega" ================== */
function setEntregaUIFromDB(filaPrincipal){
  const sel   = document.getElementById('metodo_entrega');
  const banco = document.getElementById('m_prest_banco');
  const num   = document.getElementById('m_prest_clabe');
  const caja  = document.getElementById('transferencia_fields');
  const out   = document.getElementById('out_metodo_entrega');
  if (!sel || !filaPrincipal) return;

  // Normaliza el tipo de la BD
  const rawTipo = String(filaPrincipal.entrega_tipo || '').trim().toUpperCase(); // EFECTIVO|CHEQUE|TRANSFERENCIA
  const tipo = rawTipo.toLowerCase(); // para el <select>

  // Selecciona en el <select> si es uno válido
  if (['efectivo','cheque','transferencia'].includes(tipo)) {
    sel.value = tipo;
  } else {
    // si viene vacío o desconocido NO tocamos el select
    console.warn('[entrega] tipo no reconocido desde BD:', rawTipo);
  }

  // Mostrar/ocultar bloque de transferencia sin depender de otros listeners
  if (caja) caja.classList.toggle('hidden', tipo !== 'transferencia');

  // Si es transferencia, rellena banco y cuenta/CLABE
  if (tipo === 'transferencia') {
    if (banco) banco.value = (filaPrincipal.entrega_banco || '').trim();

    if (num) {
      // mete crudo; tus listeners ya formatean/validan
      const raw = String(filaPrincipal.entrega_cuenta || '').replace(/\D/g,'');
      num.value = raw;
      // dispara tus formateadores/validador existentes
      num.dispatchEvent(new Event('input', { bubbles:true }));
      num.dispatchEvent(new Event('blur',  { bubbles:true }));
    }
  } else {
    // limpia por si quedaron residuos
    if (banco) banco.value = '';
    if (num)   num.value   = '';
  }

  // Actualiza el texto que va al contrato (fallback por si no existe la función)
  if (typeof window.actualizarOutMetodo === 'function') {
    window.actualizarOutMetodo();
  } else if (out) {
    if (tipo === 'transferencia') {
      const bank = (banco?.value || (filaPrincipal.entrega_banco || '')).trim();
      const acct = (num?.value || String(filaPrincipal.entrega_cuenta || '')).replace(/\D/g,'');
      const etiqueta = acct.length === 18 ? 'CLABE' : (acct ? 'Cuenta/Tarjeta' : '');
      out.textContent = ['Transferencia', bank, etiqueta && acct ? `${etiqueta}: ${acct}` : '']
        .filter(Boolean).join(' ');
    } else if (tipo === 'efectivo') out.textContent = 'Efectivo';
    else if (tipo === 'cheque')    out.textContent = 'Cheque nominativo';
  }

  // log de depuración
  console.log('[entrega] tipo=', rawTipo, ' banco=', filaPrincipal.entrega_banco, ' cuenta=', filaPrincipal.entrega_cuenta);
}


/* ============== Cargar desde BD y pintar ============== */
async function cargarFirmas() {
  const params = new URLSearchParams(location.search);
  const solicitud_id = Number(params.get('solicitud_id') || 0);
  if (!solicitud_id) {
    console.warn('[firmas] Falta ?solicitud_id');
    return;
  }

  // ===== Helpers locales =====
  const _sliceISO = s => (s && typeof s === 'string') ? s.slice(0,10) : null; // "YYYY-MM-DD"
  const _U = s => (s||'').toString().trim().toUpperCase();
  const _todayISO = () => {
    const d = new Date();
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const dd = String(d.getDate()).padStart(2,'0');
    return `${d.getFullYear()}-${mm}-${dd}`;
  };
  const _extraerFechas = (firmas) => {
    if (!Array.isArray(firmas)) return {fc:null, fe:null, fd:null};
    let fila = firmas.find(f =>
      _U(f.documento_norm||f.documento) === 'CONTRATO' &&
      _U(f.firmante_norm || f.firmante) === 'PRESTATARIO' &&
      Number(f.page||1) === 1
    );
    if (!fila) fila = firmas.find(f => f.fecha_contrato || f.fecha_entrega || f.fecha_devolucion);
    return {
      fc: fila ? _sliceISO(fila.fecha_contrato)   : null,
      fe: fila ? _sliceISO(fila.fecha_entrega)    : null,
      fd: fila ? _sliceISO(fila.fecha_devolucion) : null,
    };
  };
  const _aplicarFechasSrv = ({fc,fe,fd}) => {
    const { base, entr, devol } = getFechaKeys();
    try {
      if (fc) {
        localStorage.setItem(base, fc);
      } else if (!localStorage.getItem(base)) {
        localStorage.setItem(base, _todayISO());
      }
      if (fe) localStorage.setItem(entr,  fe);
      if (fd) localStorage.setItem(devol, fd);
    } catch(_) {}
    if (typeof pintarFechasDerivadas === 'function') pintarFechasDerivadas();
  };

  // Espera a que exista una función global y luego la ejecuta (máx. ~1.5s)
  const waitForFn = (name, cb, tries=30) => {
    if (typeof window[name] === 'function') { cb(window[name]); return; }
    if (tries <= 0) return;
    setTimeout(() => waitForFn(name, cb, tries-1), 50);
  };

  try {
    const { firmas = [] } = await fetchFirmas(solicitud_id);
    console.log(`[firmas] Cargadas ${firmas.length} firmas.`);

    // 1) Fechas
    _aplicarFechasSrv(_extraerFechas(firmas));

    // 1.1) Fila principal (CONTRATO / PRESTATARIO / page 1)
    const principal = firmas.find(f =>
      _U(f.documento_norm||f.documento) === 'CONTRATO' &&
      _U(f.firmante_norm || f.firmante) === 'PRESTATARIO' &&
      Number(f.page||1) === 1
    );

    // -> Forma de entrega al UI (solo si existe principal)
if (!principal) {
  console.warn('[firmas] No encontré fila principal (CONTRATO/PRESTATARIO/page=1).');
} else {
  // >>> pinta forma de entrega desde BD
  setEntregaUIFromDB(principal);
}

    // 1.2) Beneficiario desde principal
    if (principal) {
      const beneObj = {
        nombre: (principal.beneficiario_nombre || '').trim(),
        fuente: principal.beneficiario_fuente || null, // MANUAL | REFERENCIA | CODEUDOR
        parentesco: principal.beneficiario_parentesco || '',
        celular: principal.beneficiario_celular || '',
        email: principal.beneficiario_email || ''
      };

      __setParentescoUI(beneObj.parentesco);
      window.__benePrefPendiente = beneObj; // por si no cargó aún el módulo

      waitForFn('prefijarBeneficiarioDesdeBD', (fn) => fn(beneObj));

      // Fallback tardío (UI mínimo)
setTimeout(() => {
  const sel  = document.getElementById('beneficiario_ctrl');
  const inp  = document.getElementById('beneficiario_ctrl_input');
  const wrap = document.getElementById('bene_extra_wrap');
  if (!sel && !inp) return;
  if (!beneObj.nombre) return;

  // 1) ¿viene de catálogo?
  const fuente = String(beneObj.fuente || '').trim().toUpperCase();
  const esCatalogo = ['REFERENCIA', 'CODEUDOR', 'CATALOGO'].includes(fuente);

  // 2) ¿hay match en el select?
  let matched = false;
  if (sel) {
    const byValue = sel.querySelector(`option[value="${CSS.escape(beneObj.nombre)}"]`);
    const byText  = Array.from(sel.options)
      .find(o => (o.text || '').trim().toUpperCase() === beneObj.nombre.toUpperCase());
    if (byValue) { sel.value = byValue.value; matched = true; }
    else if (byText) { sel.value = byText.value; matched = true; }
  }

  // 3) Si es catálogo o hubo match -> ocultar extras
  if (esCatalogo || matched) {
    if (sel) {
      // asegúrate de que exista "__OTRO__" pero NO la selecciones
      if (!Array.from(sel.options).some(o => o.value === '__OTRO__')) {
        sel.append(new Option('Otro (capturar)…', '__OTRO__'));
      }
      sel.hidden = false;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (inp)  inp.hidden = true;
    if (wrap) wrap.classList.add('hidden');

    document.querySelectorAll('.out_beneficiario').forEach(el => el.textContent = beneObj.nombre);
    return;
  }

  // 4) No es catálogo y no hubo match -> forzar “Otro”
  if (sel) {
    if (!Array.from(sel.options).some(o => o.value === '__OTRO__')) {
      sel.append(new Option('Otro (capturar)…', '__OTRO__'));
    }
    sel.hidden = false;
    sel.value = '__OTRO__';
    sel.dispatchEvent(new Event('change', { bubbles: true }));
  }
  if (inp)  inp.hidden = true;
  if (wrap) wrap.classList.remove('hidden');

  // Prellenar extras
  document.getElementById('bene_extra_nombre')?.setAttribute('value', beneObj.nombre || '');
  const parSel = document.getElementById('bene_extra_parentesco');
  if (parSel) parSel.value = beneObj.parentesco || '';
  const cel = document.getElementById('bene_extra_cel');  if (cel)  cel.value  = beneObj.celular || '';
  const mail= document.getElementById('bene_extra_mail'); if (mail) mail.value = beneObj.email   || '';

  document.querySelectorAll('.out_beneficiario').forEach(el => el.textContent = beneObj.nombre);
}, 400);
(function () {
  const sel  = document.getElementById('beneficiario_ctrl');
  const wrap = document.getElementById('bene_extra_wrap');
  function toggleExtra(){
    if (sel && wrap) wrap.classList.toggle('hidden', sel.value !== '__OTRO__');
  }
  document.addEventListener('DOMContentLoaded', () => {
    sel?.addEventListener('change', toggleExtra);
    toggleExtra(); // primera pasada
  });
})();

    }

    // 2) Mapear pads
    const normKey = s => String(s||'').trim().toUpperCase();
    const padMap = new Map(
      [...document.querySelectorAll('.sig-pad')].map((w) => [normKey(w.getAttribute('data-label')), w])
    );

    // 3) Pintar firmas
    firmas.forEach((f) => {
      const key = normKey(f.firmante_norm || f.firmante || '');
      const wrap = padMap.get(key);
      if (!wrap) return;

      const dataURL = f.b64 ? `data:${f.mime || 'image/png'};base64,${f.b64}` : null;

      const canvas = wrap.querySelector('canvas');
      if (canvas && dataURL) drawOnCanvasFromDataURL(canvas, dataURL);

      const out = wrap.querySelector('.sig-output');
      if (out) {
        out.innerHTML = '';
        if (dataURL) {
          const img = new Image();
          img.src = dataURL;
          img.style.maxWidth = '100%';
          img.style.maxHeight = '80px';
          out.appendChild(img);
        } else {
          const em = document.createElement('em');
          em.textContent = '(sin imagen de firma)';
          out.appendChild(em);
        }
      }
    });

    // 4) Debug opcional
    console.table({
      pads_en_dom: [...padMap.keys()],
      firmantes_en_bd: firmas.map((f) => normKey(f.firmante_norm || f.firmante || '')),
    });

  } catch (err) {
    console.error('[firmas] Error al cargar:', err);
  }
}


function swalContratoLoading(){
  return Swal.fire({
    title: 'Guardando contrato…',
    html: `
      <div>
        Estoy guardando las firmas y la información del contrato.<br>
        <small style="display:block;margin-top:8px;color:#64748b;">
          Por favor no cierres esta ventana.
        </small>
      </div>
    `,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    buttonsStyling: false,
    customClass: {
      popup: 'cip-swal',
      title: 'cip-swal-title',
      htmlContainer: 'cip-swal-text'
    },
    didOpen: () => {
      Swal.showLoading();
    }
  });
}

function swalContratoError(mensaje){
  return Swal.fire({
    icon: 'error',
    title: 'Error al guardar',
    html: `
      <div>
        ${mensaje || 'No se pudo guardar el contrato.'}
      </div>
    `,
    confirmButtonText: 'Aceptar',
    buttonsStyling: false,
    customClass: {
      popup: 'cip-swal',
      title: 'cip-swal-title',
      htmlContainer: 'cip-swal-text',
      confirmButton: 'cip-swal-error'
    }
  });
}

async function swalContratoGuardado(solicitudId){
  const tablaUrl = `${PUBLIC}tablas/tabla.html?solicitud_id=${encodeURIComponent(String(solicitudId))}`;

  const result = await Swal.fire({
    icon: 'success',
    title: 'Contrato guardado',
    html: `
      <div>
        El contrato se guardó correctamente.<br>
        <strong>¿Deseas avanzar a la tabla de amortización?</strong>
      </div>
    `,
    showDenyButton: true,
    showCancelButton: false,
    confirmButtonText: 'Avanzar',
    denyButtonText: 'Salir',
    reverseButtons: true,
    buttonsStyling: false,
    customClass: {
      popup: 'cip-swal',
      title: 'cip-swal-title',
      htmlContainer: 'cip-swal-text',
      confirmButton: 'cip-swal-confirm',
      denyButton: 'cip-swal-cancel',
      cancelButton: 'cip-swal-cancel'
    }
  });

  if (result.isConfirmed) {
    window.location.href = tablaUrl;
    return;
  }

  if (result.isDenied) {
    window.location.href = `${PUBLIC}index.php`;
  }
}


/* ============== Guardar (expuesto para el botón) ============== */
async function guardarContrato() {
  function _getFechaKeys(){
    if (typeof getFechaKeys === 'function') return getFechaKeys();
    const q = new URLSearchParams(location.search);
    const sid = Number(q.get('solicitud_id') || 0) || 'GLOBAL';
    return {
      base:  `contrato_fecha_base_${sid}`,
      devol: `contrato_fecha_devolucion_${sid}`,
      entr:  `contrato_fecha_entrega_${sid}`
    };
  }

  const onlyDigits = s => String(s||'').replace(/\D+/g,'');
  function esClabeValida(clabe){
    const s = onlyDigits(clabe);
    if (s.length !== 18) return false;
    const pesos = [3,7,1];
    let suma = 0;
    for (let i=0;i<17;i++) suma += ((+s[i]) * pesos[i % 3]) % 10;
    const dig = (10 - (suma % 10)) % 10;
    return dig === +s[17];
  }

  const params = new URLSearchParams(location.search);
  const solicitud_id = Number(params.get('solicitud_id') || 0);
if (!solicitud_id) {
  swalContratoError('Falta ?solicitud_id');
  return;
}
  const btn = document.getElementById('btn_guardar');
  if (btn) {
    btn.disabled = true;
    btn.dataset.prev = btn.textContent;
    btn.textContent = 'Guardando...';
  }

  try {
      swalContratoLoading();
    // 1) Firmas: toma directo del canvas si hay tinta
    const firmas = [];
    document.querySelectorAll('.sig-pad').forEach((wrap, i) => {
      const lbl = (wrap.getAttribute('data-label') || `firmante_${i}`).trim();
      const dataURL = dataURLFromPad(wrap);
      if (!dataURL) return;

      firmas.push({
        documento: 'CONTRATO',
        firmante: lbl,
        page: 1,
        storage: 'db',
        mime: 'image/png',
        data_base64: dataURL
      });
    });

    // 2) Fechas
    const { base, entr, devol } = _getFechaKeys();
    let fecha_contrato = null;
    let fecha_entrega = null;
    let fecha_devolucion = null;

    try {
      fecha_contrato   = localStorage.getItem(base);
      fecha_entrega    = localStorage.getItem(entr);
      fecha_devolucion = localStorage.getItem(devol);
    } catch (_) {}

    // 3) Beneficiario
    let bene = (typeof window.getBeneficiarioSeleccion === 'function')
      ? window.getBeneficiarioSeleccion()
      : null;

    if (!bene || !bene.valor) {
      const inp = document.getElementById('beneficiario_ctrl_input');
      const manual = (inp?.value || '').trim();
      bene = manual
        ? { valor: manual, fuente: 'OTRO', numero: null }
        : { valor: '', fuente: null, numero: null };
    }

    let fuenteRaw = bene?.fuente ? String(bene.fuente).toUpperCase().trim() : null;
    if (fuenteRaw && /MANUAL/.test(fuenteRaw)) fuenteRaw = 'OTRO';
    if (fuenteRaw && /OTRO/.test(fuenteRaw))   fuenteRaw = 'OTRO';
    if (fuenteRaw && /CATALOGO/.test(fuenteRaw)) fuenteRaw = 'REFERENCIA';

    const FUENTES_OK = new Set(['REFERENCIA','CODEUDOR','OTRO']);
    const bene_fuente = FUENTES_OK.has(fuenteRaw) ? fuenteRaw : null;

    if (bene_fuente === 'OTRO') {
      const nombre = (document.getElementById('bene_extra_nombre')?.value || '').trim();
      const parentesco = (document.getElementById('bene_extra_parentesco')?.value || '').trim();
      const parentescoOtro = (document.getElementById('bene_extra_parentesco_otro')?.value || '').trim();
      const celular = (document.getElementById('bene_extra_cel')?.value || '').trim();
      const email = (document.getElementById('bene_extra_mail')?.value || '').trim();

      bene.extra = {
        nombre,
        parentesco: (parentesco === 'OTRO' ? parentescoOtro : parentesco) || '',
        celular,
        email
      };

      if (!bene.valor && nombre) bene.valor = nombre;
    }

    const meta = {
      fecha_contrato: fecha_contrato || null,
      fecha_entrega: fecha_entrega || null,
      fecha_devolucion: fecha_devolucion || null,
      beneficiario: bene?.valor ? bene.valor.trim() : null,
      bene_fuente
    };

    if (bene?.numero != null) {
      const n = parseInt(bene.numero, 10);
      if (Number.isFinite(n)) meta.bene_numero = n;
    }

    if (bene_fuente === 'OTRO' && bene?.extra) {
      const extra = {
        nombre: (bene.extra.nombre || '').trim(),
        parentesco: (bene.extra.parentesco || '').trim(),
        celular: (bene.extra.celular || '').trim(),
        email: (bene.extra.email || '').trim()
      };
      if (extra.nombre || extra.parentesco || extra.celular || extra.email) {
        meta.beneficiario_extra = extra;
      }
    }

    // 4) Entrega
    const tipoSel  = document.getElementById('metodo_entrega');
    const bancoInp = document.getElementById('m_prest_banco');
    const ctaInp   = document.getElementById('m_prest_clabe');

    const entrega = {
      tipo: (tipoSel?.value || '').toUpperCase()
    };

    if (entrega.tipo === 'TRANSFERENCIA') {
      const banco = (bancoInp?.value || '').trim();
      const cuenta = onlyDigits(ctaInp?.value || '');

      if (cuenta) {
        if (cuenta.length < 16 || cuenta.length > 19) {
          throw new Error('La cuenta/CLABE debe tener entre 16 y 19 dígitos.');
        }
        if (cuenta.length === 18 && !esClabeValida(cuenta)) {
          throw new Error('CLABE no válida (revisa el dígito verificador).');
        }
      }

      if (banco) entrega.banco = banco;
      if (cuenta) entrega.cuenta = cuenta;
    }

    // 5) Payload
    const payload = {
      solicitud_id,
      mode: 'upsert',
      meta,
      entrega
    };

    // Solo manda firmas si sí existen
    if (firmas.length) {
      payload.firmas = firmas;
    }

await postFirmas(payload);

if (typeof cargarFirmas === 'function') {
  await cargarFirmas();
}

await swalContratoGuardado(solicitud_id);

} catch (e) {
  console.error(e);
  swalContratoError(e.message || e);
} finally {
    if (btn) {
      btn.disabled = false;
      btn.textContent = btn.dataset.prev || 'Guardar Formato';
    }
  }
}

window.guardarContrato = guardarContrato;

  // arrancar
  document.addEventListener('DOMContentLoaded', () => {
    setupPads();
    cargarFirmas();
  });
})();


function drawOnCanvasFromDataURL(canvas, dataURL, tries=0){
  if (!canvas || !dataURL) return;

  // Si aún no tiene tamaño CSS (está oculto), reintenta un momento
  const cssW = canvas.clientWidth;
  const cssH = canvas.clientHeight;
  if ((!cssW || !cssH) && tries < 10){
    return requestAnimationFrame(() => drawOnCanvasFromDataURL(canvas, dataURL, tries+1));
  }

  const dpr = window.devicePixelRatio || 1;
  const w = Math.max(1, Math.round((cssW||400) * dpr));
  const h = Math.max(1, Math.round((cssH||180) * dpr));

  if (canvas.width !== w || canvas.height !== h){
    canvas.width = w; canvas.height = h;
    const ctx0 = canvas.getContext('2d');
    ctx0.setTransform(dpr,0,0,dpr,0,0);
  }

  const ctx = canvas.getContext('2d');
  const img = new Image();
  img.onload = () => {
    ctx.clearRect(0, 0, cssW||400, cssH||180);
    const ratio = Math.min((cssW||400)/img.width, (cssH||180)/img.height);
    const dw = img.width * ratio, dh = img.height * ratio;
    const dx = ((cssW||400) - dw)/2, dy = ((cssH||180) - dh)/2;
    ctx.drawImage(img, dx, dy, dw, dh);
  };
  img.src = dataURL;
}

// 👇 Asegúrate de mostrar las páginas y *luego* cargar firmas
document.addEventListener('DOMContentLoaded', () => {
  // si tienes una función que muestra el contrato:
  if (typeof window.mostrarContrato === 'function') window.mostrarContrato();

  // pequeño delay para garantizar que los pads ya tienen tamaño
  requestAnimationFrame(() => {
    if (typeof window.cargarFirmas === 'function') window.cargarFirmas();
  });
});


(function () {
  // Base pública (…/public/)
function getPublicBase(){
  const b = document.querySelector('base')?.href;
  if (b) return b.replace(/\/+$/, '/');

  const { origin, pathname } = window.location;
  const p = pathname.replace(/\/+$/, '/').toLowerCase();

  if (p.includes('/hp/public/')) {
    return `${origin}/hp/public/`;
  }

  if (p.includes('/sempiternal/public/')) {
    return `${origin}/sempiternal/public/`;
  }

  return `${origin}/`;
}
  const PUBLIC = getPublicBase();

  async function cargarFolio() {
    const params = new URLSearchParams(location.search);
    const sid = Number(params.get('solicitud_id') || 0);
    if (!sid) return;

    try {
      // Ajusta la ruta si tu PHP quedó en otra carpeta
      const url = `${PUBLIC}app/controllers/contratos/folio.php?solicitud_id=${sid}`;
      const res = await fetch(url, { cache: 'no-store' });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data || !data.ok) throw new Error(data?.error || 'No se pudo obtener folio');

      const folio = (data.folio || '').trim() || 'SIN-FOLIO';

      // 1) Si ya actualizaste el HTML, usa <span class="out_folio"></span> en cada hoja:
      let targets = Array.from(document.querySelectorAll('.out_folio'));

      // 2) Respaldo: si no hay .out_folio, usa los spans dentro de .contrato-num
      if (targets.length === 0) {
        targets = Array.from(document.querySelectorAll('.contrato-num span'));
      }

      // 3) Último respaldo: si sólo hay un #out_folio (no recomendado, id duplicado), actualiza todos
      if (targets.length === 0) {
        targets = Array.from(document.querySelectorAll('#out_folio'));
      }

      targets.forEach(el => { el.textContent = folio; });
    } catch (e) {
      console.warn('[folio]', e.message || e);
    }
  }

  document.addEventListener('DOMContentLoaded', cargarFolio);
})();



// ====== CLAVES POR SOLICITUD ======
// ====== CLAVES POR SOLICITUD (SIEMPRE POR-ID) ======
function getFechaKeys() {
  let sidStr = 'GLOBAL';
  try {
    // 1) si hay global window.solicitud_id, úsala
    if (typeof window.solicitud_id !== 'undefined' && window.solicitud_id) {
      sidStr = String(window.solicitud_id);
    } else {
      // 2) si no, léela del querystring
      const p = new URLSearchParams(location.search);
      const sid = Number(p.get('solicitud_id') || 0);
      if (sid) sidStr = String(sid);
    }
  } catch (_) {}

  return {
    base:  `contrato_fecha_base_${sidStr}`,      // "YYYY-MM-DD" (fija)
    devol: `contrato_fecha_devolucion_${sidStr}`,// cache derivada
    entr:  `contrato_fecha_entrega_${sidStr}`    // cache derivada
  };
}


// ====== HELPERS ======
const toISO = d => d.toISOString().slice(0,10);
function fromISO(s){
  if(!/^\d{4}-\d{2}-\d{2}$/.test(s||'')) return null;
  const [y,m,d]=s.split('-').map(Number);
  return new Date(y, m-1, d);
}
const fmtLargo = d => d.toLocaleDateString('es-MX', {day:'2-digit', month:'long', year:'numeric'});

function addDays(d, n){
  const x = new Date(d.getTime());
  x.setDate(x.getDate() + (Number(n)||0));
  return x;
}
function addMonthsSafe(d, n){
  const x = new Date(d.getTime());
  const target = x.getMonth() + (Number(n)||0);
  const day = x.getDate();
  x.setDate(1);
  x.setMonth(target);
  const last = new Date(x.getFullYear(), x.getMonth()+1, 0).getDate();
  x.setDate(Math.min(day, last));
  return x;
}

// ====== 1) ASEGURAR FECHA PRINCIPAL (SE FIJA UNA SOLA VEZ) ======
function asegurarFechaPrincipal() {
  const { base } = getFechaKeys();
  let iso = null;
  try { iso = localStorage.getItem(base); } catch(_){}

  if (!iso) { // primera vez → se fija “hoy”
    iso = toISO(new Date());
    try { localStorage.setItem(base, iso); } catch(_){}
  }
  return fromISO(iso) || new Date();
}

// ====== 2) CALCULAR Y PINTAR FECHAS DERIVADAS ======
function pintarFechasDerivadas() {
  const { base, devol, entr } = getFechaKeys();
  const dBase = asegurarFechaPrincipal();

  // a) out_fecha_actual (SIEMPRE la principal fija)
  const elActual = document.getElementById('out_fecha_actual');
  if (elActual) elActual.textContent = fmtLargo(dBase);

  // b) out_fecha_entrega = principal + días (si tienes un input, usa su valor)
  //    Si no tienes input, déjalo en 0 días (misma fecha)
  const diasEntrega = Number(document.getElementById('dias_entrega')?.value || 0);
  const dEntrega = addDays(dBase, diasEntrega);
  const elEntrega = document.getElementById('out_fecha_entrega');
  if (elEntrega) elEntrega.textContent = fmtLargo(dEntrega);
  try { localStorage.setItem(entr, toISO(dEntrega)); } catch(_){}

  // c) out_fecha_devol = principal + plazo (meses)
  const meses = parseInt(document.getElementById('plazo_meses')?.value ?? '0', 10);
  const dDevol = addMonthsSafe(dBase, Number.isFinite(meses) ? meses : 0);
  const elDevol = document.getElementById('out_fecha_devol');
  if (elDevol) elDevol.textContent = fmtLargo(dDevol);
  try { localStorage.setItem(devol, toISO(dDevol)); } catch(_){}

  // d) también muestra la principal en #out_fecha (si lo usas)
  const elBase = document.getElementById('out_fecha');
  if (elBase) elBase.textContent = fmtLargo(dBase);
}

// ====== 3) INTEGRA CON TU FLUJO ======
function propagarTodo() {
  window.aplicarSeleccion?.();
  window.aplicarPrestatarioManual?.();
  window.aplicarMonto?.();

  // Reemplaza tus llamadas de fecha:
  pintarFechasDerivadas(); // fija/lee la principal y pinta todo

  window.updateMesesText?.();
  window.actualizarMetodoEntrega?.();
  window.aplicarDatosDelPrestador?.();
  window.aplicarDatosBancarios?.();
}

// Recalcular derivadas si cambian los controles
document.addEventListener('change', (e) => {
  if (e.target && (e.target.id === 'plazo_meses' || e.target.id === 'dias_entrega')) {
    pintarFechasDerivadas();
    if (e.target.id === 'plazo_meses') window.updateMesesText?.();
  }
});


// ⬇️ Pon ESTO al final (sustituye tu última definición de propagarTodo)
(function(){
  const _orig = window.propagarTodo;
  window.propagarTodo = function(){
    _orig?.();                  // conserva lo que ya hacía
    pintarFechasDerivadas();    // ahora fijamos/mostramos fechas desde la base
  };
})();

