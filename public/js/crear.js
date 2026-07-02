

// 100% inmune a zonas horarias para cadenas 'YYYY-MM-DD'
function formatearYYYYMMDDaLargoMX(str) {
  if (!str) return null;
  const m = String(str).trim().split("T")[0].split(" ")[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!m) return null;
  const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
  const yyyy = m[1], mm = parseInt(m[2], 10), dd = m[3];
  return `${dd} de ${meses[mm-1]} de ${yyyy}`;
}

function generarFormato() {

  document.getElementById('res-atendio').textContent = document.getElementById('atendio').value;
  document.getElementById('res-medio').textContent = document.getElementById('medio').value;
document.getElementById('res-monto').textContent = parseFloat(document.getElementById('monto').value || 0).toFixed(2);
document.getElementById('res-plazo').textContent = document.getElementById('plazo').value;

const tasaMensual = document.getElementById('tasa_mensual')?.value || '10.5';
const resTasaMensual = document.getElementById('res-tasa-mensual');
if (resTasaMensual) {
  resTasaMensual.textContent = `${parseFloat(tasaMensual || 0).toFixed(2)}%`;
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

// LUGAR Y FECHA (sin new Date)
const lugarEl  = document.getElementById('lugar');
const fechaEl  = document.getElementById('fecha');
const resLugar = document.getElementById('res-lugar');
const resFecha = document.getElementById('res-fecha');

resLugar.textContent = (lugarEl?.value || '').trim() || 'N/A';

const fechaStr = fechaEl?.value || '';
resFecha.textContent = formatearYYYYMMDDaLargoMX(fechaStr) || 'N/A';

console.log('fecha input:', fechaStr, '→ impresa:', resFecha.textContent);
// Mostrar el tipo de vivienda seleccionado
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
const poseeAuto = document.querySelector('input[name="posee_auto"]:checked');
document.getElementById('res-posee-auto').textContent = poseeAuto ? poseeAuto.value : '';

document.getElementById('res-auto-detalle').textContent = document.getElementById('marca_auto')?.value || '';
document.getElementById('res-auto-valor').textContent = document.getElementById('valor_auto')?.value || '';
document.getElementById('res-auto-empresa').textContent = document.getElementById('empresa_auto')?.value || '';
document.getElementById('res-auto-mensualidad').textContent = document.getElementById('mensualidad_auto')?.value || '';
  // Mostrar formato final
  document.getElementById('formulario').classList.add('hidden');
  document.getElementById('formatoFinal').classList.remove('hidden');
  // Fecha actual
const hoy = new Date();
const fechaFormateada = hoy.toLocaleDateString('es-MX', { year: 'numeric', month: '2-digit', day: '2-digit' });
document.getElementById('fecha-consulta').textContent = fechaFormateada;
copiarFirmaVista(); // Esto reflejará la firma en el contrato

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


const lugarFuncionario = document.getElementById('lugar_funcionario')?.value.trim() || 'N/A';
const fechaFuncionario = document.getElementById('fecha_funcionario')?.value;

const fechaFormateadaFuncionario = fechaFuncionario
  ? formatearYYYYMMDDaLargoMX(fechaFuncionario)
  : 'N/A';

document.getElementById('lugar-funcionario').textContent = lugarFuncionario;
document.getElementById('fecha-funcionario').textContent = fechaFormateadaFuncionario;
}

document.addEventListener('DOMContentLoaded', () => {
  const ids = ['firmaCanvas1', 'firmaCanvas2'];
  ids.forEach(id => initSignatureCanvas(document.getElementById(id)));

  // ——— borrar firma (respeta tu API) ———
  window.borrarFirma = function(canvasId, vistas = []) {
    const canvas = document.getElementById(canvasId);
    if (canvas) {
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
    vistas.forEach(id => {
      const vista = document.getElementById(id);
      if (vista) {
        const ctxVista = vista.getContext('2d');
        ctxVista.clearRect(0, 0, vista.width, vista.height);
      }
    });
  };

  // ——— copiar a vistas (tu misma lógica) ———
  window.copiarFirmaVista = function () {
    const pares = [
      { origen: 'firmaCanvas1', destino: 'firmaCanvasVista1' },
      { origen: 'firmaCanvas2', destino: 'firmaCanvasVista2' }
    ];
    pares.forEach(par => {
      const origen = document.getElementById(par.origen);
      const destino = document.getElementById(par.destino);
      if (!origen || !destino) return;
      const ctxVista = destino.getContext('2d');
      const img = new Image();
      img.onload = () => {
        ctxVista.clearRect(0, 0, destino.width, destino.height);
        const ratio = Math.min(destino.width / img.width, destino.height / img.height);
        const newW = img.width * ratio;
        const newH = img.height * ratio;
        const offsetX = (destino.width - newW) / 2;
        const offsetY = (destino.height - newH) / 2;
        ctxVista.drawImage(img, offsetX, offsetY, newW, newH);
      };
      img.src = origen.toDataURL('image/png');
    });
  };
});

// ————————————————————————————————
// Inicializa un canvas de firma compatible con mouse/táctil/stylus
// ————————————————————————————————
function initSignatureCanvas(canvas) {
  if (!canvas) return;
  const ctx = canvas.getContext('2d');

  // Estilo del trazo
  ctx.lineCap = 'round';
  ctx.lineJoin = 'round';
  ctx.strokeStyle = '#000000';
  let baseWidth = 2; // ancho base

  // Evita scroll/zoom por gesto táctil sobre el canvas
  canvas.style.touchAction = 'none';
  canvas.style.background = '#ffffff';
  // (Opcional) borde para ver el área
  if (!canvas.style.border) canvas.style.border = '1px dashed #ccc';

  // Manejo de alta densidad (retina)
  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    const rect = canvas.getBoundingClientRect();
    const cssW = rect.width || canvas.width;   // si no hay CSS, usa atributo
    const cssH = rect.height || canvas.height;

    // Ajusta el buffer interno al tamaño CSS * ratio
    canvas.width = Math.round(cssW * ratio);
    canvas.height = Math.round(cssH * ratio);

    // Resetea transform y escala para que las coords sean en px CSS
    ctx.setTransform(1,0,0,1,0,0);
    ctx.scale(ratio, ratio);
  }
  // Asegura que el tamaño CSS exista: respeta tus width/height HTML como CSS
  if (!canvas.style.width)  canvas.style.width  = canvas.getAttribute('width')  ? canvas.getAttribute('width') + 'px'  : '350px';
  if (!canvas.style.height) canvas.style.height = canvas.getAttribute('height') ? canvas.getAttribute('height') + 'px' : '200px';
  resizeCanvas();
  window.addEventListener('resize', resizeCanvas);

  let dibujando = false;

  const getPos = (e) => {
    const rect = canvas.getBoundingClientRect();
    const clientX = (e.clientX !== undefined) ? e.clientX : (e.touches && e.touches[0]?.clientX);
    const clientY = (e.clientY !== undefined) ? e.clientY : (e.touches && e.touches[0]?.clientY);
    return {
      x: clientX - rect.left,
      y: clientY - rect.top
    };
  };

  const down = (e) => {
    dibujando = true;
    if (e.pointerId !== undefined && canvas.setPointerCapture) {
      try { canvas.setPointerCapture(e.pointerId); } catch {}
    }
    const { x, y } = getPos(e);
    ctx.beginPath();
    ctx.moveTo(x, y);
    e.preventDefault();
  };

  const move = (e) => {
    if (!dibujando) return;
    // Ajuste por presión si el dispositivo lo reporta (Apple Pencil, stylus)
    if ('pressure' in e && typeof e.pressure === 'number' && e.pressure > 0) {
      ctx.lineWidth = Math.max(1.5, baseWidth * e.pressure);
    } else {
      ctx.lineWidth = baseWidth;
    }
    const { x, y } = getPos(e);
    ctx.lineTo(x, y);
    ctx.stroke();
    e.preventDefault();
  };

  const up = (e) => {
    dibujando = false;
    e.preventDefault();
  };

  // Preferir Pointer Events (cubre mouse/touch/pen). Fallback a mouse/touch.
  if (window.PointerEvent) {
    canvas.addEventListener('pointerdown', down);
    canvas.addEventListener('pointermove', move);
    canvas.addEventListener('pointerup', up);
    canvas.addEventListener('pointercancel', up);
    canvas.addEventListener('pointerout', up);
    canvas.addEventListener('pointerleave', up);
  } else {
    // Mouse
    canvas.addEventListener('mousedown', down);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', up);
    // Touch (marcar passive:false para poder preventDefault y evitar scroll)
    canvas.addEventListener('touchstart', (e) => down(e), { passive: false });
    canvas.addEventListener('touchmove',  (e) => move(e), { passive: false });
    canvas.addEventListener('touchend', up, { passive: false });
    canvas.addEventListener('touchcancel', up, { passive: false });
  }
}

  const canvasFormulario = document.getElementById('firmaFormulario');
  const ctxFormulario = canvasFormulario.getContext('2d');
  let dibujandoFormulario = false;

  canvasFormulario.addEventListener('mousedown', (e) => {
    dibujandoFormulario = true;
    ctxFormulario.beginPath();
    ctxFormulario.moveTo(e.offsetX, e.offsetY);
  });

  canvasFormulario.addEventListener('mousemove', (e) => {
    if (dibujandoFormulario) {
      ctxFormulario.lineTo(e.offsetX, e.offsetY);
      ctxFormulario.stroke();

    }
  });

  canvasFormulario.addEventListener('mouseup', () => dibujandoFormulario = false);
  canvasFormulario.addEventListener('mouseout', () => dibujandoFormulario = false);

  function borrarFirmaFormulario() {
    ctxFormulario.clearRect(0, 0, canvasFormulario.width, canvasFormulario.height);

  }

  let currentStep = 0;
  const steps = document.querySelectorAll(".step");

  function showStep(n) {
    steps.forEach((step, index) => {
      step.classList.toggle("active", index === n);
    });
  }

  function nextPrev(n) {
    currentStep += n;
    if (currentStep < 0) currentStep = 0;
    if (currentStep >= steps.length) {
      alert("¡Formulario completado!");
      return;
    }
    showStep(currentStep);
  }

  document.addEventListener("DOMContentLoaded", () => {
    showStep(currentStep);
  });




let solicitudIdGlobal = null;

function setSolicitudId(id) {
  const v = String(id);
  solicitudIdGlobal = v;
  window.solicitudIdGlobal = v;
  sessionStorage.setItem('solicitud_id', v);
  // (opcional) si tienes inputs hidden con ese nombre:
  document.querySelectorAll('input[name="solicitud_id"]').forEach(i => i.value = v);
}

function getSolicitudId() {
  return sessionStorage.getItem('solicitud_id')
      || window.solicitudIdGlobal
      || solicitudIdGlobal
      || null;
}

// al cargar, “rescata” el id si ya existe
document.addEventListener('DOMContentLoaded', () => {
  const ss = sessionStorage.getItem('solicitud_id');
  if (ss) setSolicitudId(ss);
});

  (function () {
    const p = location.pathname;
    const i = p.indexOf('/public/');
    window.BASE_PUBLIC = (i !== -1) ? p.slice(0, i + '/public/'.length) : '/';
    console.log('BASE_PUBLIC =', BASE_PUBLIC);
  })();

// Sincroniza el hidden con el texto seleccionado del asesor
(function syncAtendidoHidden(){
  const sel = document.getElementById('atendio');
  const hid = document.getElementById('atendido_por');
  if (!sel || !hid) return;
  const update = () => {
    const v = sel.value?.trim() || '';
    // Si en tu BD guardas el nombre, puedes usar el texto visible:
    const txt = sel.options[sel.selectedIndex]?.text?.trim() || v;
    hid.value = txt;
  };
  sel.addEventListener('change', update);
  update();
})();

function guardarPaso1() {
  // 1) Modalidad desde el <select>
  const selMod = document.getElementById('contrato_modalidad');
  const contrato_modalidad = (selMod?.value || '').toUpperCase();

  // ✅ ahora acepta P10, SEM_P10, P10_ORD, P40 y P40_ORD
  const MODALIDADES_OK = ['P10', 'SEM_P10', 'P10_ORD', 'P40', 'P40_ORD'];

  if (!MODALIDADES_OK.includes(contrato_modalidad)) {
    Swal.fire({
      icon: 'warning',
      title: 'Modalidad inválida',
      text: 'Selecciona Unipersonal 10, Sem Personal 10, Personal 10 Ordinario, Personal 40 o Personal 40 Ordinario.'
    });
    return;
  }

  // 2) Asesor (texto mostrado), lo mandamos como atendido_por
  const atendioSel = document.getElementById('atendio');
  const atendido_por = atendioSel
    ? (atendioSel.value || atendioSel.options[atendioSel.selectedIndex]?.text || '')
    : '';

  // 3) Payload
  const fd = new FormData();
  fd.append('atendido_por', atendido_por);
  fd.append('medio', document.getElementById('medio').value);
fd.append('monto', document.getElementById('monto').value);
fd.append('plazo', document.getElementById('plazo').value);

const tasaMensual = document.getElementById('tasa_mensual')?.value || '10.5';
fd.append('tasa_mensual', tasaMensual);

fd.append('frecuencia', document.getElementById('frecuencia').value);
fd.append('contrato_modalidad', contrato_modalidad);

  // 4) create/update por folio guardado
  const folioActual = (typeof getSolicitudId === 'function') ? getSolicitudId() : null;
  if (folioActual) {
    fd.append('accion', 'update');
    fd.append('solicitud_id', folioActual);
  } else {
    fd.append('accion', 'create');
  }

  fetch(`${BASE_PUBLIC}app/controllers/guardar_solicitud.php`, { method:'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      console.log('Respuesta del servidor:', data);

      if (data.status === 'ok' || data.ok === true) {
        const id = data.solicitud_id || folioActual;
        if (id && typeof setSolicitudId === 'function') setSolicitudId(id);

        Swal.fire({
          icon: 'success',
          title: '¡Guardado!',
          text: 'Solicitud guardada correctamente.'
        }).then(() => nextPrev(1));
      } else {
        const msg = data.message || data.error || 'Ocurrió un problema al guardar.';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: msg
        });
      }
    })
    .catch(err => {
      console.error(err);
      Swal.fire({
        icon: 'error',
        title: 'Error inesperado',
        text: 'Ocurrió un error al guardar los datos.'
      });
    });
}


function guardarPaso2() {
  const solicitudId = sessionStorage.getItem('solicitud_id');
  if (!solicitudId) {
    Swal.fire({ icon: 'error', title: 'Falta el folio', text: 'No encontré el ID de solicitud.' });
    return;
  }

  const formData = new FormData();
  formData.append('solicitud_id', solicitudId);

  // Campos del paso 2
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

  // Loader bonito
  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(`${BASE_PUBLIC}app/controllers/guardar_datos_personales.php`, {
    method: 'POST',
    body: formData
  })
  .then(async (res) => {
    const txt = await res.text();
    let data;
    try { data = JSON.parse(txt); }
    catch { throw new Error('Respuesta no válida del servidor: ' + txt); }
    if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
    return data;
  })
  .then((data) => {
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: 'Datos personales guardados correctamente.',
      confirmButtonText: 'Continuar'
    }).then(() => nextPrev(1));
  })
  .catch((err) => {
    console.error('Error:', err);
    Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: err.message || 'Ocurrió un error al guardar los datos personales.'
    });
  });
}

function guardarPaso3() {
  const solicitud_id = getSolicitudId();
  if (!solicitud_id) { /* alerta */ return; }
  const formData = new FormData();
  formData.append('solicitud_id', solicitud_id);
  formData.append('solicitud_id', solicitudIdGlobal);

  // Campos del paso 3
  formData.append('puesto', document.getElementById('puesto').value);
  formData.append('empresa', document.getElementById('empresa').value);
  formData.append('giro_empresa', document.getElementById('giro_empresa').value);
  formData.append('direccion_trabajo', document.getElementById('direccion_trabajo').value);
  formData.append('calles_trabajo', document.getElementById('calles_trabajo').value);
  formData.append('ref_empresa_trabajo_input', document.getElementById('ref_empresa_trabajo_input').value);
  formData.append('colonia_trabajo', document.getElementById('colonia_trabajo').value);
  formData.append('municipio_trabajo', document.getElementById('municipio_trabajo').value);
  formData.append('estado_trabajo', document.getElementById('estado_trabajo').value);
  formData.append('pais_trabajo', document.getElementById('pais_trabajo').value);
  formData.append('tiempo_empleo', document.getElementById('tiempo_empleo').value);
  formData.append('telefono_trabajo', document.getElementById('telefono_trabajo').value);
  formData.append('horario_trabajo', document.getElementById('horario_trabajo').value);
  formData.append('sueldo', document.getElementById('sueldo').value);
  formData.append('forma_pago', document.getElementById('forma_pago').value);
  formData.append('otros_ingresos', document.getElementById('otros_ingresos').value);
  formData.append('fuente_ingresos', document.getElementById('fuente_ingresos').value);
  formData.append('ubicacion_negocio', document.getElementById('ubicacion_negocio').value);

  // Loader
  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(`${BASE_PUBLIC}app/controllers/guardar_info_laboral.php`, {
    method: 'POST',
    body: formData
  })
  .then(async (res) => {
    const txt = await res.text();
    let data;
    try { data = JSON.parse(txt); }
    catch { throw new Error('Respuesta no válida del servidor: ' + txt); }
    if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
    return data;
  })
  .then((data) => {
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: 'Información laboral guardada correctamente.',
      confirmButtonText: 'Continuar'
    }).then(() => nextPrev(1));
  })
  .catch((err) => {
    console.error('Error:', err);
    Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: err.message || 'Ocurrió un error al guardar la información laboral.'
    });
  });
}


function guardarPaso4() {
  const solicitud_id = getSolicitudId();
  if (!solicitud_id) { /* alerta */ return; }
  const formData = new FormData();
  formData.append('solicitud_id', solicitud_id);
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
   formData.append('parentesco_propietario', getValor('parentesco')); 
  formData.append('telefono_propietario', getValor('telefono_propietario'));

  // Radio button: ¿Posee auto?
  const poseeAuto = document.querySelector('input[name="posee_auto"]:checked');
  formData.append('posee_auto', poseeAuto ? poseeAuto.value : 'N/A');

  formData.append('marca_auto', getValor('marca_auto'));
  formData.append('valor_auto', getValor('valor_auto'));
  formData.append('empresa_auto', getValor('empresa_auto'));
  formData.append('mensualidad_auto', getValor('mensualidad_auto'));

  fetch(`${BASE_PUBLIC}app/controllers/guardar_info_adicional.php`, {
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
  const solicitud_id = getSolicitudId();

  if (!solicitud_id) {
    Swal.fire({ icon: 'error', title: 'Falta el folio', text: 'No se encontró el ID de solicitud.' });
    return;
  }

  const funcion_publica = document.querySelector('input[name="funcion_publica"]:checked')?.value || '';
  const relacion_funcion_publica = document.querySelector('input[name="relacion_funcion_publica"]:checked')?.value || '';
  const folio_consulta = document.getElementById('folio_consulta').value;
  const lugar = document.getElementById('lugar')?.value || '';
  const fecha = document.getElementById('fecha')?.value || '';

  const firma1El = document.getElementById('firmaCanvas1');
  const firma2El = document.getElementById('firmaCanvas2');
  const firma1 = firma1El ? firma1El.toDataURL() : '';
  const firma2 = firma2El ? firma2El.toDataURL() : '';

  const formData = new FormData();
  formData.append("solicitud_id", solicitud_id);
  formData.append("funcion_publica", funcion_publica);
  formData.append("relacion_funcion_publica", relacion_funcion_publica);
  formData.append("folio_consulta", folio_consulta);
  formData.append("firma_base64_1", firma1);
  formData.append("firma_base64_2", firma2);
  formData.append("lugar", lugar);
  formData.append("fecha", fecha);

  // Loader bonito
  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(`${BASE_PUBLIC}app/controllers/guardar_firma_declaracion.php`, {
    method: 'POST',
    body: formData
  })
  .then(async (response) => {
    const txt = await response.text();
    const contentType = response.headers.get('content-type') || '';
    if (!contentType.includes('application/json')) {
      throw new Error('La respuesta del servidor no es JSON. ' + txt);
    }
    let data;
    try { data = JSON.parse(txt); }
    catch { throw new Error('JSON inválido del servidor.'); }

    if (!response.ok || data.status !== 'ok') {
      throw new Error(data.message || ('HTTP ' + response.status));
    }
    return data;
  })
  .then((data) => {
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: data.message || 'Firma y declaración guardadas correctamente.',
      confirmButtonText: 'Continuar'
    }).then(() => nextPrev(1));
  })
  .catch((error) => {
    console.error('Error de red o servidor:', error);
    Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: error.message || 'Ocurrió un error al guardar.'
    });
  });
}




function guardarPaso6() {
  const solicitud_id = getSolicitudId();
  if (!solicitud_id) { /* alerta */ return; }

  const formData = new FormData();
  formData.append('solicitud_id', solicitud_id); // ✅ solo una vez

  // ---------- Referencias FAMILIARES: 1 y 2 (ids existentes) ----------
  ['','_2'].forEach(suf => {
    const val = (idBase) => document.getElementById(idBase + suf)?.value || '';
    formData.append(`form_ref_fam_nombre${suf}`,     val('form_ref_fam_nombre'));
    formData.append(`form_ref_fam_direccion${suf}`,  val('form_ref_fam_direccion'));
    formData.append(`form_ref_fam_telefono${suf}`,   val('form_ref_fam_telefono'));
    formData.append(`form_ref_fam_celular${suf}`,    val('form_ref_fam_celular'));
    formData.append(`form_ref_fam_parentesco${suf}`, val('form_ref_fam_parentesco'));
    formData.append(`form_ref_fam_correo${suf}`,     val('form_ref_fam_correo'));   // ← NUEVO
  });

  // ---------- Referencias PERSONALES: solo la 1 (sin *_2) ----------
  const v = (id) => document.getElementById(id)?.value || '';
  formData.append('form_ref_per_nombre',     v('form_ref_per_nombre'));
  formData.append('form_ref_per_direccion',  v('form_ref_per_direccion'));
  formData.append('form_ref_per_telefono',   v('form_ref_per_telefono'));
  formData.append('form_ref_per_celular',    v('form_ref_per_celular'));
  formData.append('form_ref_per_parentesco', v('form_ref_per_parentesco'));
  formData.append('form_ref_per_correo',     v('form_ref_per_correo'));            // ← NUEVO

  // ---------- Loader ----------
  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(`${BASE_PUBLIC}app/controllers/guardar_referencias.php`, {
    method: 'POST',
    body: formData
  })
  .then(async (res) => {
    const txt = await res.text();
    let data;
    try { data = JSON.parse(txt); }
    catch { throw new Error('Respuesta no válida del servidor: ' + txt); }
    if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
    return data;
  })
  .then((data) => {
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: data.message || 'Referencias guardadas correctamente.',
      confirmButtonText: 'Continuar'
    }).then(() => nextPrev(1));
  })
  .catch((err) => {
    console.error('Error en la solicitud:', err);
    Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: err.message || 'Ocurrió un error al guardar las referencias.'
    });
  });
}





function guardarPaso7() {
  const solicitud_id = getSolicitudId();
  if (!solicitud_id) { /* alerta */ return; }
  const formData = new FormData();
  formData.append('solicitud_id', solicitud_id);

  formData.append('form_co_nombre',            document.getElementById('form_co_nombre')?.value || '');
  formData.append('form_co_parentesco',        document.getElementById('form_co_parentesco')?.value || '');
  formData.append('form_co_apellido_paterno',  document.getElementById('form_co_apellido_paterno')?.value || '');
  formData.append('form_co_apellido_materno',  document.getElementById('form_co_apellido_materno')?.value || '');
  formData.append('form_co_correo',            document.getElementById('form_co_correo')?.value || '');
  formData.append('form_co_genero',            document.getElementById('form_co_genero')?.value || '');
  formData.append('form_co_nacimiento',        document.getElementById('form_co_nacimiento')?.value || '');
  formData.append('form_co_entidad',           document.getElementById('form_co_entidad')?.value || '');
  formData.append('form_co_dependientes',      document.getElementById('form_co_dependientes')?.value || '');
  formData.append('form_co_nacionalidad',      document.getElementById('form_co_nacionalidad')?.value || '');
  formData.append('form_co_pais_nacimiento',   document.getElementById('form_co_pais_nacimiento')?.value || '');
  formData.append('form_co_rfc',               document.getElementById('form_co_rfc')?.value || '');
  formData.append('form_co_curp',              document.getElementById('form_co_curp')?.value || '');
  formData.append('form_co_direccion',         document.getElementById('form_co_direccion')?.value || '');
  formData.append('form_co_entre_calles',      document.getElementById('form_co_entre_calles')?.value || '');
  formData.append('form_co_colonia',           document.getElementById('form_co_colonia')?.value || '');
  formData.append('form_co_cp',                document.getElementById('form_co_cp')?.value || '');
  formData.append('form_co_municipio',         document.getElementById('form_co_municipio')?.value || '');
  formData.append('form_co_estado',            document.getElementById('form_co_estado')?.value || '');
  formData.append('form_co_pais',              document.getElementById('form_co_pais')?.value || '');
  formData.append('form_co_tiempo',            document.getElementById('form_co_tiempo')?.value || '');
  formData.append('form_co_tel',               document.getElementById('form_co_tel')?.value || '');
  formData.append('form_co_cel',               document.getElementById('form_co_cel')?.value || '');
  formData.append('form_co_mejor_hora',        document.getElementById('form_co_mejor_hora')?.value || '');

  // Loader
  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  fetch(`${BASE_PUBLIC}app/controllers/guardar_codeudor.php`, {
    method: 'POST',
    body: formData
  })
  .then(async (res) => {
    const txt = await res.text();
    let data;
    try { data = JSON.parse(txt); }
    catch { throw new Error('Respuesta no válida del servidor: ' + txt); }
    if (!res.ok) throw new Error(data.message || ('HTTP ' + res.status));
    return data;
  })
  .then((data) => {
    Swal.fire({
      icon: 'success',
      title: '¡Listo!',
      text: data.message || 'Datos del co-deudor guardados correctamente.',
      confirmButtonText: 'Continuar'
    }).then(() => nextPrev(1));
  })
  .catch((err) => {
    console.error('Error en la solicitud:', err);
    Swal.fire({
      icon: 'error',
      title: 'No se pudo guardar',
      text: err.message || 'Ocurrió un error al guardar los datos del co-deudor.'
    });
  });
}




function guardarPaso8() {
  const solicitud_id = getSolicitudId();

  if (!solicitud_id) {
    Swal.fire({
      icon: 'error',
      title: 'Falta el folio',
      text: 'No encontré el ID de solicitud.'
    });
    return false;
  }

  const lugar = document.getElementById("lugar_funcionario")?.value?.trim() || "";
  const fecha = document.getElementById("fecha_funcionario")?.value?.trim() || "";

  if (!lugar || !fecha) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos faltantes',
      text: 'Completa Lugar y Fecha antes de guardar.'
    });
    return false;
  }

  const desempenia = document.querySelector('input[name="form_funcion_publica"]:checked')?.value || "";
  const relacion   = document.querySelector('input[name="form_relacion_publica"]:checked')?.value || "";

  const canvasAut  = document.getElementById("firmaAutorizacion");
  const canvasForm = document.getElementById("firmaFormulario");

  const firmaAutorizacion = (canvasAut && canvasAut.width && canvasAut.height)
    ? canvasAut.toDataURL("image/png")
    : "";

  const firmaFormulario = (canvasForm && canvasForm.width && canvasForm.height)
    ? canvasForm.toDataURL("image/png")
    : "";

  const formData = new FormData();
  formData.append("solicitud_id", solicitud_id);
  formData.append("desempenia_funcion_publica", desempenia);
  formData.append("relacion_funcion_publica", relacion);
  formData.append("firma_autorizacion", firmaAutorizacion);
  formData.append("firma_formulario", firmaFormulario);
  formData.append("lugar", lugar);
  formData.append("fecha", fecha);

  const urlPaso8 = `${BASE_PUBLIC}app/controllers/guardar_funcionarios_firma.php`;

  Swal.fire({
    title: 'Guardando…',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  return fetch(urlPaso8, {
    method: "POST",
    body: formData,
    credentials: "include"
  })
    .then(async (r) => {
      const txt = await r.text();

      let data;
      try {
        data = JSON.parse(txt);
      } catch {
        throw new Error('Respuesta no válida del servidor: ' + txt);
      }

      if (!r.ok || data.status !== 'ok') {
        throw new Error(data.message || ('HTTP ' + r.status));
      }

      return data;
    })
    .then((data) => {
      Swal.fire({
        icon: 'success',
        title: '¡Listo!',
        text: data.message || 'Firma guardada correctamente.',
        confirmButtonText: 'Continuar'
      }).then(() => {
        /*
          ✅ Ya NO redirige a propuesta_cliente.html.
          Aquí se queda en la misma página.
        */

        // Si quieres que al dar OK muestre el formato en la misma página:
        if (typeof generarFormato === 'function') {
          generarFormato();
        }

        const formulario = document.getElementById('formulario');
        const formatoFinal = document.getElementById('formatoFinal');

        if (formulario) formulario.classList.add('hidden');
        if (formatoFinal) formatoFinal.classList.remove('hidden');

        window.scrollTo({
          top: 0,
          behavior: 'smooth'
        });
      });

      return true;
    })
    .catch((err) => {
      console.error("Error al guardar paso 8:", err);

      Swal.fire({
        icon: 'error',
        title: 'No se pudo guardar',
        text: err.message || 'Ocurrió un error al guardar.'
      });

      return false;
    });
}




function guardarYGenerar() {
  if (!solicitudIdGlobal) {
    alert("⚠️ Aún no se ha creado la solicitud. Completa el paso 1 primero.");
    return;
  }

  guardarPaso8();   // Ya toma el ID desde solicitudIdGlobal
  generarFormato(); // Mostrar el formato final
}

function omitir() {
  nextPrev(1); // Avanza al siguiente paso del formulario multipaso
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

  // signature-handler.js

document.addEventListener('DOMContentLoaded', () => {
    // Definimos las configuraciones para cada una de tus firmas
    // Cada objeto en este array representa una firma independiente.
    const firmasConfig = [
        {
            // Configuración para la "Firma de autorización"
            drawCanvasId: 'firmaAutorizacion',      // ID del canvas donde el usuario dibuja (el grande)
            displayCanvasId: 'firmaVistaAutorizacion', // ID del canvas donde se muestra la firma final (el pequeño en el formato)
            borrarBtnId: 'borrarFirmaAutorizacion', // ID del botón para borrar esta firma específica
            ctx: null,                              // Contexto 2D del canvas de dibujo
            isDrawing: false,                       // Bandera para saber si el usuario está dibujando
            dataURL: ''                             // Almacena la firma como una URL de datos (Base64)
        },
        {
            // Configuración para la "Firma del formulario"
            drawCanvasId: 'firmaFormulario',        // ID del canvas donde el usuario dibuja (el grande)
            displayCanvasId: 'firmaVistaSolicitante', // ID del canvas donde se muestra la firma final (el pequeño en el formato)
            borrarBtnId: 'borrarFirmaFormulario',   // ID del botón para borrar esta firma específica
            ctx: null,
            isDrawing: false,
            dataURL: ''
        }
    ];

    // Iteramos sobre cada configuración de firma para inicializar su funcionalidad
    firmasConfig.forEach(config => {
        const drawCanvas = document.getElementById(config.drawCanvasId);

        // Si el canvas de dibujo no existe, salta esta configuración y muestra una advertencia
        if (!drawCanvas) {
            console.warn(`Canvas de dibujo no encontrado para ID: ${config.drawCanvasId}. Asegúrate de que el ID en HTML coincida.`);
            return;
        }

        // Obtiene el contexto 2D del canvas y configura el estilo de la línea
        config.ctx = drawCanvas.getContext('2d');
        config.ctx.lineWidth = 2;       // Grosor de la línea de la firma
        config.ctx.lineCap = 'round';   // Estilo de los extremos de la línea
        config.ctx.strokeStyle = '#000'; // Color de la firma (negro)

        // --- Eventos para dibujar en el canvas de origen (donde el usuario firma) ---

        // Cuando el mouse se presiona, comienza a dibujar
        drawCanvas.addEventListener('mousedown', (e) => {
            config.isDrawing = true;
            config.ctx.beginPath(); // Inicia un nuevo trazo
            // Mueve el "lápiz" a la posición actual del mouse
            config.ctx.moveTo(e.offsetX, e.offsetY);
        });

        // Cuando el mouse se mueve y está presionado, dibuja la línea
        drawCanvas.addEventListener('mousemove', (e) => {
            if (config.isDrawing) {
                // Dibuja una línea desde la posición anterior hasta la actual
                config.ctx.lineTo(e.offsetX, e.offsetY);
                config.ctx.stroke(); // Aplica el trazo
            }
        });

        // Cuando el mouse se suelta, detiene el dibujo y guarda la firma
        drawCanvas.addEventListener('mouseup', () => {
            config.isDrawing = false;
            // Guarda el contenido del canvas como una URL de datos (imagen Base64)
            config.dataURL = drawCanvas.toDataURL();
            // Llama a la función auxiliar para mostrar esta firma en su canvas de visualización
            displaySignatureOnCanvas(config.dataURL, config.displayCanvasId);
        });

        // Cuando el mouse sale del canvas mientras se dibuja, detiene el dibujo y guarda la firma
        drawCanvas.addEventListener('mouseout', () => {
            if (config.isDrawing) {
                config.isDrawing = false;
                config.dataURL = drawCanvas.toDataURL();
                displaySignatureOnCanvas(config.dataURL, config.displayCanvasId);
            }
        });

        // --- Configuración del botón de borrar para esta firma específica ---

        const borrarButton = document.getElementById(config.borrarBtnId);
        if (borrarButton) {
            // Adjunta un event listener al botón de borrar
            borrarButton.addEventListener('click', () => {
                clearSignature(drawCanvas, config); // Llama a la función para borrar
            });
        } else {
            console.warn(`Botón de borrar no encontrado para ID: ${config.borrarBtnId}. Asegúrate de que el ID en HTML coincida.`);
        }
    });

    // --- Funciones Auxiliares (reutilizables para ambas firmas) ---

    /**
     * Dibuja una firma (en formato Data URL) en un canvas de destino específico.
     * @param {string} dataURL - La URL de datos (Base64) de la imagen de la firma.
     * @param {string} idCanvasDestino - El ID del canvas donde se dibujará la firma.
     */
    function displaySignatureOnCanvas(dataURL, idCanvasDestino) {
        const displayCanvas = document.getElementById(idCanvasDestino);
        if (!displayCanvas) {
            console.warn(`Canvas de destino no encontrado para ID: ${idCanvasDestino}`);
            return;
        }
        const ctxDisplay = displayCanvas.getContext('2d');
        const img = new Image();

        img.onload = () => {
            // Limpia el canvas de destino antes de dibujar la nueva firma
            ctxDisplay.clearRect(0, 0, displayCanvas.width, displayCanvas.height);

            // Calcula el ratio para escalar la imagen y que quepa en el canvas de destino
            const ratio = Math.min(displayCanvas.width / img.width, displayCanvas.height / img.height);
            const newWidth = img.width * ratio;
            const newHeight = img.height * ratio;

            // Calcula el desplazamiento para centrar la imagen en el canvas
            const offsetX = (displayCanvas.width - newWidth) / 2;
            const offsetY = (displayCanvas.height - newHeight) / 2;

            // Dibuja la imagen de la firma escalada y centrada
            ctxDisplay.drawImage(img, offsetX, offsetY, newWidth, newHeight);
        };
        img.src = dataURL; // Asigna la URL de datos a la imagen para que se cargue
    }

    /**
     * Borra una firma de su canvas de dibujo y de su canvas de visualización.
     * @param {HTMLCanvasElement} drawCanvas - El elemento canvas donde el usuario dibuja.
     * @param {object} config - El objeto de configuración de la firma (contiene dataURL, ctx, displayCanvasId).
     */
    function clearSignature(drawCanvas, config) {
        // Limpia el canvas de dibujo
        config.ctx.clearRect(0, 0, drawCanvas.width, drawCanvas.height);
        config.dataURL = ''; // Restablece la URL de datos almacenada para esta firma

        // También limpia el canvas de visualización correspondiente
        const displayCanvas = document.getElementById(config.displayCanvasId);
        if (displayCanvas) {
            displayCanvas.getContext('2d').clearRect(0, 0, displayCanvas.width, displayCanvas.height);
        }
    }

    // --- Función Global para Copiar Firmas al Formato Final ---

    /**
     * Esta función se expone globalmente para ser llamada desde otras partes del código (ej. generarFormato()).
     * Asegura que todas las firmas dibujadas se copien a sus respectivos canvases de visualización.
     */
    window.copiarFirmasParaFormato = function() {
        firmasConfig.forEach(config => {
            if (config.dataURL) { // Solo copia si hay una firma dibujada
                displaySignatureOnCanvas(config.dataURL, config.displayCanvasId);
            }
        });
    };
});

function habilitarFirma(canvasId) {
  const canvas = document.getElementById(canvasId);
  const ctx = canvas.getContext('2d');
  let dibujando = false;

  function comenzarDibujo(x, y) {
    dibujando = true;
    ctx.beginPath();
    ctx.moveTo(x, y);
  }

  function dibujarLinea(x, y) {
    if (!dibujando) return;
    ctx.lineTo(x, y);
    ctx.stroke();
  }

  function detenerDibujo() {
    dibujando = false;
    ctx.closePath();
  }

  // Eventos de mouse
  canvas.addEventListener('mousedown', e => comenzarDibujo(e.offsetX, e.offsetY));
  canvas.addEventListener('mousemove', e => dibujarLinea(e.offsetX, e.offsetY));
  canvas.addEventListener('mouseup', detenerDibujo);
  canvas.addEventListener('mouseleave', detenerDibujo);

  // Eventos de pantalla táctil
  canvas.addEventListener('touchstart', e => {
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    const x = touch.clientX - rect.left;
    const y = touch.clientY - rect.top;
    comenzarDibujo(x, y);
  }, { passive: true });


  canvas.addEventListener('touchmove', e => {
    e.preventDefault(); // Evita el scroll mientras se firma
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    const x = touch.clientX - rect.left;
    const y = touch.clientY - rect.top;
    dibujarLinea(x, y);
  }, { passive: true });

  canvas.addEventListener('touchend', detenerDibujo);
}

// Activar ambas firmas
habilitarFirma("firmaFormulario");
habilitarFirma("firmaAutorizacion");

document.querySelectorAll('input[name="tipo_vivienda"]').forEach(radio => {
  radio.addEventListener('change', function () {
    const tipo = this.value;

    // Ocultar todos los grupos
    document.getElementById('grupo-propia').style.display = 'none';
    document.getElementById('grupo-hipoteca').style.display = 'none';
    document.getElementById('grupo-propietario').style.display = 'none';
    document.getElementById('campo-parentesco').style.display = 'none'; // Oculta parentesco por default

    // Mostrar según selección
    if (tipo === 'Propia') {
      document.getElementById('grupo-propia').style.display = 'block';
    } else if (tipo === 'Hipoteca') {
      document.getElementById('grupo-hipoteca').style.display = 'block';
    } else if (tipo === 'Familiar') {
      document.getElementById('grupo-propietario').style.display = 'block';
      document.getElementById('campo-parentesco').style.display = 'block'; // Solo en Familiar
    } else if (['Renta', 'Huesped'].includes(tipo)) {
      document.getElementById('grupo-propietario').style.display = 'block';
      // No mostramos parentesco aquí
    }
  });
});


function borrarFirmaAutorizacion() {
  const canvas = document.getElementById('firmaAutorizacion');
  const ctx = canvas.getContext('2d');
  ctx.clearRect(0, 0, canvas.width, canvas.height);
}

async function cargarFolioYMostrar() {
  // Usa siempre el ID REAL de solicitud
  const id =
    sessionStorage.getItem('solicitud_id') ||
    new URLSearchParams(location.search).get('solicitud_id') ||
    new URLSearchParams(location.search).get('folio');

  if (!id) return;

  let folioMostrado;

  try {
    const res = await fetch('/app/controllers/generar_folio.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: new URLSearchParams({ solicitud_id: id })
    });

    const text = await res.text();
    let data;
    try { data = JSON.parse(text); }
    catch {
      console.warn('Respuesta no-JSON de generar_folio.php:', text);
      data = {};
    }

    if ((data.status === 'ok' || data.status === 'ya_generado') && data.folio) {
      folioMostrado = data.folio; // p.ej. CIP-2025-00159
    } else {
      console.warn('Folio no provisto por PHP:', data);
      const anio = new Date().getFullYear();
      folioMostrado = `CIP-${anio}-${String(id).padStart(5, '0')}`;
    }
  } catch (e) {
    console.error('Error al pedir folio:', e);
    const anio = new Date().getFullYear();
    folioMostrado = `CIP-${anio}-${String(id).padStart(5, '0')}`;
  }

  // Guarda para reutilizar
  sessionStorage.setItem('folio_bonito', folioMostrado);

  // Pinta en la caja (sin innerHTML)
  const box = document.querySelector('.folio-en-esquina');
  if (box) {
    let span = document.getElementById('folioTexto');
    if (!span) {
      const label = document.createElement('b');
      label.textContent = 'Folio: ';
      span = document.createElement('span');
      span.id = 'folioTexto';
      box.textContent = '';
      box.appendChild(label);
      box.appendChild(span);
    }
    span.textContent = folioMostrado;
  }
}


// Ejecutar una sola vez al cargar
document.addEventListener('DOMContentLoaded', cargarFolioYMostrar, { once: true });




// crear.js - Autocompletado por Código Postal

// ================== Autocompletado por Código Postal ==================

// Helpers
const norm = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
const mapaEstados = {
  'mexico':'Estado de México',
  'coahuila de zaragoza':'Coahuila',
  'michoacan de ocampo':'Michoacán',
  'veracruz de ignacio de la llave':'Veracruz',
  'queretaro de arteaga':'Querétaro',
  'distrito federal':'Ciudad de México'
};

// === Helpers para "Otro…" en colonia ===
function ensureOtroOption(sel){
  if (!sel || sel.tagName.toLowerCase() !== 'select') return;
  const exists = Array.from(sel.options).some(o => o.value === '__otro__');
  if (!exists) sel.add(new Option('Otro…', '__otro__'));
}

// Convierte <select id="colonia"> -> <input id="colonia"> (mismo id)
function convertirSelectAInput(sel){
  const input = document.createElement('input');
  input.type = 'text';
  input.id = sel.id;                   // MISMO id
  input.name = sel.name || 'colonia';
  input.placeholder = 'Escribe tu colonia';
  input.required = true;
  input.className = sel.className;     // conserva estilos
  sel.replaceWith(input);
  return input;                        // devolver para actualizar la ref global
}


// Refs (se asignan en DOMContentLoaded)
let inpColonia, inpCP, inpMunicipio, inpEstado, inpPais, selEstadoNac;
let timer, ultimoCPConsultado = '';

// Aviso debajo del input de CP
function mostrarAviso(msg, tipo='ok'){
  if (!inpCP) return;
  let el = document.getElementById('cp-aviso');
  if (!el){
    el = document.createElement('div');
    el.id = 'cp-aviso';
    el.style.cssText = 'font-size:12px;margin-top:4px;line-height:1.2;';
    inpCP.parentElement.appendChild(el);
  }
  el.textContent = msg;
  el.style.color = (tipo === 'ok') ? '#198754' : '#cc8b00';
}

function limpiarCamposDependientes(){
  if (inpMunicipio) inpMunicipio.value = '';
  if (inpEstado)    inpEstado.value    = '';
  if (inpColonia){
    if (inpColonia.tagName.toLowerCase()==='select') inpColonia.innerHTML = '';
    else inpColonia.value = '';
  }
}

// ----------- Buscador principal (solicitante) -----------
async function buscarPorCP(force=false){
  if (!inpCP) return;

  // Normaliza a 5 dígitos
  const raw = (inpCP.value||'').toString();
  const cp  = raw.replace(/\D+/g,'').slice(0,5);
  if (inpCP.value !== cp) inpCP.value = cp;

  if (!/^\d{5}$/.test(cp)){
    if (cp==='') ultimoCPConsultado='';
    limpiarCamposDependientes();
    mostrarAviso('Escribe un CP de 5 dígitos.','warn');
    return;
  }
  if (!force && cp===ultimoCPConsultado) return;
  ultimoCPConsultado = cp;

  try{
    limpiarCamposDependientes();

    const base = (typeof BASE_PUBLIC==='string') ? BASE_PUBLIC : '/';
    const url  = `${base}app/controllers/cp_buscar.php?cp=${encodeURIComponent(cp)}&t=${Date.now()}`;
    const res  = await fetch(url, { cache:'no-store' });
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const data = await res.json();

    if (data && data.success){
      const estadoResp   = data.estado || '';
      const estadoBonito = mapaEstados[norm(estadoResp)] || estadoResp;

      // Rellena campos del domicilio
      if (inpMunicipio) inpMunicipio.value = data.municipio || '';
      if (inpEstado)    inpEstado.value    = estadoBonito || '';
      setPaisControlValue(inpPais, 'México');

      // Colonias
// Colonias
if (inpColonia && Array.isArray(data.colonias) && data.colonias.length){
  const seen = new Set(), unicas = [];
  data.colonias.forEach(c=>{
    const o = String(c||'').trim(); if (!o) return;
    const k = o.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    if (!seen.has(k)) { seen.add(k); unicas.push(o); }
  });
  unicas.sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));

  if (inpColonia.tagName.toLowerCase()==='select'){
    const prev = inpColonia.value;
    inpColonia.innerHTML = '<option value="" disabled selected>Selecciona colonia…</option>';
    unicas.forEach(col => inpColonia.add(new Option(col, col, false, col===prev)));
    ensureOtroOption(inpColonia); // <-- añade "Otro…"
  } else {
    // ya es input (porque eligieron "Otro…"): NO sobreescribas lo que escriba el usuario
    if (!inpColonia.value.trim()){
      // opcional: sugerir
      // inpColonia.value = unicas[0] || '';
    }
  }
} else {
  // Si no hay colonias devueltas, deja el select listo con "Otro…"
  if (inpColonia && inpColonia.tagName.toLowerCase()==='select'){
    inpColonia.innerHTML = '<option value="" disabled selected>Selecciona colonia…</option>';
    ensureOtroOption(inpColonia);
  }
}


      // Sincroniza el select de estado de nacimiento y dispara cálculo de CURP
      if (selEstadoNac && estadoBonito){
        if (selEstadoNac.tagName.toLowerCase()==='select'){
          const opts = Array.from(selEstadoNac.options || []);
          const match =
            opts.find(o => norm(o.value)===norm(estadoBonito) || norm(o.textContent)===norm(estadoBonito)) ||
            opts.find(o => norm(o.value)===norm(estadoResp)   || norm(o.textContent)===norm(estadoResp));
          if (match){
            selEstadoNac.value = match.value;
            // Dispara el change para quienes escuchen ese evento
            selEstadoNac.dispatchEvent(new Event('change', { bubbles:true }));
          }
        } else {
          selEstadoNac.value = estadoBonito || estadoResp || '';
        }

        // Si existe calcularCURP(), ejecútalo para llenar el campo automáticamente
        if (typeof calcularCURP === 'function') {
          calcularCURP();
        }
      }

      mostrarAviso('Datos del CP encontrados y completados.','ok');
    } else {
      mostrarAviso('CP no encontrado. Captura manual.','warn');
    }
  } catch(err){
    console.error('[CP] Error:', err);
    mostrarAviso('No fue posible verificar el CP. Captura manual.','warn');
  }
}

// ----------- Enlace de eventos (usar en tu DOMContentLoaded) -----------
document.addEventListener('DOMContentLoaded', () => {
  // Asignar refs
  inpColonia   = document.getElementById('colonia');
  inpCP        = document.getElementById('cp');
  inpMunicipio = document.getElementById('municipio');
  inpEstado    = document.getElementById('estado');
  inpPais      = document.getElementById('pais');
  selEstadoNac = document.getElementById('estado_nacimiento');

  // Debounce mientras escribe el CP
  if (inpCP){
    inpCP.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => buscarPorCP(true), 300);
    });
    inpCP.addEventListener('blur', () => buscarPorCP(true));
  }

  if (inpColonia && inpColonia.tagName.toLowerCase()==='select'){
  inpColonia.addEventListener('change', () => {
    if (inpColonia.value === '__otro__'){
      // se convierte y ACTUALIZAMOS la ref global
      inpColonia = convertirSelectAInput(inpColonia);
    }
  });
}
});

// ---------- binder reutilizable para el co-deudor ----------
function bindCPAutofill({
  cpId, coloniaId, municipioId, estadoId, paisId,
  estadoNacimientoId = null, avisoId = 'cp-aviso-co',
  endpoint = ((typeof BASE_PUBLIC === 'string') ? BASE_PUBLIC : '/') + 'app/controllers/cp_buscar.php'
}) {
  const cpEl  = document.getElementById(cpId);
  const colEl = document.getElementById(coloniaId);
  const munEl = document.getElementById(municipioId);
  const estEl = document.getElementById(estadoId);
  const paisEl= document.getElementById(paisId);
  const entEl = estadoNacimientoId ? document.getElementById(estadoNacimientoId) : null;
  if (!cpEl) return;

  // aviso pequeño
  let aviso = document.getElementById(avisoId);
  if (!aviso){
    aviso = document.createElement('div');
    aviso.id = avisoId; aviso.style.cssText='font-size:12px;margin-top:4px;line-height:1.2;';
    cpEl.parentElement.appendChild(aviso);
  }
  const ok   = m=>{ aviso.textContent=m; aviso.style.color='#198754'; };
  const warn = m=>{ aviso.textContent=m; aviso.style.color='#cc8b00'; };

  let t, last='';
  async function run(force=false){
    const cp = (cpEl.value||'').replace(/\D+/g,'').slice(0,5);
    if (cpEl.value!==cp) cpEl.value=cp;
    if (!/^\d{5}$/.test(cp)){ if(cp==='') last=''; [munEl,estEl,colEl].forEach(el=>{ if(!el) return; if(el.tagName?.toLowerCase()==='select') el.innerHTML=''; else el.value=''; }); warn('Escribe un CP de 5 dígitos.'); return; }
    if (!force && cp===last) return; last=cp;

    try{
      [munEl,estEl,colEl].forEach(el=>{ if(!el) return; if(el.tagName?.toLowerCase()==='select') el.innerHTML=''; else el.value=''; });
      const url = `${endpoint}?cp=${encodeURIComponent(cp)}&t=${Date.now()}`;
      const r = await fetch(url,{cache:'no-store'}); if(!r.ok) throw new Error(`HTTP ${r.status}`);
      const d = await r.json();
      if (d && d.success){
        const estadoResp=d.estado||''; const estadoBonito = mapaEstados[norm(estadoResp)] || estadoResp;
        if (munEl)  munEl.value  = d.municipio || '';
        if (estEl)  estEl.value  = estadoBonito || '';
        if (paisEl) paisEl.value = 'México';
        if (colEl && Array.isArray(d.colonias) && d.colonias.length){
          const seen=new Set(), unicas=[];
          d.colonias.forEach(c=>{ const o=String(c||'').trim(); if(!o) return; const k=o.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); if(!seen.has(k)){seen.add(k); unicas.push(o);} });
          unicas.sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));
          if (colEl.tagName.toLowerCase()==='select'){
            const prev=colEl.value; colEl.innerHTML='<option value="" disabled selected>Selecciona colonia…</option>';
            unicas.forEach(col=> colEl.add(new Option(col,col,false,col===prev)));
          } else if (!colEl.value.trim()){ colEl.value = unicas[0] || ''; }
        }
        if (entEl){
          if (entEl.tagName?.toLowerCase()==='select'){
            const opts = Array.from(entEl.options||[]);
            const m = opts.find(o=>norm(o.value)===norm(estadoBonito)||norm(o.textContent)===norm(estadoBonito))
                    || opts.find(o=>norm(o.value)===norm(estadoResp)||norm(o.textContent)===norm(estadoResp));
            if (m) entEl.value = m.value;
          } else {
            entEl.value = estadoBonito || estadoResp || '';
          }
        }
        ok('Datos del CP encontrados y completados.');
      } else { warn('CP no encontrado. Captura manual.'); }
    } catch(e){ console.error(e); warn('No fue posible verificar el CP. Captura manual.'); }
  }

  cpEl.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>run(false),250); });
  cpEl.addEventListener('blur',  ()=>run(true));
  cpEl.addEventListener('change',()=>run(true));
  if (/^\d{5}$/.test(cpEl.value||'')) run(true); // precarga
}

// ---------- inicialización segura ----------
document.addEventListener('DOMContentLoaded', () => {
  // refs que usas arriba
  inpColonia   = document.getElementById('colonia');
  inpCP        = document.getElementById('cp');
  inpMunicipio = document.getElementById('municipio');
  inpEstado    = document.getElementById('estado');
  inpPais      = document.getElementById('pais');
  selEstadoNac = document.getElementById('estado_nacimiento');

  // dispara búsqueda por CP con debounce mientras escribe
  if (inpCP){
    inpCP.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(() => buscarPorCP(true), 300);
    });
    inpCP.addEventListener('blur', () => buscarPorCP(true));
  }

  // (lo que ya tenías para CURP)
  ['nombres','apellido_paterno','apellido_materno','fecha_nacimiento','genero','estado_nacimiento']
    .forEach(id=>{
      const el = document.getElementById(id);
      if (el){
        el.addEventListener('input',  calcularCURP);
        el.addEventListener('change', calcularCURP);
        el.addEventListener('blur',   calcularCURP);
      }
    });

  calcularCURP();
});







// ========================== CURP (base 16 + hint + preserva homoclave + estilos) ==========================

// Normaliza: minúsculas, sin acentos, espacios colapsados
const mxNorm = (s='') =>
  s.normalize('NFD').replace(/[\u0300-\u036f]/g,'')
   .replace(/\s+/g,' ').trim().toLowerCase();

// Mapa CURP por nombre/código
const CURP_STATE = {
  'estado de mexico':'MC','mexico':'MC','edomex':'MC','edo mex':'MC','edo. de mexico':'MC','mexico, estado de':'MC',
  'aguascalientes':'AS','baja california':'BC','baja california sur':'BS','campeche':'CC',
  'chiapas':'CS','chihuahua':'CH','ciudad de mexico':'DF','distrito federal':'DF',
  'coahuila':'CL','coahuila de zaragoza':'CL','colima':'CM','durango':'DG',
  'guanajuato':'GT','guerrero':'GR','hidalgo':'HG','jalisco':'JC',
  'michoacan':'MN','michoacan de ocampo':'MN','morelos':'MS','nayarit':'NT','nuevo leon':'NL',
  'oaxaca':'OC','puebla':'PL','queretaro':'QT','queretaro de arteaga':'QT',
  'quintana roo':'QR','san luis potosi':'SP','sinaloa':'SL','sonora':'SR',
  'tabasco':'TC','tamaulipas':'TS','tlaxcala':'TL','veracruz':'VZ',
  'veracruz de ignacio de la llave':'VZ','yucatan':'YN','zacatecas':'ZS',
  'extranjero':'NE','nacido en el extranjero':'NE',
  // por si value ya trae código en minúsculas
  'as':'AS','bc':'BC','bs':'BS','cc':'CC','cs':'CS','ch':'CH','df':'DF','cl':'CL','cm':'CM','dg':'DG',
  'mc':'MC','gt':'GT','gr':'GR','hg':'HG','jc':'JC','mn':'MN','ms':'MS','nt':'NT','nl':'NL','oc':'OC',
  'pl':'PL','qt':'QT','qr':'QR','sp':'SP','sl':'SL','sr':'SR','tc':'TC','ts':'TS','tl':'TL','vz':'VZ','yn':'YN','zs':'ZS','ne':'NE'
};

// INEGI → CURP (por si el select usa números 1..32)
const INEGI_TO_CURP = {
  '01':'AS','02':'BC','03':'BS','04':'CC','05':'CL','06':'CM','07':'CS','08':'CH','09':'DF',
  '10':'DG','11':'GT','12':'GR','13':'HG','14':'JC','15':'MC','16':'MN','17':'MS','18':'NT',
  '19':'NL','20':'OC','21':'PL','22':'QT','23':'QR','24':'SP','25':'SL','26':'SR','27':'TC',
  '28':'TS','29':'TL','30':'VZ','31':'YN','32':'ZS'
};

// Lee el estado del <select> con tolerancia (placeholders y variantes)
function estadoCodigoDesdeSelect(sel){
  if (!sel) return '';
  let val = (sel.value || '').toString().trim();
  const text = (sel.options[sel.selectedIndex]?.text || '').trim();

  // placeholder
  if (!val && /^seleccione/i.test(text)) return '';

  // INEGI 1..32
  if (/^\d{1,2}$/.test(val)) {
    if (val.length === 1) val = '0' + val;
    const cod = INEGI_TO_CURP[val];
    if (cod) return cod;
  }

  // value como texto/código
  const vNorm = mxNorm(val);
  if (CURP_STATE[vNorm]) return CURP_STATE[vNorm];

  // por texto visible
  const tNorm = mxNorm(text);
  if (CURP_STATE[tNorm]) return CURP_STATE[tNorm];

  // limpieza de muletillas
  const tLite = tNorm.replace(/\b(edo\.?|estado|de|del|la|,)\b/g,' ')
                     .replace(/\s+/g,' ').trim();
  if (CURP_STATE[tLite]) return CURP_STATE[tLite];

  // heurística final
  if (tNorm.includes('mexico')) return 'MC';
  return '';
}

// Fecha flexible: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, con/sin espacios
function parseFechaFlexible(str=''){
  const s = (str||'').replace(/\s+/g,'').trim();
  if (/^\d{4}[-/]\d{2}[-/]\d{2}$/.test(s)) { const [Y,M,D]=s.split(/[-/]/).map(n=>+n); return new Date(Y,M-1,D); }
  if (/^\d{2}[-/]\d{2}[-/]\d{4}$/.test(s)) { const [D,M,Y]=s.split(/[-/]/).map(n=>+n); return new Date(Y,M-1,D); }
  return new Date(NaN);
}

function primeraVocalInterna(s=''){
  s = mxNorm(s).toUpperCase();
  for (let i=1;i<s.length;i++) if ('AEIOU'.includes(s[i])) return s[i];
  return 'X';
}
function primeraConsonanteInterna(s=''){
  s = mxNorm(s).toUpperCase();
  for (let i=1;i<s.length;i++){
    const c = s[i];
    if (!'AEIOU'.includes(c) && /[A-ZÑ]/.test(c)) return (c==='Ñ'?'X':c);
  }
  return 'X';
}

// --- Estilos del input CURP (Bootstrap + fallback) ---
function setCurpValidityStyle(state){ // 'valid' | 'invalid' | 'neutral'
  const el = document.getElementById('curp');
  if (!el) return;
  // limpia
  el.classList.remove('is-valid','is-invalid');
  el.removeAttribute('aria-invalid');
  el.style.borderColor = '';
  el.style.boxShadow = '';

  if (state === 'valid'){
    el.classList.add('is-valid');        // Bootstrap (si está)
    el.style.borderColor = '#198754';    // fallback
    el.style.boxShadow  = '0 0 0 .2rem rgba(25,135,84,.25)';
    el.setAttribute('aria-invalid','false');
  } else if (state === 'invalid'){
    el.classList.add('is-invalid');      // Bootstrap (si está)
    el.style.borderColor = '#dc3545';    // fallback
    el.style.boxShadow  = '0 0 0 .2rem rgba(220,53,69,.25)';
    el.setAttribute('aria-invalid','true');
  }
}

// --- Hint debajo del input CURP ---
function getOrCreateCurpHint(){
  const curpEl = document.getElementById('curp');
  if (!curpEl) return null;

  let hint = document.getElementById('curp-hint');
  if (!hint){
    hint = document.createElement('small');
    hint.id = 'curp-hint';
    hint.className = 'form-text'; // opcional (Bootstrap)
    hint.style.display = 'block';
    hint.style.marginTop = '4px';
    hint.style.fontSize = '12px';
    curpEl.insertAdjacentElement('afterend', hint); // justo después del input
  }
  return hint;
}

function actualizarCurpHint(){
  const curpEl = document.getElementById('curp');
  const hint = getOrCreateCurpHint();
  if (!curpEl || !hint) return;

  const v = (curpEl.value || '').trim();

  if (v.length < 16){
    hint.textContent = `Faltan ${16 - v.length} caracteres de la base.`;
    hint.style.color = '#cc8b00';
    setCurpValidityStyle('invalid');
  } else if (v.length === 16){
    hint.textContent = 'Faltan 2 caracteres (homoclave oficial).';
    hint.style.color = '#cc8b00';
    setCurpValidityStyle('invalid'); // rojo hasta tener 18
  } else if (v.length === 17){
    hint.textContent = 'Falta 1 carácter (homoclave oficial).';
    hint.style.color = '#cc8b00';
    setCurpValidityStyle('invalid'); // sigue rojo
  } else if (v.length === 18){
    hint.textContent = 'CURP completa (18/18).';
    hint.style.color = '#198754';
    setCurpValidityStyle('valid');   // verde
  } else if (v.length > 18){
    hint.textContent = `Sobran ${v.length - 18} caracteres.`;
    hint.style.color = '#dc3545';
    setCurpValidityStyle('invalid');
  } else {
    hint.textContent = '';
    setCurpValidityStyle('neutral');
  }
}

// (Opcional) autocompleta País/Nacionalidad desde la CURP
function autocompletarPaisYNacionalidadDesdeCURP(){
  const curpEl = document.getElementById('curp');
  if (!curpEl) return;
  const curp = (curpEl.value || '').toUpperCase().replace(/[^A-Z0-9Ñ]/g,'');
  if (curp.length < 13) return; // aún no llega al código de entidad

  const codigoEntidad = curp.slice(11,13); // posiciones 12–13 (0-based [11,13))
  const esExtranjero = (codigoEntidad === 'NE');

  const paisNacEl = document.getElementById('pais_nacimiento')
                  || document.getElementById('paisNacimiento')
                  || document.getElementById('paisNac');
  const nacEl     = document.getElementById('nacionalidad')
                  || document.getElementById('nacion');

  if (paisNacEl) paisNacEl.value = esExtranjero ? '' : 'México';
  if (nacEl && !esExtranjero && !nacEl.value.trim()) nacEl.value = 'Mexicana';
}

// Calcula y escribe base16; preserva homoclave si ya estaba escrita y la base coincide
function calcularCURP(){
  const curpEl  = document.getElementById('curp');
  if (!curpEl) return;

  const nombres = (document.getElementById('nombres')?.value || '').trim();
  const apPat   = (document.getElementById('apellido_paterno')?.value || '').trim();
  const apMat   = (document.getElementById('apellido_materno')?.value || '').trim();
  const genTxt  = (document.getElementById('genero')?.value || '').trim();
  const fecha   = (document.getElementById('fecha_nacimiento')?.value || '').trim();
  const selEnt  = document.getElementById('estado_nacimiento');

  const edo = estadoCodigoDesdeSelect(selEnt);
  if (!edo){ curpEl.value = ''; actualizarCurpHint(); return; }

  const d = parseFechaFlexible(fecha);
  if (isNaN(d.getTime())) { curpEl.value = ''; actualizarCurpHint(); return; }

  const YY = String(d.getFullYear()).slice(-2);
  const MM = String(d.getMonth()+1).padStart(2,'0');
  const DD = String(d.getDate()).padStart(2,'0');

  const genero = genTxt.toLowerCase().startsWith('h') ? 'H'
                 : genTxt.toLowerCase().startsWith('m') ? 'M' : '';
  if (!genero){ curpEl.value = ''; actualizarCurpHint(); return; }

  const N = mxNorm(nombres).toUpperCase();
  const P = mxNorm(apPat).toUpperCase();
  const M = mxNorm(apMat).toUpperCase();

  const STOP = new Set(['JOSE','J','J.','MARIA','MA','MA.']);
  const partes = N.split(/\s+/).filter(Boolean);
  let primerNombre = partes[0] || 'X';
  if (STOP.has(primerNombre)) primerNombre = partes[1] || primerNombre;

  // Construcción base de 16
  let base16 = '';
  base16 += (P[0]||'X');
  base16 += primeraVocalInterna(P);
  base16 += (M[0]||'X');
  base16 += (primerNombre[0]||'X');
  base16 += YY + MM + DD;
  base16 += genero + edo;
  base16 += primeraConsonanteInterna(P);
  base16 += primeraConsonanteInterna(M);
  base16 += primeraConsonanteInterna(primerNombre);

  // Preservar homoclave si ya estaba y la base coincide
  const actual = (curpEl.value || '').toUpperCase().replace(/\s+/g,'');
  if (actual.length >= 16 && actual.startsWith(base16)){
    curpEl.value = actual.slice(0,18); // conserva los 2 últimos si están
  } else {
    curpEl.value = base16;             // escribe sólo base16
  }

  actualizarCurpHint();
  autocompletarPaisYNacionalidadDesdeCURP();
}

// Listeners
document.addEventListener('DOMContentLoaded', ()=>{
  const curpEl = document.getElementById('curp');
  if (curpEl){
    curpEl.setAttribute('maxlength','18');
    curpEl.addEventListener('input', ()=>{
      curpEl.value = (curpEl.value || '')
        .toUpperCase()
        .replace(/[^A-Z0-9Ñ]/g,'')
        .slice(0,18);
      actualizarCurpHint();
      autocompletarPaisYNacionalidadDesdeCURP();
    });
  }

  ['nombres','apellido_paterno','apellido_materno','fecha_nacimiento']
    .forEach(id=>{
      const el = document.getElementById(id);
      if (el){
        el.addEventListener('input', calcularCURP);
        el.addEventListener('blur',  calcularCURP);
        el.addEventListener('keyup', calcularCURP); // útil al teclear fecha
      }
    });
  ['genero','estado_nacimiento'].forEach(id=>{
    const el = document.getElementById(id);
    el && el.addEventListener('change', calcularCURP);
  });

  calcularCURP();
  actualizarCurpHint(); // pinta estado inicial
});



document.addEventListener('DOMContentLoaded', () => {
  // Lista de estados válidos, normalizados
  const norm = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
  const validos = new Set([
    'Aguascalientes','Baja California','Baja California Sur','Campeche','Chiapas','Chihuahua',
    'Ciudad de México','Coahuila','Colima','Durango','Estado de México','Guanajuato','Guerrero',
    'Hidalgo','Jalisco','Michoacán','Morelos','Nayarit','Nuevo León','Oaxaca','Puebla','Querétaro',
    'Quintana Roo','San Luis Potosí','Sinaloa','Sonora','Tabasco','Tamaulipas','Tlaxcala','Veracruz',
    'Yucatán','Zacatecas'
  ].map(norm));

  const est = document.getElementById('estado');
  if (!est) return;

  function setValidity(ok, msg=''){
    est.classList.remove('is-valid','is-invalid');
    if (ok) {
      est.classList.add('is-valid');
      est.setCustomValidity('');
      est.setAttribute('aria-invalid','false');
      est.title = '';
    } else {
      est.classList.add('is-invalid');
      est.setCustomValidity(msg || 'Selecciona un estado de la lista');
      est.setAttribute('aria-invalid','true');
      est.title = msg || 'Selecciona un estado de la lista';
    }
  }

  function validarEstadoControl(){
    const tag = est.tagName.toLowerCase();
    const raw = (est.value || '').trim();

    if (tag === 'select') {
      // Válido si eligió una opción distinta del placeholder
      const ok = raw !== '' && est.selectedIndex > 0;
      setValidity(ok);
    } else {
      // input (con o sin datalist)
      const ok = raw !== '' && validos.has(norm(raw));
      setValidity(ok);
    }
  }

  // Valida al cambiar y al perder foco
  est.addEventListener('change', validarEstadoControl);
  est.addEventListener('blur',   validarEstadoControl);

  // Valida una vez al cargar (por si se autocompleta vía CP)
  validarEstadoControl();
});



// ====== Países (ES) y utilidades ======
const _norm = s => (s||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();

const PAISES_ES = [
  'México','Afganistán','Albania','Alemania','Andorra','Angola','Antigua y Barbuda','Arabia Saudita',
  'Argelia','Argentina','Armenia','Australia','Austria','Azerbaiyán','Bahamas','Bangladés','Barbados',
  'Baréin','Bélgica','Belice','Benín','Bielorrusia','Birmania (Myanmar)','Bolivia',
  'Bosnia y Herzegovina','Botsuana','Brasil','Brunéi','Bulgaria','Burkina Faso','Burundi','Bután',
  'Cabo Verde','Camboya','Camerún','Canadá','Catar','Chad','Chile','China','Chipre','Colombia','Comoras',
  'Corea del Norte','Corea del Sur','Costa de Marfil','Costa Rica','Croacia','Cuba','Dinamarca','Dominica',
  'Ecuador','Egipto','El Salvador','Emiratos Árabes Unidos','Eritrea','Eslovaquia','Eslovenia','España',
  'Estados Unidos','Estonia','Etiopía','Filipinas','Finlandia','Fiyi','Francia','Gabón','Gambia','Georgia',
  'Ghana','Granada','Grecia','Guatemala','Guinea','Guinea-Bisáu','Guinea Ecuatorial','Guyana','Haití',
  'Honduras','Hungría','India','Indonesia','Irak','Irán','Irlanda','Islandia','Islas Marshall',
  'Islas Salomón','Israel','Italia','Jamaica','Japón','Jordania','Kazajistán','Kenia','Kirguistán',
  'Kiribati','Kuwait','Laos','Lesoto','Letonia','Líbano','Liberia','Libia','Liechtenstein','Lituania',
  'Luxemburgo','Madagascar','Malasia','Malaui','Maldivas','Mali','Malta','Marruecos','Mauricio',
  'Mauritania','Micronesia','Moldavia','Mónaco','Mongolia','Montenegro','Mozambique','Namibia','Nauru',
  'Nepal','Nicaragua','Níger','Nigeria','Noruega','Nueva Zelanda','Omán','Países Bajos','Pakistán',
  'Palaos','Panamá','Papúa Nueva Guinea','Paraguay','Perú','Polonia','Portugal','Reino Unido',
  'República Centroafricana','República Checa','República del Congo','República Democrática del Congo',
  'República Dominicana','Ruanda','Rumania','Rusia','Samoa','San Cristóbal y Nieves','San Marino',
  'San Vicente y las Granadinas','Santa Lucía','Santo Tomé y Príncipe','Senegal','Serbia','Seychelles',
  'Sierra Leona','Singapur','Siria','Somalia','Sri Lanka','Esuatini (Suazilandia)','Sudáfrica','Sudán',
  'Sudán del Sur','Suecia','Suiza','Surinam','Tailandia','Tanzania','Tayikistán','Timor Oriental','Togo',
  'Tonga','Trinidad y Tobago','Túnez','Turquía','Turkmenistán','Tuvalu','Ucrania','Uganda','Uruguay',
  'Uzbekistán','Vanuatu','Vaticano','Venezuela','Vietnam','Yemen','Yibuti','Zambia','Zimbabue'
];

// México primero, resto alfabético
function _ordenPaises(){
  const otros = PAISES_ES.filter(p => p !== 'México')
    .sort((a,b)=>a.localeCompare(b,'es',{sensitivity:'base'}));
  return ['México', ...otros];
}

// Poblar <select> con países
function poblarSelectPaises(selectEl){
  if (!selectEl) return;
  const orden = _ordenPaises();
  selectEl.innerHTML = '';
  const ph = document.createElement('option');
  ph.value = ''; ph.disabled = true; ph.selected = true; ph.textContent = 'Seleccione un país…';
  selectEl.appendChild(ph);
  orden.forEach(p=>{
    const opt = document.createElement('option');
    opt.value = p; opt.textContent = p;
    selectEl.appendChild(opt);
  });
}

// Seleccionar por texto (sirve para select; input queda de fallback)
function setPaisControlValue(ctrl, nombre){
  if (!ctrl) return;
  const objetivo = _norm(nombre);
  if (ctrl.tagName?.toLowerCase() === 'select'){
    const match = Array.from(ctrl.options||[]).find(o => _norm(o.value||o.textContent) === objetivo);
    if (match){
      ctrl.value = match.value;
    } else {
      ctrl.selectedIndex = 0;
    }
    ctrl.dispatchEvent(new Event('change',{bubbles:true}));
  } else {
    ctrl.value = nombre || '';
    ctrl.dispatchEvent(new Event('input',{bubbles:true}));
    ctrl.dispatchEvent(new Event('blur',{bubbles:true}));
  }
}

// Validación visual (Bootstrap + fallback)
function validarPaisControl(el){
  if (!el) return;
  const ok = el.tagName.toLowerCase()==='select'
    ? (el.value !== '' && el.selectedIndex > 0)
    : (!!el.value.trim() && _ordenPaises().some(p => _norm(p) === _norm(el.value)));

  el.classList.remove('is-valid','is-invalid');
  if (ok){
    el.classList.add('is-valid');
    el.setCustomValidity('');
    el.setAttribute('aria-invalid','false');
    el.title = '';
  } else {
    el.classList.add('is-invalid');
    el.setCustomValidity('Seleccione un país válido');
    el.setAttribute('aria-invalid','true');
    el.title = 'Seleccione un país válido';
  }
}

// Convierte #pais_nacimiento (si aún es input) a <select>, lo puebla y devuelve el select
function upgradeInputToSelectPais(id){
  let el = document.getElementById(id);
  if (!el) return null;

  if (el.tagName.toLowerCase() !== 'select'){
    const sel = document.createElement('select');
    sel.id = el.id;
    sel.name = el.getAttribute('name') || el.id;
    sel.className = el.className;
    if (el.hasAttribute('required')) sel.required = true;
    sel.setAttribute('autocomplete','country-name');
    el.replaceWith(sel);
    el = sel;
  }

  poblarSelectPaises(el);
  return el;
}

// ---- Arranque
document.addEventListener('DOMContentLoaded', () => {
  // #pais (select ya en HTML)
  const selPais = document.getElementById('pais');
  if (selPais){
    poblarSelectPaises(selPais);
    selPais.addEventListener('change', ()=>validarPaisControl(selPais));
    selPais.addEventListener('blur',   ()=>validarPaisControl(selPais));
    validarPaisControl(selPais);
  }

  // #pais_nacimiento (era input -> lo volvemos select)
  const selNac = upgradeInputToSelectPais('pais_nacimiento');
  if (selNac){
    selNac.addEventListener('change', ()=>validarPaisControl(selNac));
    selNac.addEventListener('blur',   ()=>validarPaisControl(selNac));
    validarPaisControl(selNac);
  }

  // útil desde otros scripts (CP/CURP)
  window.setPaisControlValue = setPaisControlValue;
});



// ===== RFC persona física: base10 + deja escribir homoclave (3) =====
// ================== RFC (base10) CON ESTILO COMO CURP ==================
// Usa tus helpers existentes: mxNorm, primeraVocalInterna y parseFechaFlexible si están.
// Si no existen, declaramos mínimos de respaldo:
const _mxNormRFC = (typeof mxNorm === 'function')
  ? mxNorm
  : (s='') => s.normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/\s+/g,' ').trim().toLowerCase();

const _primVocalRFC = (typeof primeraVocalInterna === 'function')
  ? primeraVocalInterna
  : function(s=''){ s=_mxNormRFC(s).toUpperCase(); for(let i=1;i<s.length;i++) if('AEIOU'.includes(s[i])) return s[i]; return 'X'; };

const _parseFechaRFC = (typeof parseFechaFlexible === 'function')
  ? parseFechaFlexible
  : function(str=''){ const s=(str||'').replace(/\s+/g,''); if(/^\d{4}[-/]\d{2}[-/]\d{2}$/.test(s)){const [Y,M,D]=s.split(/[-/]/).map(Number); return new Date(Y,M-1,D);} if(/^\d{2}[-/]\d{2}[-/]\d{4}$/.test(s)){const [D,M,Y]=s.split(/[-/]/).map(Number); return new Date(Y,M-1,D);} return new Date(NaN); };

const RFC_BANNED = new Set([
  'BUEI','BUEY','CACA','CACO','CAGA','CAGO','CAKA','CAKO','COGE','COGI','COJA','COJE','COJI','COJO',
  'COLA','CULO','FALO','FETO','GUEY','JOTO','KACA','KACO','KAGA','KAGO','KOGE','KOGI','KOJA','KOJE',
  'KOJI','KOJO','KOLA','KULO','MAME','MAMO','MEAR','MEAS','MEON','MION','MOCO','MULA','PEDA','PEDO',
  'PENE','PUTA','PUTO','QULO','RATA','RUIN'
]);
const rfcSafe4 = p => RFC_BANNED.has((p||'XXXX').slice(0,4))
  ? (p[0] + 'X' + p.slice(2,4))
  : (p||'XXXX').slice(0,4);

// --- Estilos del input RFC (igual que CURP: Bootstrap + fallback) ---
function setRfcValidityStyle(state){ // 'valid' | 'invalid' | 'neutral'
  const el = document.getElementById('rfc');
  if (!el) return;
  el.classList.remove('is-valid','is-invalid');
  el.removeAttribute('aria-invalid');
  el.style.borderColor = '';
  el.style.boxShadow   = '';
  if (state === 'valid'){
    el.classList.add('is-valid');
    el.style.borderColor = '#198754';
    el.style.boxShadow   = '0 0 0 .2rem rgba(25,135,84,.25)';
    el.setAttribute('aria-invalid','false');
  } else if (state === 'invalid'){
    el.classList.add('is-invalid');
    el.style.borderColor = '#dc3545';
    el.style.boxShadow   = '0 0 0 .2rem rgba(220,53,69,.25)';
    el.setAttribute('aria-invalid','true');
  }
}

// --- Hint debajo del RFC ---
function getOrCreateRfcHint(){
  const el = document.getElementById('rfc'); if (!el) return null;
  let hint = document.getElementById('rfc-hint');
  if (!hint){
    hint = document.createElement('small');
    hint.id = 'rfc-hint';
    hint.className = 'form-text';
    hint.style.display = 'block';
    hint.style.marginTop = '4px';
    hint.style.fontSize  = '12px';
    el.insertAdjacentElement('afterend', hint);
  }
  return hint;
}

function actualizarRfcHint(){
  const rfcEl = document.getElementById('rfc');
  const hint  = getOrCreateRfcHint();
  if (!rfcEl || !hint) return;

  const v = (rfcEl.value||'').trim();
  if (v.length < 10){
    hint.textContent = `Faltan ${10 - v.length} caracteres de la base.`;
    hint.style.color = '#cc8b00';
    setRfcValidityStyle('invalid');
  } else if (v.length === 10){
    hint.textContent = 'Faltan 3 caracteres (homoclave SAT).';
    hint.style.color = '#cc8b00';
    setRfcValidityStyle('invalid');
  } else if (v.length === 11){
    hint.textContent = 'Faltan 2 caracteres (homoclave SAT).';
    hint.style.color = '#cc8b00';
    setRfcValidityStyle('invalid');
  } else if (v.length === 12){
    hint.textContent = 'Falta 1 carácter (homoclave SAT).';
    hint.style.color = '#cc8b00';
    setRfcValidityStyle('invalid');
  } else if (v.length === 13){
    hint.textContent = 'RFC completo (13/13).';
    hint.style.color = '#198754';
    setRfcValidityStyle('valid');
  } else if (v.length > 13){
    hint.textContent = `Sobran ${v.length - 13} caracteres.`;
    hint.style.color = '#dc3545';
    setRfcValidityStyle('invalid');
  } else {
    hint.textContent = '';
    setRfcValidityStyle('neutral');
  }
}

// --- Cálculo base-10 y preserva homoclave escrita ---
function calcularRFC_Base10(){
  const rfcEl = document.getElementById('rfc'); if (!rfcEl) return;

  const nombres = (document.getElementById('nombres')?.value || '');
  const apPat   = (document.getElementById('apellido_paterno')?.value || '');
  const apMat   = (document.getElementById('apellido_materno')?.value || '');
  const fecha   = (document.getElementById('fecha_nacimiento')?.value || '');

  const N = _mxNormRFC(nombres).toUpperCase();
  const P = _mxNormRFC(apPat).toUpperCase();
  const M = _mxNormRFC(apMat).toUpperCase();

  // omitir JOSE / MARIA / J / MA
  const STOP = new Set(['JOSE','J','J.','MARIA','MA','MA.']);
  const partes = N.split(/\s+/).filter(Boolean);
  let primerNombre = partes[0] || 'X';
  if (STOP.has(primerNombre)) primerNombre = partes[1] || primerNombre;

  // 4 letras
  let pref4 = (P[0]||'X') + _primVocalRFC(P) + (M[0]||'X') + (primerNombre[0]||'X');
  pref4 = rfcSafe4(pref4);

  // fecha YYMMDD
  const d = _parseFechaRFC(fecha);
  if (isNaN(d.getTime())){ rfcEl.value=''; actualizarRfcHint(); return; }
  const YY = String(d.getFullYear()).slice(-2);
  const MM = String(d.getMonth()+1).padStart(2,'0');
  const DD = String(d.getDate()).padStart(2,'0');

  const base10 = pref4 + YY + MM + DD;

  // preserva homoclave si ya está y la base coincide
  const actual = (rfcEl.value||'').toUpperCase().replace(/\s+/g,'');
  rfcEl.value = (actual.length>=10 && actual.startsWith(base10))
    ? actual.slice(0,13)
    : base10;

  actualizarRfcHint();
}

// --- Listeners y saneo de input ---
document.addEventListener('DOMContentLoaded', ()=>{
  const rfcEl = document.getElementById('rfc');
  if (rfcEl){
    rfcEl.setAttribute('maxlength','13');
    rfcEl.addEventListener('input', ()=>{
      rfcEl.value = (rfcEl.value||'').toUpperCase().replace(/[^A-Z0-9Ñ&]/g,'').slice(0,13);
      actualizarRfcHint();
    });
  }

  ['nombres','apellido_paterno','apellido_materno','fecha_nacimiento'].forEach(id=>{
    const el = document.getElementById(id);
    if (el){
      el.addEventListener('input',  calcularRFC_Base10);
      el.addEventListener('blur',   calcularRFC_Base10);
      el.addEventListener('change', calcularRFC_Base10);
    }
  });

  calcularRFC_Base10();
  actualizarRfcHint(); // pinta estado inicial
});



// Convierte #tiempo_<id> en: [ NÚMERO ] año/años (con sufijo grande)
function upgradeInputToYearsOnly(id, { min=0, max=80, store='text', suffixSize='1.25rem' } = {}) {
  const old = document.getElementById(id);
  if (!old || old.dataset.yearsUpgraded === '1') return;   // evita doble init
  old.dataset.yearsUpgraded = '1';

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
  num.style.maxWidth = '320px';                 // hazlo más ancho aquí
  num.className = old.className || 'form-control';
  if (old.placeholder) num.placeholder = old.placeholder;
  if (old.hasAttribute('required')) num.required = true;

  const suf = document.createElement('span');
  suf.id = id + '_sufijo';
  suf.textContent = 'años';
  suf.style.fontSize = suffixSize;
  suf.style.fontWeight = '600';
  suf.style.lineHeight = '1';

  const hid = document.createElement('input');  // conserva id/name originales
  hid.type = 'hidden';
  hid.id = id;
  hid.name = old.getAttribute('name') || id;

  // prefill (si venía "3 años" o "3")
  (function prefill(){
    const v = (old.value || '').trim();
    const m = v.match(/(\d+)/);
    if (m) num.value = String(Math.max(min, Math.min(max, parseInt(m[1],10))));
  })();

  const clamp = n => Math.max(min, Math.min(max, n));
  function actualizar(){
    const n = (num.value === '' ? NaN : clamp(parseInt(num.value,10)));
    if (Number.isNaN(n)) { suf.textContent = 'años'; hid.value = ''; return; }
    suf.textContent = (n === 1) ? 'año' : 'años';
    hid.value = (store === 'num') ? String(n) : `${n} ${n===1?'año':'años'}`;
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
}

// Inicializa AMBOS campos
document.addEventListener('DOMContentLoaded', () => {
  upgradeInputToYearsOnly('tiempo_domicilio',     { max: 80, store: 'text', suffixSize: '1.25rem' });
  upgradeInputToYearsOnly('tiempo_estado_civil',  { max: 80, store: 'text', suffixSize: '1.25rem' });
});






// HORA PARA LA LLAMADA O VISITA 
// === Time Range Picker "tipo Calendar": define upgradeInputToTimeRangeFancy ===
(function(){
  const pad2 = n => String(n).padStart(2,'0');
  const toMin = s => { const m = /^(\d{1,2}):(\d{2})$/.exec(s||''); return m ? (+m[1])*60 + (+m[2]) : NaN; };
  const fromMin = m => isNaN(m)? '' : `${pad2(Math.floor(m/60))}:${pad2(m%60)}`;
  const fmt12 = hhmm => {
    const m = toMin(hhmm); if (isNaN(m)) return '';
    let h = Math.floor(m/60), mm = pad2(m%60), am = h<12;
    const h12 = ((h%12)||12); return `${h12}:${mm}${am?'am':'pm'}`;
  };
  const buildList = (startMin, endMin, stepMin) => {
    const out=[]; for(let t=startMin; t<=endMin; t+=stepMin) out.push(fromMin(t)); return out;
  };
  function createCombo(wrapper, items, onSelect){
    wrapper.classList.add('trp-input');
    const text = document.createElement('div'); text.className='trp-text'; text.textContent='—';
    const dd = document.createElement('div'); dd.className='trp-dd'; let idx=-1;
    items.forEach(v=>{
      const it=document.createElement('div'); it.className='trp-item'; it.dataset.value=v; it.textContent=fmt12(v);
      it.addEventListener('mousedown', e=>{ e.preventDefault(); onSelect(v); close(); });
      dd.appendChild(it);
    });
    function open(){ wrapper.classList.add('trp-open'); }
    function close(){ wrapper.classList.remove('trp-open'); idx=-1; setActive(); }
    function setActive(){
      Array.from(dd.children).forEach((el,i)=> el.classList.toggle('active', i===idx));
      if (idx>=0) dd.children[idx].scrollIntoView({block:'nearest'});
    }
    wrapper.addEventListener('click', ()=> wrapper.classList.contains('trp-open') ? close() : open());
    wrapper.addEventListener('keydown', e=>{
      const max = dd.children.length-1;
      if (e.key==='ArrowDown'){ e.preventDefault(); idx = Math.min(max, idx+1); setActive(); }
      else if (e.key==='ArrowUp'){ e.preventDefault(); idx = Math.max(0, idx<=0?0:idx-1); setActive(); }
      else if (e.key==='Enter'){ e.preventDefault(); if (idx>=0) { onSelect(dd.children[idx].dataset.value); close(); } }
      else if (e.key==='Escape'){ close(); }
    });
    document.addEventListener('click', e=>{ if (!wrapper.contains(e.target)) close(); });
    wrapper.appendChild(text); wrapper.appendChild(dd);
    return { setLabel:(v)=>{ text.textContent = v?fmt12(v):'—'; }, dd };
  }

  window.upgradeInputToTimeRangeFancy = function(id, {
    dayStart='08:00', dayEnd='20:00', stepMinutes=15, minGap=0, separator=' a '
  } = {}){
    const old = document.getElementById(id); if (!old) return;
    if (old.dataset.trpInit === '1') return;

    const root = document.createElement('div'); root.className='trp';
    const startWrap = document.createElement('div');
    const sep = document.createElement('span'); sep.textContent='—'; sep.className='trp-sep';
    const endWrap = document.createElement('div');
    const hint = document.createElement('small'); hint.id=id+'_hint'; hint.className='trp-hint';

    const hidden = document.createElement('input');
    hidden.type='hidden'; hidden.id=id; hidden.name=old.getAttribute('name')||id;

    startWrap.style.width = endWrap.style.width = '240px';

    const allTimes = buildList(toMin(dayStart), toMin(dayEnd), stepMinutes);

    let startVal='', endVal='';
    const startCombo = createCombo(startWrap, allTimes, v=>{
      startVal=v; startCombo.setLabel(v);
      const minEnd = toMin(v) + (minGap||0);
      Array.from(endCombo.dd.children).forEach(el => {
        el.style.display = (toMin(el.dataset.value) > minEnd) ? '' : 'none';
      });
      if (endVal && toMin(endVal) <= minEnd){ endVal=''; endCombo.setLabel(''); }
      refresh();
    });
    const endCombo = createCombo(endWrap, allTimes, v=>{ endVal=v; endCombo.setLabel(v); refresh(); });

    (function prefill(){
      const v=(old.value||'').trim();
      const m=/^\s*(\d{1,2}:\d{2})\s*(?:a|-|–|—)\s*(\d{1,2}:\d{2})\s*$/i.exec(v);
      if(m){ startVal=m[1]; endVal=m[2]; startCombo.setLabel(startVal); endCombo.setLabel(endVal); }
    })();

    function setState(s){ root.classList.remove('trp-valid','trp-invalid'); if(s==='valid')root.classList.add('trp-valid'); if(s==='invalid')root.classList.add('trp-invalid'); }
    function fmt(h1,h2){ return `Horario: ${fmt12(h1)}${separator}${fmt12(h2)}`; }
    function refresh(){
      if (!startVal && !endVal){ hidden.value=''; hint.textContent=''; setState(); return; }
      if (startVal && endVal){
        const ok = toMin(endVal) > toMin(startVal) + (minGap||0);
        if (ok){ hidden.value = `${startVal}${separator}${endVal}`; hint.textContent = fmt(startVal,endVal); hint.style.color='#198754'; setState('valid'); }
        else { hidden.value=''; hint.textContent='La hora final debe ser mayor que la inicial.'; hint.style.color='#dc3545'; setState('invalid'); }
      } else { hidden.value=''; hint.textContent='Completa ambas horas.'; hint.style.color='#cc8b00'; setState('invalid'); }
    }

    old.replaceWith(root);
    root.appendChild(startWrap); root.appendChild(sep); root.appendChild(endWrap);
    root.insertAdjacentElement('afterend', hint);
    root.appendChild(hidden);
    hidden.dataset.trpInit='1';
    refresh();
  };
})();


document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('mejor_hora');
  if (!el) return;
  if (typeof upgradeInputToTimeRangeFancy !== 'function') {
    console.error('upgradeInputToTimeRangeFancy no está definido (archivo no cargó o hubo un error previo).');
    return;
  }
  upgradeInputToTimeRangeFancy('mejor_hora', {
    dayStart: '08:00',
    dayEnd:   '20:00',
    stepMinutes: 15,
    minGap: 0
  });
});
