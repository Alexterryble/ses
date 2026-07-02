// === Helpers "Otro…" (idempotentes) ===
window.ensureOtroOption = window.ensureOtroOption || function(sel){
  if (!sel || sel.tagName.toLowerCase() !== 'select') return;
  const existe = Array.from(sel.options).some(o => o.value === '__otro__');
  if (!existe) sel.add(new Option('Otro…', '__otro__'));
};

window.convertirSelectAInputMismoId = window.convertirSelectAInputMismoId || function(sel){
  const input = document.createElement('input');
  input.type = 'text';
  input.id = sel.id;                           // ← mismo id
  input.name = sel.name || sel.id;
  input.placeholder = 'Escribe tu colonia';
  input.required = true;
  input.className = sel.className;
  sel.replaceWith(input);
  return input;
};


/* ================== Helpers idempotentes (Buscar por C.P) ================== */
window._nrm  = window._nrm  || (s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim());
const  _nrm  = window._nrm;
window.norm  = window.norm  || _nrm;
const  NORM  = window.norm;

window.mapaEstados = window.mapaEstados || {
  'mexico':'Estado de México','coahuila de zaragoza':'Coahuila','michoacan de ocampo':'Michoacán',
  'veracruz de ignacio de la llave':'Veracruz','queretaro de arteaga':'Querétaro','distrito federal':'Ciudad de México'
};
const MAPA_ESTADOS = window.mapaEstados;

window.ESTADOS_MX = window.ESTADOS_MX || [
  'Aguascalientes','Baja California','Baja California Sur','Campeche','Coahuila',
  'Colima','Chiapas','Chihuahua','Ciudad de México','Durango','Estado de México',
  'Guanajuato','Guerrero','Hidalgo','Jalisco','Michoacán','Morelos','Nayarit',
  'Nuevo León','Oaxaca','Puebla','Querétaro','Quintana Roo','San Luis Potosí',
  'Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas'
];
const ESTADOS_MX = window.ESTADOS_MX;
// Agrega "Otro…" y convierte a <input> cuando lo eligen
function hookOtroEnSelect(sel){
  if (!sel || sel.tagName.toLowerCase()!=='select') return;
  window.ensureOtroOption(sel);
  if (sel._otroHooked) return;
  sel.addEventListener('change', () => {
    if (sel.value === '__otro__') window.convertirSelectAInputMismoId(sel);
  });
  sel._otroHooked = true;
}

/* ================== Estado (input → select) ================== */
function poblarSelectEstadosMX(selectEl, placeholder='Seleccione un estado…'){
  if (!selectEl) return;
  selectEl.innerHTML = '';
  const ph = document.createElement('option');
  ph.value = ''; ph.disabled = true; ph.selected = true; ph.textContent = placeholder;
  selectEl.appendChild(ph);
  ESTADOS_MX.forEach(e=>{
    const opt = document.createElement('option');
    opt.value = e; opt.textContent = e;
    selectEl.appendChild(opt);
  });
}
function selectEstadoByName(selectEl, nombre){
  if (!selectEl || !nombre) return false;
  const objetivo = _nrm(nombre);
  const match = Array.from(selectEl.options||[])
    .find(o => _nrm(o.value||o.textContent) === objetivo);
  if (match){
    selectEl.value = match.value;
    selectEl.dispatchEvent(new Event('change',{bubbles:true}));
    return true;
  }
  return false;
}
function upgradeInputToSelectEstadoMX(id='estado_trabajo'){
  const old = document.getElementById(id);
  if (!old) return;
  if (old.tagName.toLowerCase()==='select'){ poblarSelectEstadosMX(old); return; }

  const sel = document.createElement('select');
  sel.id = old.id;
  sel.name = old.getAttribute('name') || old.id;
  sel.className = old.className || 'form-control';
  if (old.hasAttribute('required')) sel.required = true;

  const previo = (old.value||'').trim();
  old.replaceWith(sel);
  poblarSelectEstadosMX(sel);
  if (previo) selectEstadoByName(sel, previo);
}

/* ===== CP TRABAJO: autocompleta y selecciona colonia/estado ===== */
(function setupCpTrabajo(){
  const cp = document.getElementById('cp_trabajo');
  if (!cp) return;

  const get = id => document.getElementById(id);
  const limpiar = () => {
    const mun=get('municipio_trabajo'), edo=get('estado_trabajo'), col=get('colonia_trabajo'), pais=get('pais_trabajo');
    if (mun) mun.value=''; if (edo) edo.value=''; if (col) col.value=''; if (pais) pais.value='';
  };

  let last = '';
  const buscar = async () => {
    const raw = (cp.value||'').replace(/\D+/g,'').slice(0,5);
    if (cp.value !== raw) cp.value = raw;
    if (!/^\d{5}$/.test(raw)){ last=''; limpiar(); return; }
    if (raw === last) return; last = raw;

    try{
      const base = (typeof BASE_PUBLIC==='string') ? BASE_PUBLIC : '/';
      const url  = `${base}app/controllers/cp_buscar.php?cp=${encodeURIComponent(raw)}&t=${Date.now()}`;
      const res  = await fetch(url,{cache:'no-store'});
      const data = await res.json();

      const mun  = get('municipio_trabajo');
      const edo  = get('estado_trabajo');
      const col  = get('colonia_trabajo');
      const pais = get('pais_trabajo');

      if (data && data.success){
        const estadoResp   = data.estado || '';
        const estadoBonito = MAPA_ESTADOS[NORM(estadoResp)] || estadoResp;

        if (mun)  mun.value  = data.municipio || '';
        if (pais) pais.value = 'México';

        // Estado
        if (edo){
          if (edo.tagName.toLowerCase()==='select'){
            if (!selectEstadoByName(edo, estadoBonito)) selectEstadoByName(edo, estadoResp);
          } else {
            edo.value = estadoBonito || '';
          }
        }

        // Colonias
// Colonias
if (col && Array.isArray(data.colonias) && data.colonias.length){
  const seen = new Set(), lista = [];
  data.colonias.forEach(c=>{
    const t = String(c||'').trim(); if (!t) return;
    const k = NORM(t); if (!seen.has(k)){ seen.add(k); lista.push(t); }
  });
  lista.sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));

if (col.tagName.toLowerCase() === 'select'){
  const prev = col.value;
  col.innerHTML = '<option value="" disabled selected>Selecciona colonia…</option>';
  lista.forEach(v => col.add(new Option(v, v, false, false)));

  hookOtroEnSelect(col); // << AQUI (asegura “Otro…” y el change)

  if (prev && lista.some(x => NORM(x) === NORM(prev))) {
    col.value = prev;
  }
  col.dispatchEvent(new Event('change', { bubbles:true }));
} else {
    // Si ya es input (porque eligieron "Otro…"), NO sobrescribas lo que escriban
    if (!col.value.trim()) {
      // opcional: sugerir primera colonia
      // col.value = lista[0] || '';
    }
  }
}

      } else {
        limpiar();
      }
    } catch(e){
      console.warn('[CP trabajo] Error', e);
      limpiar();
    }
  };

  let t;
  cp.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(buscar,300); });
  cp.addEventListener('blur',  buscar);
})();

/* ================== GIRO / actividad de la empresa ================== */
window.GIROS_MX = window.GIROS_MX || [
  // Primario
  'Agricultura','Ganadería','Silvicultura','Pesca','Acuicultura',
  // Minería / energía / construcción
  'Minería','Extracción de petróleo y gas','Construcción','Suministro de electricidad','Suministro de gas','Suministro de agua',
  // Manufactura
  'Alimentos y bebidas','Panadería','Tortillería','Dulces y confitería','Lácteos','Procesamiento de carne',
  'Procesamiento de frutas y verduras','Cervecería','Destilería','Textil','Confección','Calzado',
  'Madera y muebles','Carpintería','Fabricación de muebles','Papel y cartón','Imprenta',
  'Químicos','Farmoquímicos','Plásticos y hule','Vidrio','Cerámica','Cemento y concreto',
  'Metalmecánica','Maquinado','Soldadura','Estructuras metálicas','Automotriz','Autopartes','Electrónica',
  // Comercio
  'Comercio al por mayor','Comercio al por menor','Tienda de abarrotes','Mini super','Ferretería','Papelería',
  'Farmacia','Tienda de ropa','Zapatería','Joyería','Tienda de electrónicos','Refacciones automotrices','Mueblería',
  'Mercado / tianguis','E-commerce (tienda en línea)',
  // Transporte / logística
  'Transporte de carga','Transporte de pasajeros','Taxis / plataforma','Mensajería y paquetería','Almacenamiento y logística',
  // Turismo / alimentos
  'Hoteles y hospedaje','Hostal','Airbnb (hospedaje)','Restaurante','Taquería','Cafetería','Bar','Antro',
  'Banquetes','Catering','Cocina económica','Comida rápida','Food truck',
  // Salud / educación / social
  'Consultorio médico','Clínica','Hospital','Laboratorio clínico','Odontología','Veterinaria','Óptica',
  'Fisioterapia','Farmacia con consultorio','Guardería','Escuela','Universidad','Capacitación / cursos',
  'Asociación civil (A.C.)','Fundación','Iglesia / culto',
  // Profesionales / negocios
  'Despacho contable','Despacho jurídico','Consultoría','Publicidad y marketing','Diseño gráfico','Fotografía',
  'Arquitectura','Ingeniería','Topografía','Software / TI','Desarrollo web','Ciberseguridad','Data / Analítica',
  'Recursos humanos','Call center','Coworking','Inmobiliaria','Arrendamiento de bienes','Administración de condominios',
  'Servicios financieros','Aseguradora / Seguros','Fintech','Cajas de ahorro',
  // Personales / hogar / oficios
  'Estética / barbería','Spa','Cosméticos y belleza','Tatuajes / perforaciones','Gimnasio','Yoga / pilates',
  'Entrenamiento deportivo','Lavandería','Tintorería','Planchaduría','Reparación de calzado','Sastrería',
  'Jardinería','Limpieza','Control de plagas','Cerrajería','Electricista','Plomería','Albañilería','Pintura',
  'Climatización / refrigeración','Instalación de gas',
  // Autos / talleres
  'Taller mecánico','Hojalatería y pintura','Lavado de autos / detailing','Venta de autos','Llantera','Grúas',
  // Cultura / entretenimiento
  'Cine / teatro','Eventos y espectáculos','Renta de sonido / iluminación','Artesanías','Galería de arte','Editorial',
  // Gobierno / otros
  'Gobierno / dependencia','Organismo público','Otro'
];
const GIROS_MX = window.GIROS_MX;

function poblarSelectGiros(selectEl){
  if (!selectEl) return;
  const seen = new Set();
  const opciones = GIROS_MX
    .filter(g=>{ const k=_nrm(g); if (seen.has(k)) return false; seen.add(k); return true; })
    .sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));

  selectEl.innerHTML = '';
  const ph = document.createElement('option');
  ph.value = ''; ph.disabled = true; ph.selected = true; ph.textContent = 'Seleccione un giro…';
  selectEl.appendChild(ph);

  opciones.forEach(g=>{
    const opt = document.createElement('option');
    opt.value = g; opt.textContent = g;
    selectEl.appendChild(opt);
  });
}
function upgradeInputToSelectGiro(id='giro_empresa'){
  const old = document.getElementById(id);
  if (!old) return;
  if (old.tagName.toLowerCase()==='select'){ poblarSelectGiros(old); return; }

  const sel = document.createElement('select');
  sel.id = old.id;
  sel.name = old.getAttribute('name') || old.id;
  sel.className = old.className || 'form-control';
  if (old.hasAttribute('required')) sel.required = true;

  const previo = (old.value||'').trim();
  old.replaceWith(sel);
  poblarSelectGiros(sel);
  if (previo){
    const match = Array.from(sel.options).find(o => _nrm(o.value) === _nrm(previo));
    if (match) sel.value = match.value;
  }
}
function validarGiroSelect(el){
  if (!el) return;
  const ok = el.value !== '' && el.selectedIndex > 0;
  el.classList.remove('is-valid','is-invalid');
  if (ok){
    el.classList.add('is-valid'); el.setCustomValidity(''); el.setAttribute('aria-invalid','false');
  } else {
    el.classList.add('is-invalid'); el.setCustomValidity('Seleccione un giro de la lista'); el.setAttribute('aria-invalid','true');
  }
}

/* ================== Auto-init ================== */
document.addEventListener('DOMContentLoaded', () => {
  // Estado (trabajo) como select de 32 estados
  upgradeInputToSelectEstadoMX('estado_trabajo');

  // Giro / actividad de la empresa
  upgradeInputToSelectGiro('giro_empresa');
  const giroSel = document.getElementById('giro_empresa');
  if (giroSel){
    giroSel.addEventListener('change', ()=>validarGiroSelect(giroSel));
    giroSel.addEventListener('blur',   ()=>validarGiroSelect(giroSel));
    validarGiroSelect(giroSel);
  }
});




// años de trabajo o meses
window.upgradeInputToYearsOnly = window.upgradeInputToYearsOnly || function upgradeInputToYearsOnly(
  id, { min=0, max=80, store='text', width='680px', suffixSize='1.1rem' } = {}
){
  const old = document.getElementById(id);
  if (!old) return;

  const box = document.createElement('div');
  box.style.display = 'flex';
  box.style.alignItems = 'center';
  box.style.gap = '8px';

  const num = document.createElement('input');
  num.type = 'number';
  num.id = id + '_num';
  num.min = String(min);
  num.max = String(max);
  num.step = '1';
  num.inputMode = 'numeric';
  num.style.maxWidth = width;
  num.className = old.className || 'form-control';
  if (old.placeholder) num.placeholder = old.placeholder;
  if (old.hasAttribute('required')) num.required = true;

  const suf = document.createElement('span');
  suf.id = id + '_sufijo';
  suf.textContent = 'años';
  suf.style.fontSize = suffixSize;
  suf.style.fontWeight = '600';
  suf.style.lineHeight = '1';

  const hid = document.createElement('input');
  hid.type = 'hidden';
  hid.id = id;                        // <- conserva id original
  hid.name = old.getAttribute('name') || id;

  (function prefill(){
    const m = (old.value||'').match(/(\d+)/);
    if (m) num.value = String(Math.max(min, Math.min(max, parseInt(m[1],10))));
  })();

  const clamp = n => Math.max(min, Math.min(max, n));
  function actualizar(){
    const n = num.value === '' ? null : clamp(parseInt(num.value,10));
    if (n == null || Number.isNaN(n)) {
      suf.textContent = 'años';
      hid.value = '';
    } else {
      suf.textContent = (n === 1) ? 'año' : 'años';
      hid.value = (store === 'num') ? String(n) : `${n} ${n===1?'año':'años'}`;
    }
  }

  num.addEventListener('wheel', e => e.preventDefault(), { passive:false });
  num.addEventListener('keydown', e => { if (['e','E','+','-','.'].includes(e.key)) e.preventDefault(); });
  num.addEventListener('input', actualizar);
  num.addEventListener('change', actualizar);

  old.replaceWith(box);
  box.appendChild(num);
  box.appendChild(suf);
  box.appendChild(hid);

  actualizar();
};

// Inicializa #tiempo_empleo
document.addEventListener('DOMContentLoaded', () => {
  upgradeInputToYearsOnly('tiempo_empleo', { max: 80, store: 'text', width: '200px' });
});

//horario 
document.addEventListener('DOMContentLoaded', () => {
  // Rango con intervalos de 15 min, de 06:00 a 22:00
  upgradeInputToTimeRangeFancy('horario_trabajo', {
    dayStart: '06:00',
    dayEnd: '22:00',
    stepMinutes: 15,
    minGap: 15 // la hora de fin debe ser > inicio por al menos 15 min
  });
});



// --- Pago casa ---
// ===== Helpers MXN (idempotentes: no se redeclaran si ya existen) =====
window.parseMXN = window.parseMXN || function(str=''){
  const clean = String(str)
    .replace(/[^0-9.,-]/g, '')   // deja dígitos y separadores
    .replace(/\./g, '')          // quita miles con punto
    .replace(',', '.');          // coma -> punto decimal
  const n = parseFloat(clean);
  return Number.isFinite(n) ? Math.max(0, n) : null; // sin negativos
};
window.fmtMXN = window.fmtMXN || function(n){
  return new Intl.NumberFormat('es-MX', {
    style: 'currency', currency: 'MXN', minimumFractionDigits: 2, maximumFractionDigits: 2
  }).format(n);
};

// ===== Conecta un input texto para que se comporte como dinero (MXN) =====
function wireCurrencyField(id){
  const inp = document.getElementById(id);
  if (!inp) return;

  inp.setAttribute('inputmode','decimal');
  inp.setAttribute('autocomplete','off');
  if (!inp.placeholder) inp.placeholder = '$ 0.00';

  // Al enfocar: muestra número crudo (editable, sin $)
  inp.addEventListener('focus', () => {
    const n = parseMXN(inp.value);
    inp.value = n == null ? '' : String(n).replace('.', ','); // opcional: coma visible
  });

  // Mientras escribe: sólo dígitos y separadores básicos
  inp.addEventListener('input', () => {
    inp.value = inp.value.replace(/[^0-9.,]/g, '');
  });

  // Al salir: formatea como MXN
  inp.addEventListener('blur', () => {
    const n = parseMXN(inp.value);
    inp.value = n == null ? '' : fmtMXN(n);
  });

  // Si ya venía con algo, formatearlo de inicio
  if (inp.value.trim()) {
    const n = parseMXN(inp.value);
    if (n != null) inp.value = fmtMXN(n);
  }
}


// En donde ya tienes tu init de montos MXN:
document.addEventListener('DOMContentLoaded', () => {
  [
    'pago_casa',
    'pago_servicios',
    'pago_otros',
    'gasto_mensual',
    'valor_casa',
    'saldo_hipoteca',
    'valor_auto',
    'mensualidad_auto'   // 👈 nuevo
  ].forEach(wireCurrencyField);
});





// ===== Parentesco Datos de Vivienda del Solicitante =====
(function(){
  const _nrm = window._nrm || (s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim());

  // Lista (con pares masc/fem y opciones generales)
  const PARENTESCOS = [
    'Madre','Padre',
    'Esposa','Esposo','Cónyuge','Unión libre / Pareja',
    'Hija','Hijo',
    'Hermana','Hermano',
    'Abuela','Abuelo',
    'Nieta','Nieto',
    'Tía','Tío',
    'Sobrina','Sobrino',
    'Prima','Primo',
    'Suegra','Suegro',
    'Cuñada','Cuñado',
    'Yerno','Nuera',
    'Madrastra','Padrastro',
    'Hijastra','Hijastro',
    'Tutor(a)',
    'Amiga','Amigo',
    'Vecina','Vecino',
    'Compañera de trabajo','Compañero de trabajo',
    'Jefa','Jefe',
    'Otro'
  ];

  // Quita duplicados por normalización y ordena
  const OPCIONES = Array.from(
    new Map(PARENTESCOS.map(x => [_nrm(x), x])).values()
  ).sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));

  function poblarSelectParentesco(selectEl){
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = ''; ph.disabled = true; ph.selected = true; ph.textContent = 'Seleccione parentesco…';
    selectEl.appendChild(ph);
    OPCIONES.forEach(txt=>{
      const opt = document.createElement('option');
      opt.value = txt; opt.textContent = txt;
      selectEl.appendChild(opt);
    });
  }

  function upgradeInputToSelectParentesco(id='parentesco'){
    const old = document.getElementById(id);
    if (!old) return;

    // Si ya es <select>, sólo poblar
    if (old.tagName.toLowerCase()==='select'){ poblarSelectParentesco(old); return; }

    // Crear <select> clonando atributos útiles
    const sel = document.createElement('select');
    sel.id = old.id;
    sel.name = old.getAttribute('name') || old.id;
    sel.className = old.className || 'form-control';
    if (old.hasAttribute('required')) sel.required = true;

    const previo = (old.value||'').trim();
    old.replaceWith(sel);
    poblarSelectParentesco(sel);

    // Si había texto previo, intenta seleccionarlo por coincidencia "tolerante"
    if (previo){
      const objetivo = _nrm(previo);
      const match = Array.from(sel.options).find(o => _nrm(o.value||o.textContent) === objetivo);
      if (match) sel.value = match.value;
    }

    // Validación visual
    const validar = () => {
      const ok = sel.value !== '' && sel.selectedIndex > 0;
      sel.classList.remove('is-valid','is-invalid');
      if (ok){
        sel.classList.add('is-valid'); sel.setCustomValidity(''); sel.setAttribute('aria-invalid','false');
      } else {
        sel.classList.add('is-invalid'); sel.setCustomValidity('Seleccione un parentesco'); sel.setAttribute('aria-invalid','true');
      }
    };
    sel.addEventListener('change', validar);
    sel.addEventListener('blur',   validar);
    validar();
  }

  // Auto-init al cargar
  document.addEventListener('DOMContentLoaded', () => {
    upgradeInputToSelectParentesco('parentesco');
  });
})();





// ============ 7 SECCION DEL CODEUDOR Y FUNCIONES ============
/* ==== Paso 7 (Co-deudor) – versión aislada para evitar conflictos ==== */
;(function initCoDeudorStep(){
  'use strict';
  if (window.__coStep7Loaded) return; // evita doble carga
  window.__coStep7Loaded = true;

  /* ============== Helpers idempotentes (reusa si existen) ============== */
  const _nrm = window._nrm || (s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim());
  if (!window._nrm) window._nrm = _nrm;

  const NORM = window.norm || _nrm;
  if (!window.norm) window.norm = NORM;

  const mxNorm = window.mxNorm || function(s=''){
    return (s||'')
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
      .replace(/[^A-Za-zÑñ\s]/g,'')
      .replace(/\s+/g,' ').trim();
  };
  if (!window.mxNorm) window.mxNorm = mxNorm;

  const primeraVocalInterna = window.primeraVocalInterna || function(s=''){
    const m = (s||'').toUpperCase().slice(1).match(/[AEIOUÑ]/); return m ? m[0] : 'X';
  };
  if (!window.primeraVocalInterna) window.primeraVocalInterna = primeraVocalInterna;

  const primeraConsonanteInterna = window.primeraConsonanteInterna || function(s=''){
    const m = (s||'').toUpperCase().slice(1).match(/[BCDFGHJKLMNÑPQRSTVWXYZ]/); return m ? m[0] : 'X';
  };
  if (!window.primeraConsonanteInterna) window.primeraConsonanteInterna = primeraConsonanteInterna;

  const parseFechaFlexible = window.parseFechaFlexible || function(str=''){
    const s=(str||'').trim();
    if(/^\d{4}-\d{2}-\d{2}$/.test(s)){ const [Y,M,D]=s.split('-').map(Number); return new Date(Y,M-1,D); }
    if(/^\d{2}\/\d{2}\/\d{4}$/.test(s)){ const [D,M,Y]=s.split('/').map(Number); return new Date(Y,M-1,D); }
    return new Date(NaN);
  };
  if (!window.parseFechaFlexible) window.parseFechaFlexible = parseFechaFlexible;

  const generoALetra = window.generoALetra || function(txt=''){
    const s = String(txt).trim().toLowerCase();
    if (s==='h' || s.startsWith('hom') || s==='masculino' || s.startsWith('mas')) return 'H';
    if (s==='m' || s.startsWith('muj') || s==='femenino' || s.startsWith('fem')) return 'M';
    return '';
  };
  if (!window.generoALetra) window.generoALetra = generoALetra;

  // Estados y mapas (solo si faltan)
  const MAPA_ESTADOS = window.mapaEstados || {
    'mexico':'Estado de México','coahuila de zaragoza':'Coahuila','michoacan de ocampo':'Michoacán',
    'veracruz de ignacio de la llave':'Veracruz','queretaro de arteaga':'Querétaro','distrito federal':'Ciudad de México'
  };
  if (!window.mapaEstados) window.mapaEstados = MAPA_ESTADOS;

  const ESTADOS_MX = window.ESTADOS_MX || [
    'Aguascalientes','Baja California','Baja California Sur','Campeche','Coahuila','Colima','Chiapas','Chihuahua',
    'Ciudad de México','Durango','Estado de México','Guanajuato','Guerrero','Hidalgo','Jalisco','Michoacán',
    'Morelos','Nayarit','Nuevo León','Oaxaca','Puebla','Querétaro','Quintana Roo','San Luis Potosí',
    'Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz','Yucatán','Zacatecas'
  ];
  if (!window.ESTADOS_MX) window.ESTADOS_MX = ESTADOS_MX;

  const CURP_ENT = window.CURP_ENT || {
    'Aguascalientes':'AS','Baja California':'BC','Baja California Sur':'BS','Campeche':'CC','Coahuila':'CL','Colima':'CM',
    'Chiapas':'CS','Chihuahua':'CH','Ciudad de México':'DF','Durango':'DG','Guanajuato':'GT','Guerrero':'GR','Hidalgo':'HG',
    'Jalisco':'JC','Estado de México':'MC','Michoacán':'MN','Morelos':'MS','Nayarit':'NT','Nuevo León':'NL','Oaxaca':'OC',
    'Puebla':'PL','Querétaro':'QT','Quintana Roo':'QR','San Luis Potosí':'SP','Sinaloa':'SL','Sonora':'SR','Tabasco':'TC',
    'Tamaulipas':'TS','Tlaxcala':'TL','Veracruz':'VZ','Yucatán':'YN','Zacatecas':'ZS'
  };
  if (!window.CURP_ENT) window.CURP_ENT = CURP_ENT;

  /* ============== UI helpers ============== */
  function setValidityStyle(el, state){
    if(!el) return;
    el.classList.remove('is-valid','is-invalid'); el.style.borderColor=''; el.style.boxShadow='';
    if(state==='valid'){ el.classList.add('is-valid'); el.style.borderColor='#198754'; el.style.boxShadow='0 0 0 .2rem rgba(25,135,84,.25)'; }
    if(state==='invalid'){ el.classList.add('is-invalid'); el.style.borderColor='#dc3545'; el.style.boxShadow='0 0 0 .2rem rgba(220,53,69,.25)'; }
  }
  function getOrCreateHint(afterEl, id){
    let h=document.getElementById(id);
    if(!h){ h=document.createElement('small'); h.id=id; h.style.display='block'; h.style.marginTop='4px'; h.style.fontSize='12px'; afterEl.insertAdjacentElement('afterend',h); }
    return h;
  }

  /* ============== Select de estados (32) ============== */
  function poblarSelectEstadosMX(selectEl, placeholder='Seleccione un estado…'){
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value=''; ph.disabled=true; ph.selected=true; ph.textContent=placeholder;
    selectEl.appendChild(ph);
    (window.ESTADOS_MX || ESTADOS_MX).forEach(e=>{
      const opt = document.createElement('option'); opt.value=e; opt.textContent=e; selectEl.appendChild(opt);
    });
  }
  function selectEstadoByName(selectEl, nombre){
    if (!selectEl || !nombre) return false;
    const tgt=_nrm(nombre);
    const m = Array.from(selectEl.options||[]).find(o=>_nrm(o.value||o.textContent)===tgt);
    if (m){ selectEl.value=m.value; selectEl.dispatchEvent(new Event('change',{bubbles:true})); return true; }
    return false;
  }
  function upgradeInputToSelectEstadoMX(id){
    const old=document.getElementById(id); if(!old) return;
    if (old.tagName.toLowerCase()==='select'){ poblarSelectEstadosMX(old); return; }
    const sel=document.createElement('select');
    sel.id=old.id; sel.name=old.getAttribute('name')||old.id; sel.className=old.className||'form-control';
    if (old.hasAttribute('required')) sel.required=true;
    const previo=(old.value||'').trim();
    old.replaceWith(sel); poblarSelectEstadosMX(sel);
    if (previo) selectEstadoByName(sel, previo);
  }

  /* ============== RFC (10→13) ============== */
  function calcularRFC10_Co(){
    const el = document.getElementById('form_co_rfc'); if(!el) return;
    const nom = mxNorm(document.getElementById('form_co_nombre')?.value||'').toUpperCase();
    const apP = mxNorm(document.getElementById('form_co_apellido_paterno')?.value||'').toUpperCase();
    const apM = mxNorm(document.getElementById('form_co_apellido_materno')?.value||'').toUpperCase();
    const fecha = parseFechaFlexible(document.getElementById('form_co_nacimiento')?.value||'');
    if (isNaN(fecha.getTime())) return;

    const STOP=new Set(['JOSE','J','J.','MARIA','MA','MA.']);
    const partes = nom.split(/\s+/).filter(Boolean);
    let primerNombre = partes[0]||'X'; if (STOP.has(primerNombre)) primerNombre = partes[1]||primerNombre;

    const YY=String(fecha.getFullYear()).slice(-2), MM=String(fecha.getMonth()+1).padStart(2,'0'), DD=String(fecha.getDate()).padStart(2,'0');

    let base4=''; base4 += (apP[0]||'X'); base4 += primeraVocalInterna(apP); base4 += (apM[0]||'X'); base4 += (primerNombre[0]||'X');
    const base10 = (base4 + YY + MM + DD).toUpperCase().replace(/[^A-Z0-9Ñ]/g,'');

    const actual = (el.value||'').toUpperCase().replace(/\s+/g,'');
    el.value = actual.startsWith(base10) ? actual.slice(0,13) : base10;

    const hint = getOrCreateHint(el,'form_co_rfc_hint');
    const L = el.value.length;
    if (L<10){ hint.textContent=`Faltan ${10-L} de la base (LLLL+YYMMDD).`; hint.style.color='#cc8b00'; setValidityStyle(el,'invalid'); }
    else if (L<13){ hint.textContent=`Faltan ${13-L} de homoclave (13 en total).`; hint.style.color='#cc8b00'; setValidityStyle(el,'invalid'); }
    else if (L===13){ hint.textContent='RFC completo (13/13).'; hint.style.color='#198754'; setValidityStyle(el,'valid'); }
    else { hint.textContent=`Sobran ${L-13}.`; hint.style.color='#dc3545'; setValidityStyle(el,'invalid'); }
  }
  function wireRFC_Co(){
    const el=document.getElementById('form_co_rfc'); if(!el) return;
    el.setAttribute('maxlength','13'); el.style.textTransform='uppercase';
    el.addEventListener('input', ()=>{ el.value=el.value.toUpperCase().replace(/[^A-Z0-9Ñ]/g,'').slice(0,13); calcularRFC10_Co(); });
    ['form_co_nombre','form_co_apellido_paterno','form_co_apellido_materno','form_co_nacimiento'].forEach(id=>{
      const e=document.getElementById(id); e && e.addEventListener('input', calcularRFC10_Co);
    });
    calcularRFC10_Co();
  }

  /* ============== CURP (16→18) ============== */
function calcularCURP_Co(){
  const el = document.getElementById('form_co_curp'); if(!el) return;

  const nombres = (document.getElementById('form_co_nombre')?.value||'');
  const apPat   = (document.getElementById('form_co_apellido_paterno')?.value||'');
  const apMat   = (document.getElementById('form_co_apellido_materno')?.value||'');

  // --- Género robusto + fallback al que ya tenía la CURP ---
  const generoNode = document.getElementById('form_co_genero');
  const generoTexto = generoNode
    ? (generoNode.value || generoNode.options[generoNode.selectedIndex]?.text || '')
    : '';
  const generoPrev  = ((el.value||'').toUpperCase()[10] || ''); // 11º char en CURP previa
  const genero      = generoALetra(generoTexto) || (['H','M'].includes(generoPrev) ? generoPrev : '');

  const fecha   = parseFechaFlexible(document.getElementById('form_co_nacimiento')?.value||'');
  const estadoN = (document.getElementById('form_co_entidad')?.value||'');
  if (isNaN(fecha.getTime())) return;

  const YY = String(fecha.getFullYear()).slice(-2);
  const MM = String(fecha.getMonth()+1).padStart(2,'0');
  const DD = String(fecha.getDate()).padStart(2,'0');

  const N = mxNorm(nombres).toUpperCase();
  const P = mxNorm(apPat).toUpperCase();
  const M = mxNorm(apMat).toUpperCase();

  const STOP = new Set(['JOSE','J','J.','MARIA','MA','MA.']);
  const partes = N.split(/\s+/).filter(Boolean);
  let primerNombre = partes[0] || 'X';
  if (STOP.has(primerNombre)) primerNombre = partes[1] || primerNombre;

  let base16='';
  base16 += (P[0]||'X');
  base16 += primeraVocalInterna(P);
  base16 += (M[0]||'X');
  base16 += (primerNombre[0]||'X');
  base16 += YY+MM+DD;
  base16 += (genero==='H'||genero==='M') ? genero : 'X';           // ← ya no se pierde
  base16 += (window.CURP_ENT||CURP_ENT)[estadoN] || 'NE';
  base16 += primeraConsonanteInterna(P);
  base16 += primeraConsonanteInterna(M);
  base16 += primeraConsonanteInterna(primerNombre);

  const actual=(el.value||'').toUpperCase().replace(/\s+/g,'');
  el.value = actual.startsWith(base16) ? actual.slice(0,18) : base16;

    const hint=getOrCreateHint(el,'form_co_curp_hint');
    const L=el.value.length;
    if (L<16){ hint.textContent=`Faltan ${16-L} de la base.`; hint.style.color='#cc8b00'; setValidityStyle(el,'invalid'); }
    else if (L===16){ hint.textContent='Faltan 2 de homoclave.'; hint.style.color='#cc8b00'; setValidityStyle(el,'invalid'); }
    else if (L===17){ hint.textContent='Falta 1 de homoclave.'; hint.style.color='#cc8b00'; setValidityStyle(el,'invalid'); }
    else if (L===18){ hint.textContent='CURP completa (18/18).'; hint.style.color='#198754'; setValidityStyle(el,'valid'); }
    else { hint.textContent=`Sobran ${L-18}.`; hint.style.color='#dc3545'; setValidityStyle(el,'invalid'); }
  }
  function wireCURP_Co(){
    const el=document.getElementById('form_co_curp'); if(!el) return;
    el.setAttribute('maxlength','18'); el.style.textTransform='uppercase';
    el.addEventListener('input', ()=>{ el.value=el.value.toUpperCase().replace(/[^A-Z0-9Ñ]/g,'').slice(0,18); calcularCURP_Co(); autoPaisYNacionalidadDesdeCURP_Co(); });
    ['form_co_nombre','form_co_apellido_paterno','form_co_apellido_materno','form_co_nacimiento','form_co_genero','form_co_entidad']
      .forEach(id=>{ const e=document.getElementById(id); e && e.addEventListener('change', ()=>{ calcularCURP_Co(); autoPaisYNacionalidadDesdeCURP_Co(); }); });
    calcularCURP_Co();
  }

  function autoPaisYNacionalidadDesdeCURP_Co(){
    const el = document.getElementById('form_co_curp'); if(!el) return;
    const curp=(el.value||'').toUpperCase();
    if (curp.length<13) return;
    const code = curp.slice(11,13); // 12-13
    const paisN = document.getElementById('form_co_pais_nacimiento');
    const nac   = document.getElementById('form_co_nacionalidad');
    if (code==='NE'){ paisN && (paisN.value='');       nac && !nac.value && (nac.value='Extranjera'); }
    else            { paisN && (paisN.value='México'); nac && !nac.value && (nac.value='Mexicana');  }
  }

  /* ============== CP → colonia/mun/estado/país ============== */
  (function setupCpCo(){
    const cp = document.getElementById('form_co_cp'); if(!cp) return;
    const get = id => document.getElementById(id);
    const limpiar = () => {
      ['form_co_municipio','form_co_estado','form_co_pais'].forEach(id=>{ const e=get(id); e && (e.value=''); });
      const col=get('form_co_colonia'); if(col){ if(col.tagName.toLowerCase()==='select') col.innerHTML=''; else col.value=''; }
    };

    let last='';
async function buscar(){
  const raw=(cp.value||'').replace(/\D+/g,'').slice(0,5);
  if (cp.value!==raw) cp.value=raw;
  if (!/^\d{5}$/.test(raw)){ last=''; limpiar(); return; }
  if (raw===last) return; last=raw;

  try{
    const base=(typeof window.BASE_PUBLIC==='string')?window.BASE_PUBLIC:'/';
    const url = `${base}app/controllers/cp_buscar.php?cp=${encodeURIComponent(raw)}&t=${Date.now()}`;
    const res = await fetch(url,{cache:'no-store'}); if(!res.ok) throw new Error(res.status);
    const data=await res.json();

    const mun=get('form_co_municipio'), edo=get('form_co_estado'), col=get('form_co_colonia'), pais=get('form_co_pais');

    if(data && data.success){
      const estadoResp=data.estado||'';
      const estadoBonito=(window.mapaEstados||MAPA_ESTADOS)[NORM(estadoResp)]||estadoResp;

      mun  && (mun.value  = data.municipio || '');
      pais && (pais.value = 'México');

      // Estado del domicilio (form_co_estado)
      if (edo){
        if (edo.tagName.toLowerCase()==='select'){
          if(!selectEstadoByName(edo,estadoBonito)) selectEstadoByName(edo,estadoResp);
        } else {
          edo.value = estadoBonito || '';
        }
      }

      // ⚑ Entidad federativa de nacimiento (form_co_entidad)
// Autollenar Entidad federativa de nacimiento si está vacía
const entidadNac = get('form_co_entidad');
if (entidadNac) {
  if (entidadNac.tagName.toLowerCase() === 'select') {
    if (!entidadNac.value) {
      if (!selectEstadoByName(entidadNac, estadoBonito)) {
        selectEstadoByName(entidadNac, estadoResp);
      }
    }
  } else {
    if (!entidadNac.value) entidadNac.value = estadoBonito || '';
  }
}


      // Colonias
if (col && Array.isArray(data.colonias) && data.colonias.length){
  const unicas = [...new Set(data.colonias.map(c=>String(c||'').trim()).filter(Boolean))]
    .sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));

  if (col.tagName.toLowerCase()==='select'){
    const prev = col.value;
    col.innerHTML = '<option value="" disabled selected>Selecciona colonia…</option>';
    unicas.forEach(v => col.add(new Option(v, v, false, false)));

    hookOtroEnSelect(col) // << AQUI

    if (prev && unicas.some(x => NORM(x)===NORM(prev))) col.value = prev;
    col.dispatchEvent(new Event('change',{bubbles:true}));
  } else if (!col.value.trim()){
    // era input (por “Otro…”)
    // no auto-llenar si el usuario ya escribió
  }
}

    } else {
      limpiar();
    }
  }catch(e){
    console.warn('[CP co] Error',e);
    limpiar();
  }
}


    let t; cp.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(buscar,300); });
    cp.addEventListener('blur', buscar);
  })();

  /* ============== Parentesco (input → select) ============== */
  function upgradeInputToParentesco(id){
    const old=document.getElementById(id); if(!old) return;
    const opts=['Padre/Madre','Cónyuge/Esposo(a)','Hijo/Hija','Concubino/Concubina','Hermano/Hermana','Abuelo/Abuela','Tío/Tía','Primo/Prima','Suegro(a)','Cuñado(a)','Amigo(a)','Otro'];
    const sel=document.createElement('select');
    sel.id=old.id; sel.name=old.getAttribute('name')||old.id; sel.className=old.className||'form-control';
    const ph=new Option('Seleccione','',true,true); ph.disabled=true; sel.add(ph);
    opts.forEach(x=>sel.add(new Option(x,x)));
    const previo=(old.value||'').trim();
    old.replaceWith(sel);
    if (previo){ const m=Array.from(sel.options).find(o=>_nrm(o.value)===_nrm(previo)); if(m) sel.value=m.value; }
  }

  /* ============== Tiempo (años) y Horario (dropdown) ============== */
  function tryUpgradeYears(id){ if (typeof window.upgradeInputToYearsOnly==='function'){ window.upgradeInputToYearsOnly(id,{max:80,store:'text'}); } }
  function tryUpgradeTimeRange(id){ if (typeof window.upgradeInputToTimeRangeFancy==='function'){ window.upgradeInputToTimeRangeFancy(id,{dayStart:'08:00',dayEnd:'20:00',stepMinutes:15}); } }

  /* ============== INIT ============== */
  document.addEventListener('DOMContentLoaded', () => {
    upgradeInputToSelectEstadoMX('form_co_estado');       // Estado (select 32)
    upgradeInputToParentesco('form_co_parentesco');       // Parentesco (select)
    tryUpgradeYears('form_co_tiempo');                    // Tiempo en domicilio
    tryUpgradeTimeRange('form_co_mejor_hora');            // Horario contacto
    wireRFC_Co();                                         // RFC dinámico
    wireCURP_Co();                                        // CURP dinámico + país/nacionalidad
  }, { once: true });
['colonia','colonia_trabajo','form_co_colonia']
    .map(id => document.getElementById(id))
    .forEach(el => el && el.tagName.toLowerCase()==='select' && hookOtroEnSelect(el));
})();


  const sel = document.getElementById('form_co_colonia');
  if (sel && sel.tagName.toLowerCase() === 'select'){
    sel.addEventListener('change', () => {
      if (sel.value === '__otro__'){
        // se vuelve input manteniendo el MISMO id
        window.convertirSelectAInputMismoId(sel);
        // No necesitas guardar referencia: en tu código usas get('form_co_colonia') cuando lo ocupas.
      }
    });
  }






(async function setAsesorFromSession() {
  // Ajusta la base si tu app está en /public
  const hasPublic = location.pathname.includes('/public/');
  const P = hasPublic ? '/public' : '';
  const URL_ME = `${P}/app/controllers/auth/me.php`;

  // Helpers
  const $  = (s) => document.querySelector(s);
  const normUpper = (s='') => s
      .normalize('NFD').replace(/[\u0300-\u036f]/g,'') // sin acentos
      .replace(/\s+/g,' ').trim().toUpperCase();

  const sel = $('#atendio');
  const hid = $('#atendido_por');
  if (!sel || !hid) return;

  let nombreSesion = '';

  try {
    const res = await fetch(URL_ME, { credentials: 'include' });
    const me  = await res.json();

    // Trata de encontrar el nombre según varios formatos comunes
    nombreSesion =
      me?.user?.nombre ??
      me?.data?.nombre ??
      me?.nombre ??
      me?.user?.full_name ??
      me?.full_name ?? '';

    // Fallback opcional: si guardas en sessionStorage/localStorage
    if (!nombreSesion) {
      nombreSesion = sessionStorage.getItem('asesor_nombre') ||
                     localStorage.getItem('asesor_nombre') || '';
    }
  } catch (e) {
    console.warn('No se pudo leer el usuario de sesión:', e);
  }

  if (!nombreSesion) {
    console.warn('No hay nombre de asesor en sesión; se deja el select editable.');
    return;
  }

  const N = normUpper(nombreSesion);

  // 1) Coloca/actualiza la opción seleccionada
  //    - si ya existe la opción con ese texto/valor, sólo selecciónala
  //    - si no existe, reemplaza opciones por una sola (la del asesor)
  const opts = Array.from(sel.options || []);
  const match = opts.find(o => normUpper(o.value) === N || normUpper(o.text) === N);

  if (match) {
    sel.value = match.value || match.text;
  } else {
    sel.innerHTML = ''; // limpiamos y dejamos sólo el asesor logueado
    const opt = document.createElement('option');
    opt.value = N;
    opt.textContent = N;
    opt.selected = true;
    sel.appendChild(opt);
  }

  // 2) Copia al hidden para asegurar envío
  hid.value = N;

  // 3) “Bloquea” visualmente el select (para que no lo alteren), pero sigue enviando valor
  sel.classList.add('locked');
  sel.addEventListener('mousedown', e => e.preventDefault());
  sel.addEventListener('keydown',  e => e.preventDefault());
  // Si quieres permitir que un admin lo cambie, quita estos listeners según el rol.

})();


