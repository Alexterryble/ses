/* ============================================================
   CARÁTULA: DATOS + CÁLCULOS + ENVIAR A RESUMEN
   Archivo sugerido:
   /sempiternal/public/js/caratulas/enviar-resumen.js

   ✅ Tasa ordinaria dinámica directa
      Ejemplo: si capturas 18, se usa 18%.
      NO se divide entre 1.16.

   ✅ CAT dinámico:
      tasa mensual x 12
      Ejemplo: 18 x 12 = 216.00%

   ✅ Seguro con checkbox
   ✅ Seguro automático por tramo y editable manualmente
   ✅ Investigación con checkbox y zona bloqueada/desbloqueada
   ✅ Envía datos limpios al resumen
   ✅ NO toca #btnGuardar para no romper el guardado real
============================================================ */

(function () {
  'use strict';

  const SESSION_KEY = 'CIP_DATOS_TABLA3';
  const SESSION_KEY_CARATULA = 'CIP_DATOS_CARATULA';

  const IDS = {
    /*
      IMPORTANTE:
      Antes estaba btnGuardar y eso rompía tu guardado real.
      Si quieres botón para resumen, crea uno así:
      <button type="button" id="btnEnviarResumen">📄 Enviar a resumen</button>
    */
    btn: 'btnEnviarResumen',

    monto: 'montoSolicitado',
    montoHidden: 'montoSolicitadoInput',
    zona: 'zonaSelect',
    fechaBase: 'fechaBase',
    plazo: 'plazo',
    producto: 'nombreProducto',
    fechaLarga: 'fechaLarga',
    fechaLimite: 'fechaMasUnAnio',
    fechaCorte: 'fechaMenosOcho',
    fechaLimiteISO: 'fechaLimitePagoISO',

    apertura: 'costo65',
    investigacion: 'costoInvestigacion',
    seguro: 'costoSeguro',

    /*
      En tu HTML actual:
      montoTotalPagar = base con comisiones
      montoLineaCredito = total final con interés
    */
    montoTotal: 'montoTotalPagar',
    montoLinea: 'montoLineaCredito',

    incluirSeguro: 'incluirSeguro',
    aplicarZona: 'aplicarZona',

    tasaInput: 'tasaOrdinariaInput',
    tasaTexto: 'tasaOrdinariaTexto',
    catTexto: 'catTexto'
  };

  const TASA_DEFAULT = 10.5;

  const TARIFAS_SEGURO = [
    { base: 250000, pct: 0.0185, costo:  4625.00 },
    { base: 350000, pct: 0.0180, costo:  6300.00 },
    { base: 450000, pct: 0.0175, costo:  7875.00 },
    { base: 550000, pct: 0.0170, costo:  9350.00 },
    { base: 650000, pct: 0.0165, costo: 10725.00 },
    { base: 750000, pct: 0.0160, costo: 12000.00 },
    { base: 850000, pct: 0.0155, costo: 13175.00 },
    { base: 950000, pct: 0.0150, costo: 14250.00 }
  ];

  const $id = id => document.getElementById(id);

  /* =========================
     HELPERS DE TEXTO / INPUT
  ========================= */

  function text(id) {
    const el = $id(id);
    if (!el) return '';

    if ('value' in el && el.tagName === 'SELECT') {
      return String(el.value || '').trim();
    }

    if ('value' in el && el.tagName === 'INPUT') {
      return String(el.value || '').trim();
    }

    return String(el.textContent || '').trim();
  }

  function value(id) {
    const el = $id(id);
    return el && 'value' in el ? String(el.value || '').trim() : '';
  }

  function setText(id, val) {
    const el = $id(id);
    if (!el) return;

    if (el.tagName === 'SELECT') {
      const v = String(val ?? '').trim();
      const existe = Array.from(el.options).some(op => op.value === v);

      if (existe) {
        el.value = v;
      } else if (v) {
        const op = document.createElement('option');
        op.value = v;
        op.textContent = v;
        el.appendChild(op);
        el.value = v;
      }

      return;
    }

    if ('value' in el && el.tagName === 'INPUT') {
      el.value = val ?? '';
      return;
    }

    el.textContent = val ?? '';
  }

  function setValue(id, val) {
    const el = $id(id);
    if (el && 'value' in el) {
      el.value = val ?? '';
    }
  }

  function raw(id) {
    return ($id(id)?.dataset?.raw || '').trim();
  }

  function money(n) {
    return new Intl.NumberFormat('es-MX', {
      style: 'currency',
      currency: 'MXN',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(Number(n) || 0);
  }

  function redondear2(n) {
    return Math.round((Number(n || 0) + Number.EPSILON) * 100) / 100;
  }

  function toNumber(v) {
    if (v === null || v === undefined) return 0;

    if (typeof v === 'number') {
      return Number.isFinite(v) ? v : 0;
    }

    let s = String(v)
      .replace(/\u00A0/g, '')
      .replace(/\s+/g, '')
      .replace(/\$/g, '')
      .trim();

    if (!s) return 0;

    s = s.replace(/[^\d.,-]/g, '');
    if (!s) return 0;

    const lastDot = s.lastIndexOf('.');
    const lastComma = s.lastIndexOf(',');

    let decimal = null;

    if (lastDot !== -1 && lastComma !== -1) {
      decimal = lastDot > lastComma ? '.' : ',';
    } else if (lastDot !== -1) {
      decimal = (s.length - lastDot - 1 <= 2) ? '.' : null;
    } else if (lastComma !== -1) {
      decimal = (s.length - lastComma - 1 <= 2) ? ',' : null;
    }

    if (decimal) {
      const thousands = decimal === '.' ? ',' : '.';
      s = s.replace(new RegExp('\\' + thousands, 'g'), '');

      if (decimal === ',') {
        s = s.replace(',', '.');
      }

      const parts = s.split('.');

      if (parts.length > 2) {
        const dec = parts.pop();
        s = parts.join('') + '.' + dec;
      }
    } else {
      s = s.replace(/[.,]/g, '');
    }

    const n = Number(s);
    return Number.isFinite(n) ? n : 0;
  }

  /* =========================
     SESSION
  ========================= */

  function getSession() {
    try {
      const dataCaratula = JSON.parse(sessionStorage.getItem(SESSION_KEY_CARATULA) || '{}');
      const dataTabla3 = JSON.parse(sessionStorage.getItem(SESSION_KEY) || '{}');

      return {
        ...dataCaratula,
        ...dataTabla3
      };
    } catch (e) {
      console.error('Error leyendo datos de sesión para carátula:', e);
      return {};
    }
  }

  function setSession(data) {
    sessionStorage.setItem(SESSION_KEY, JSON.stringify(data));
    sessionStorage.setItem(SESSION_KEY_CARATULA, JSON.stringify(data));
  }

  /* =========================
     RUTAS / FETCH
  ========================= */

  function getPublicBase() {
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

  function query(name) {
    return new URLSearchParams(location.search).get(name);
  }

  async function getJSON(url) {
    const res = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store'
    });

    const txt = await res.text();

    try {
      return JSON.parse(txt);
    } catch (e) {
      console.error('Respuesta no JSON:', txt);
      throw e;
    }
  }

  /* =========================
     PRODUCTOS / MODALIDAD
  ========================= */

  function productoDesdeModalidad(mod) {
    const m = String(mod || '').replace(/\s+/g, '').toUpperCase();

    if (m === 'SEM_P10') return 'Sem Personal 10';
    if (m === 'P10') return 'Unipersonal 10';
    if (m === 'P40') return 'Personal 40 Retro';

    if (m === 'P10_ORDINARIO' || m === 'P10ORDINARIO') {
      return 'Personal 10 Ordinario';
    }

    if (m === 'P40_ORDINARIO' || m === 'P40ORDINARIO') {
      return 'Personal 40 Ordinario';
    }

    return 'Préstamo Personal';
  }

  /* =========================
     DATOS PHP / SESSION
  ========================= */

  async function getSolicitudPHP() {
    const solicitudId =
      query('solicitud_id') ||
      query('id') ||
      document.querySelector('[data-solicitud-id]')?.dataset?.solicitudId ||
      '';

    if (!solicitudId) return null;

    const url =
      `${getPublicBase()}app/controllers/obtener_datos/get_solicitud_por_id.php?solicitud_id=${encodeURIComponent(solicitudId)}`;

    try {
      const json = await getJSON(url);

      if (!json?.ok) return null;

      const cli = json.cliente || {};
      const sol = json.solicitud || {};

      const nombre = cli.nombre ||
        [cli.nombres, cli.apellido_paterno, cli.apellido_materno]
          .filter(Boolean)
          .join(' ');

      const monto = Number(sol.monto || 0);
      const plazo = sol.plazo_meses || sol.plazo || '';
      const modalidad = sol.contrato_modalidad || '';

      window.CONTRATO_MODALIDAD = String(modalidad || '').trim().toUpperCase();

      return {
        fuente: 'php',
        solicitud_id: solicitudId,
        folio: sol.folio || '',
        nombre: nombre || '',
        rfc: (cli.rfc || '').toUpperCase(),
        curp: (cli.curp || '').toUpperCase(),
        nss: cli.nss || sol.nss || '',
        montoSolicitadoNumero: monto,
        montoSolicitado: money(monto),
        plazoCredito: String(plazo || ''),
        plazoCreditoTexto: plazo ? `${plazo} meses` : '',
        contratoModalidad: modalidad,
        nombreProducto: productoDesdeModalidad(modalidad),
        tasaMensual: sol.tasa_mensual || sol.tasa || ''
      };
    } catch (e) {
      console.error('Error consultando PHP:', e);
      return null;
    }
  }

  function pintarDatos(data) {
    if (!data) return;

    const montoBaseRaw =
      toNumber(data.montoGrandeNumero) ||
      toNumber(data.montoGrande) ||
      toNumber(data.montoRetroactivoNumero) ||
      toNumber(data.montoRetroactivo) ||
      toNumber(data.resultadoTotalAfore) ||
      toNumber(data.montoSolicitadoNumero) ||
      toNumber(data.montoSolicitado) ||
      toNumber(data.cipFinancialMexico) ||
      0;

    const montoBase = montoBaseRaw > 0 && montoBaseRaw <= 10000000
      ? redondear2(montoBaseRaw)
      : 0;

    if (montoBaseRaw > 10000000) {
      console.warn('⚠️ Monto rechazado por ser demasiado grande:', {
        montoBaseRaw,
        montoGrande: data.montoGrande,
        montoGrandeNumero: data.montoGrandeNumero,
        montoRetroactivo: data.montoRetroactivo,
        montoSolicitado: data.montoSolicitado
      });
    }

    const plazoBase =
      String(data.plazoCredito || data.plazo || '10').replace(/[^\d]/g, '') || '10';

    const producto =
      data.nombreProducto ||
      data.contratoModalidad ||
      data.tipoCredito ||
      'Préstamo Personal';

    setText('ctlNombre', data.nombre || '—');
    setText('nombreCliente', data.nombre || '—');
    setText('titularNombre', data.nombre || '—');

    setText('ctlNSS', data.nss || '—');
    setText('nssCliente', data.nss || '—');
    setValue('titularNss', data.nss || '');

    setText('ctlCURP', data.curp || '—');
    setText('curpCliente', data.curp || '—');
    setText('titular-curp', data.curp || '—');

    setText('ctlRFC', data.rfc || '—');
    setText('rfcCliente', data.rfc || '—');

    setText('folio', data.folio || '—');
    setText(IDS.producto, producto);

    const tasaInput = $id(IDS.tasaInput);

    if (tasaInput) {
      const tasaSesion =
        toNumber(data.tasaMensual) ||
        toNumber(data.tasa_mensual) ||
        toNumber(data.tasaOrdinariaConIVANumero) ||
        toNumber(data.tasaOrdinariaSinIVANumero);

      if (tasaSesion > 0) {
        tasaInput.value = tasaSesion.toFixed(2);
      } else if (!tasaInput.value) {
        tasaInput.value = TASA_DEFAULT.toFixed(2);
      }
    }

    if (montoBase > 0) {
      const el = $id(IDS.monto);

      if (el) {
        el.dataset.raw = String(montoBase);
        el.dataset.rawInput = String(montoBase);
        el.textContent = money(montoBase);
      }

      const hidden = asegurarMontoHidden();

      hidden.value = String(montoBase);
      hidden.dataset.raw = String(montoBase);
      hidden.dataset.rawInput = String(montoBase);

      setSession({
        ...getSession(),
        montoSolicitado: money(montoBase),
        montoSolicitadoNumero: montoBase,
        montoGrande: money(montoBase),
        montoGrandeNumero: montoBase,
        updatedAt: Date.now()
      });
    }

    const plazo = $id(IDS.plazo);

    if (plazo) {
      plazo.textContent = plazoBase;
      plazo.setAttribute('aria-valuenow', plazoBase);
    }
  }

  async function cargarDatosIniciales() {
    const dataPHP = await getSolicitudPHP();

    if (dataPHP) {
      const payload = {
        ...getSession(),
        ...dataPHP,
        updatedAt: Date.now()
      };

      setSession(payload);
      pintarDatos(payload);
      return payload;
    }

    const dataSession = getSession();

    pintarDatos(dataSession);

    return dataSession;
  }

  /* =========================
     MONTO EDITABLE
  ========================= */

  function limpiarMontoInput(v) {
    let s = String(v || '')
      .replace(/\u00A0/g, '')
      .replace(/\s+/g, '')
      .replace(/\$/g, '')
      .replace(/,/g, '')
      .replace(/[^\d.]/g, '');

    if (!s) return '';

    const firstDot = s.indexOf('.');

    if (firstDot !== -1) {
      s = s.slice(0, firstDot + 1) + s.slice(firstDot + 1).replace(/\./g, '');
    }

    let [entero, decimal] = s.split('.');

    if (s.startsWith('.')) entero = '0';

    entero = String(entero || '').replace(/^0+(?=\d)/, '');

    if (entero === '' && s.includes('.')) entero = '0';

    if (decimal !== undefined) {
      decimal = decimal.slice(0, 2);
      return `${entero}.${decimal}`;
    }

    return entero;
  }

  function asegurarMontoHidden() {
    let hidden = $id(IDS.montoHidden);

    if (!hidden) {
      hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.id = IDS.montoHidden;
      document.body.appendChild(hidden);
    }

    return hidden;
  }

  function cursorFinal(el) {
    if (!el) return;

    const range = document.createRange();
    const sel = window.getSelection();

    range.selectNodeContents(el);
    range.collapse(false);

    sel.removeAllRanges();
    sel.addRange(range);
  }

  function haySeleccion(el) {
    const sel = window.getSelection();

    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) return false;

    return el.contains(sel.getRangeAt(0).commonAncestorContainer);
  }

  function setMontoRaw(rawValue, formatear = false) {
    const el = $id(IDS.monto);

    if (!el) return;

    const rawClean = limpiarMontoInput(rawValue);
    const numero = Number(rawClean) || 0;

    el.dataset.raw = String(numero);
    el.dataset.rawInput = rawClean;

    const hidden = asegurarMontoHidden();

    hidden.value = String(numero);
    hidden.dataset.raw = String(numero);
    hidden.dataset.rawInput = rawClean;

    hidden.dispatchEvent(new Event('input', { bubbles: true }));
    hidden.dispatchEvent(new Event('change', { bubbles: true }));

    if (formatear) {
      el.textContent = numero > 0 ? money(numero) : '';
    } else {
      el.textContent = rawClean;
    }

    cursorFinal(el);
    calcularTodoDebounced();
  }

  function iniciarMontoEditable() {
    const el = $id(IDS.monto);

    if (!el) return;

    el.setAttribute('contenteditable', 'true');
    el.setAttribute('inputmode', 'decimal');
    el.style.outline = 'none';

    const inicial = toNumber(el.dataset.rawInput || el.dataset.raw || el.textContent);

    el.dataset.raw = String(inicial);
    el.dataset.rawInput = inicial > 0 ? String(inicial) : '';

    if (inicial > 0) {
      el.textContent = money(inicial);
    }

    el.addEventListener('focus', () => {
      const n = toNumber(el.dataset.raw || el.textContent);

      el.textContent = n > 0 ? String(n) : '';
      el.dataset.rawInput = n > 0 ? String(n) : '';

      setTimeout(() => cursorFinal(el), 0);
    });

    el.addEventListener('keydown', e => {
      const key = e.key;

      if (
        ['Tab', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(key) ||
        ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(key.toLowerCase()))
      ) {
        return;
      }

      e.preventDefault();

      let actual = el.dataset.rawInput || '';

      if (/^\d$/.test(key)) {
        setMontoRaw(haySeleccion(el) ? key : actual + key, false);
        return;
      }

      if (key === '.' || key === ',') {
        if (haySeleccion(el)) {
          setMontoRaw('0.', false);
        } else if (!actual.includes('.')) {
          setMontoRaw((actual || '0') + '.', false);
        }
        return;
      }

      if (key === 'Backspace') {
        setMontoRaw(haySeleccion(el) ? '' : actual.slice(0, -1), false);
        return;
      }

      if (key === 'Delete') {
        setMontoRaw('', false);
        return;
      }

      if (key === 'Enter') {
        el.blur();
      }
    });

    el.addEventListener('paste', e => {
      e.preventDefault();

      const txt = (e.clipboardData || window.clipboardData).getData('text') || '';

      setMontoRaw(txt, false);
    });

    el.addEventListener('blur', () => {
      const n = toNumber(el.dataset.raw || el.textContent);

      el.dataset.raw = String(n);
      el.dataset.rawInput = n > 0 ? String(n) : '';
      el.textContent = n > 0 ? money(n) : '';

      calcularTodoDebounced();
    });

    el.addEventListener('click', () => cursorFinal(el));
  }

  /* =========================
     TASA / CAT CORREGIDO
  ========================= */

function getTasaConIVA() {
  const input = $id(IDS.tasaInput);
  const n = toNumber(input?.value || TASA_DEFAULT);

  return Number.isFinite(n) && n >= 0 ? n : 0;
}

/*
  Tasa ordinaria SIN IVA.
  Ejemplo:
  10.50 / 1.16 = 9.05
  11.00 / 1.16 = 9.48
*/
function getTasaSinIVAPorcentaje() {
  return redondear2(getTasaConIVA() / 1.16);
}

/*
  Esta es la tasa que se usa para calcular interés.
  Si quieres que el cálculo del total se haga SIN IVA, debe usar esta.
*/
function getTasaMensualSinIVA() {
  return getTasaSinIVAPorcentaje() / 100;
}

/*
  CAT SIN IVA.
  Ejemplo:
  9.05 * 12 = 108.62
*/
function getCATNumero() {
  return redondear2(getTasaSinIVAPorcentaje() * 12);
}

function pintarTasaYCat() {
  const tasaTexto = $id(IDS.tasaTexto);

  if (tasaTexto) {
    tasaTexto.textContent = `${getTasaSinIVAPorcentaje().toFixed(2)}%`;
  }

  const catTexto = $id(IDS.catTexto);

  if (catTexto) {
    catTexto.textContent = `${getCATNumero().toFixed(2)}%`;
  }
}

  /* =========================
     ZONAS / CHECKS
  ========================= */

  function pctToFactor(pct) {
    const n = Number(pct || 0);

    if (!Number.isFinite(n) || n <= 0) return 0;

    return n >= 1 ? n / 100 : n;
  }

  function seguroActivado() {
    return $id(IDS.incluirSeguro)?.checked === true;
  }

  function investigacionActivada() {
    return $id(IDS.aplicarZona)?.checked === true;
  }

  function actualizarEstadoZona() {
    const sel = $id(IDS.zona);

    if (!sel) return;

    sel.disabled = !investigacionActivada();

    if (!investigacionActivada()) {
      sel.value = '';
    }
  }

  function limpiarSeguroSiApagado() {
    const el = $id(IDS.seguro);

    if (!el) return;

    if (!seguroActivado()) {
      el.dataset.manual = '0';
      el.dataset.raw = '0';

      if ('value' in el && el.tagName === 'INPUT') {
        el.value = money(0);
      } else {
        el.textContent = money(0);
      }
    }
  }

  async function cargarZonas() {
    const sel = $id(IDS.zona);

    if (!sel) return;

    const url = `${getPublicBase()}app/controllers/concredito/listar.php`;

    try {
      sel.innerHTML = '<option value="">Cargando zonas...</option>';

      const json = await getJSON(url);

      if (!json?.ok) {
        throw new Error(json?.msg || 'Error al cargar zonas');
      }

      sel.innerHTML = '<option value="">Selecciona zona...</option>';

      (json.items || []).forEach(z => {
        const op = document.createElement('option');

        op.value = z.id;
        op.textContent = z.etiqueta || `Zona ${z.id}`;

        op.dataset.cuota = z.cuota_fija ?? 0;
        op.dataset.pct = z.pct_monto ?? 0;

        if (z.minimo != null) op.dataset.min = z.minimo;
        if (z.maximo != null) op.dataset.max = z.maximo;

        sel.appendChild(op);
      });

      if (!sel.value && sel.options.length > 1) {
        sel.selectedIndex = 1;
      }

      actualizarEstadoZona();

      sel.dispatchEvent(new Event('change', { bubbles: true }));

      if (window.DEBUG_CARATULA === true) {
        console.log('✅ Zonas cargadas:', json.items || []);
      }
    } catch (e) {
      console.error('Error cargando zonas:', e);

      sel.innerHTML = '<option value="">No se pudieron cargar zonas</option>';

      actualizarEstadoZona();
    }
  }

  /* =========================
     FECHAS
  ========================= */

  function isoLocal(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');

    return `${y}-${m}-${day}`;
  }

  function fechaLarga(d) {
    if (!(d instanceof Date) || isNaN(d)) return '—';

    const txt = d.toLocaleDateString('es-MX', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric'
    });

    return txt.charAt(0).toUpperCase() + txt.slice(1);
  }

  function sumarMeses(fecha, meses) {
    const f = new Date(fecha.getTime());
    const dia = f.getDate();

    f.setMonth(f.getMonth() + meses);

    if (f.getDate() < dia) {
      f.setDate(0);
    }

    return f;
  }

  function getPlazo() {
    return parseInt(text(IDS.plazo).replace(/[^\d]/g, ''), 10) || 0;
  }

  function actualizarFechas() {
    const inp = $id(IDS.fechaBase);

    if (!inp) return;

    if (!inp.value) {
      inp.value = isoLocal(new Date());
    }

    const base = new Date(inp.value + 'T00:00:00');

    if (isNaN(base)) return;

    const plazo = getPlazo();
    const limite = plazo > 0 ? sumarMeses(base, plazo) : base;

    const corte = new Date(limite);

    corte.setDate(corte.getDate() - 8);

    setText(IDS.fechaLarga, fechaLarga(base));
    setText(IDS.fechaLimite, fechaLarga(limite));
    setText(IDS.fechaCorte, fechaLarga(corte));
    setValue(IDS.fechaLimiteISO, isoLocal(limite));
  }

  /* =========================
     CÁLCULOS
  ========================= */

  function getMontoSolicitadoNumero() {
    return (
      toNumber(raw(IDS.montoHidden)) ||
      toNumber(value(IDS.montoHidden)) ||
      toNumber(raw(IDS.monto)) ||
      toNumber(text(IDS.monto))
    );
  }

  function calcularApertura(monto) {
    return redondear2(Math.max(0, monto * 0.065));
  }

  function calcularInvestigacion(monto) {
    if (!investigacionActivada()) return 0;

    const sel = $id(IDS.zona);
    const opt = sel?.selectedOptions?.[0];

    if (!opt || !opt.value) return 0;

    const fijo = Number(opt.dataset.cuota) || 0;
    const pct = pctToFactor(opt.dataset.pct);
    const min = opt.dataset.min != null ? Number(opt.dataset.min) : null;
    const max = opt.dataset.max != null ? Number(opt.dataset.max) : null;

    let extra = fijo + (monto * pct);

    if (min != null) extra = Math.max(extra, min);
    if (max != null) extra = Math.min(extra, max);

    return redondear2(extra);
  }

  function calcularSeguro(monto) {
    if (!seguroActivado() || monto <= 0) return 0;

    const tramo =
      TARIFAS_SEGURO.find(t => monto <= t.base) ||
      TARIFAS_SEGURO[TARIFAS_SEGURO.length - 1];

    const costo = Number.isFinite(Number(tramo.costo))
      ? Number(tramo.costo)
      : Number(tramo.base || 0) * Number(tramo.pct || 0);

    return redondear2(costo);
  }

  function getSeguroManualNumero() {
    const el = $id(IDS.seguro);

    if (!el) return null;

    if (el.dataset.manual !== '1') return null;

    const n = toNumber(el.dataset.raw || value(IDS.seguro) || text(IDS.seguro));

    return Number.isFinite(n) ? n : 0;
  }

  function setSeguroTexto(valor, manual = false) {
    const el = $id(IDS.seguro);

    if (!el) return;

    el.dataset.raw = String(valor);
    el.dataset.manual = manual ? '1' : '0';

    if (document.activeElement === el) return;

    if ('value' in el && el.tagName === 'INPUT') {
      el.value = money(valor);
    } else {
      el.textContent = money(valor);
    }
  }

  function pintarCalculos() {
    const monto = getMontoSolicitadoNumero();

    const apertura = calcularApertura(monto);
    const investigacion = calcularInvestigacion(monto);

    const seguroManual = getSeguroManualNumero();

    let seguro = 0;

    if (seguroActivado()) {
      seguro = seguroManual !== null ? seguroManual : calcularSeguro(monto);
    }

    const comisiones = redondear2(apertura + investigacion + seguro);
    const montoTotal = redondear2(monto + comisiones);
    const plazo = getPlazo();

const tasaMensualCalculo = getTasaConIVA() / 100;
const interes = redondear2(montoTotal * tasaMensualCalculo * plazo);
const montoLinea = redondear2(montoTotal + interes);

    setText(IDS.apertura, money(apertura));
    setText(IDS.investigacion, money(investigacion));
    setSeguroTexto(seguro, seguroActivado() && seguroManual !== null);
    setText(IDS.montoTotal, money(montoTotal));
    setText(IDS.montoLinea, money(montoLinea));

    [
      [IDS.apertura, apertura],
      [IDS.investigacion, investigacion],
      [IDS.seguro, seguro],
      [IDS.montoTotal, montoTotal],
      [IDS.montoLinea, montoLinea]
    ].forEach(([id, val]) => {
      const el = $id(id);

      if (el) {
        el.dataset.raw = String(val);
      }
    });

    pintarTasaYCat();
    actualizarFechas();

    if (window.DEBUG_CARATULA === true) {
      console.log('✅ Cálculos de carátula:');
      console.table({
        montoSolicitado: money(monto),
        apertura: money(apertura),
        investigacion: money(investigacion),
        seguro: money(seguro),
        comisiones: money(comisiones),
        montoTotalPagar: money(montoTotal),
        plazo,
        tasaCapturada: `${getTasaConIVA().toFixed(2)}%`,
        tasaAplicada: `${getTasaSinIVAPorcentaje().toFixed(2)}%`,
        cat: `${getCATNumero().toFixed(2)}%`,
        interes: money(interes),
        montoLineaCredito: money(montoLinea)
      });
    }
  }

  let timer = null;

  function calcularTodoDebounced() {
    clearTimeout(timer);
    timer = setTimeout(pintarCalculos, 80);
  }

  /* =========================
     DATOS PARA RESUMEN
  ========================= */

  function getMontoLineaCreditoNumero() {
    return toNumber(raw(IDS.montoLinea)) || toNumber(text(IDS.montoLinea));
  }

  function getMontoTotalPagarNumero() {
    return toNumber(raw(IDS.montoTotal)) || toNumber(text(IDS.montoTotal));
  }

  function getDatosCliente() {
    return {
      nombre: text('ctlNombre') || text('nombreCliente'),
      nss: text('ctlNSS') || text('nssCliente'),
      curp: text('ctlCURP') || text('curpCliente'),
      rfc: text('ctlRFC') || text('rfcCliente'),
      folio: text('folio'),
      nombreProducto: text(IDS.producto)
    };
  }

  function getDatosCaratula() {
    const montoSolicitado = getMontoSolicitadoNumero();
    const montoLinea = getMontoLineaCreditoNumero();
    const montoTotal = getMontoTotalPagarNumero();
    const plazo = getPlazo();

    const seguro =
      toNumber(raw(IDS.seguro)) ||
      toNumber(value(IDS.seguro)) ||
      toNumber(text(IDS.seguro));

    const investigacion =
      toNumber(raw(IDS.investigacion)) ||
      toNumber(text(IDS.investigacion));

    const apertura =
      toNumber(raw(IDS.apertura)) ||
      toNumber(text(IDS.apertura));

    return {
      montoSolicitado: money(montoSolicitado),
      montoSolicitadoNumero: montoSolicitado,

      /*
        Mantengo los mismos nombres que ya enviabas.
        montoLineaCredito = total final con interés.
        montoTotalPagar = base con comisiones.
      */
      montoLineaCredito: money(montoLinea),
      montoLineaCreditoNumero: montoLinea,

      montoTotalPagar: money(montoTotal),
      montoTotalPagarNumero: montoTotal,

      aperturaCredito: money(apertura),
      aperturaCreditoNumero: apertura,

      investigacion: money(investigacion),
      investigacionNumero: investigacion,
      aplicarZona: investigacionActivada() ? 1 : 0,
      zonaId: value(IDS.zona),
      zonaLabel: $id(IDS.zona)?.selectedOptions?.[0]?.textContent?.trim() || '',

      seguro: money(seguro),
      seguroNumero: seguro,
      incluirSeguro: seguroActivado() ? 1 : 0,

      /*
        Mantengo estos nombres para no afectar el resumen.
        Ahora ambas representan la tasa directa aplicada.
      */
      tasaOrdinariaConIVA: `${getTasaConIVA().toFixed(2)}%`,
      tasaOrdinariaConIVANumero: getTasaConIVA(),
      tasaOrdinariaSinIVA: `${getTasaSinIVAPorcentaje().toFixed(2)}%`,
      tasaOrdinariaSinIVANumero: getTasaSinIVAPorcentaje(),

      cat: `${getCATNumero().toFixed(2)}%`,
      catNumero: getCATNumero(),

      plazoCredito: String(plazo),
      plazoCreditoTexto: `${plazo} meses`,

      fechaLimitePago: text(IDS.fechaLimite),
      fechaCorte: text(IDS.fechaCorte)
    };
  }

  function enviarResumen() {
    pintarCalculos();

    const prev = getSession();
    const cliente = getDatosCliente();
    const caratula = getDatosCaratula();

    const payload = {
      ...prev,

      nombre: cliente.nombre || prev.nombre || '',
      nss: cliente.nss || prev.nss || '',
      curp: cliente.curp || prev.curp || '',
      rfc: cliente.rfc || prev.rfc || '',
      folio: cliente.folio || prev.folio || '',
      nombreProducto: cliente.nombreProducto || prev.nombreProducto || '',

      ...caratula,

      updatedAt: Date.now()
    };

    setSession(payload);

    if (window.DEBUG_CARATULA === true) {
      console.log('📋 Datos enviados a resumen:');
      console.table({
        nombre: payload.nombre,
        nss: payload.nss,
        curp: payload.curp,
        rfc: payload.rfc,
        folio: payload.folio,
        nombreProducto: payload.nombreProducto,
        montoSolicitado: payload.montoSolicitado,
        montoLineaCredito: payload.montoLineaCredito,
        montoTotalPagar: payload.montoTotalPagar,
        seguro: payload.seguro,
        investigacion: payload.investigacion,
        tasaOrdinariaSinIVA: payload.tasaOrdinariaSinIVA,
        cat: payload.cat,
        plazoCredito: payload.plazoCreditoTexto,
        fechaLimitePago: payload.fechaLimitePago,
        fechaCorte: payload.fechaCorte
      });
    }

    window.location.href = `${getPublicBase()}plantillaresumen.html`;
  }

  /* =========================
     SEGURO EDITABLE
  ========================= */

  function escribirSeguroEditando(valorTexto) {
    const el = $id(IDS.seguro);

    if (!el) return;

    const chkSeguro = $id(IDS.incluirSeguro);

    if (!seguroActivado() && chkSeguro) {
      chkSeguro.checked = true;
    }

    const n = toNumber(valorTexto);

    el.dataset.manual = '1';
    el.dataset.raw = String(n);
  }

  function iniciarSeguroEditable() {
    const el = $id(IDS.seguro);

    if (!el) return;

    /*
      Si es input, no necesita contenteditable.
      Si es div/span, sí se lo ponemos.
    */
    if (!('value' in el && el.tagName === 'INPUT')) {
      el.setAttribute('contenteditable', 'true');
    }

    el.setAttribute('inputmode', 'decimal');
    el.setAttribute('role', 'textbox');
    el.setAttribute('aria-label', 'Costo del seguro');
    el.classList.add('editable-money');

    const valorInicial = toNumber(el.dataset.raw || value(IDS.seguro) || el.textContent);

    el.dataset.raw = String(valorInicial);

    if (!text(IDS.seguro)) {
      setSeguroTexto(valorInicial, el.dataset.manual === '1');
    }

    el.addEventListener('focus', () => {
      const n = toNumber(el.dataset.raw || value(IDS.seguro) || el.textContent);

      if ('value' in el && el.tagName === 'INPUT') {
        el.value = n > 0 ? String(n) : '';
      } else {
        el.textContent = n > 0 ? String(n) : '';
        setTimeout(() => cursorFinal(el), 0);
      }
    });

    el.addEventListener('input', () => {
      escribirSeguroEditando(value(IDS.seguro) || el.textContent);
      calcularTodoDebounced();
    });

    el.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        el.blur();
      }
    });

    el.addEventListener('blur', () => {
      escribirSeguroEditando(value(IDS.seguro) || el.textContent);

      const n = toNumber(el.dataset.raw);

      setSeguroTexto(n, true);
      calcularTodoDebounced();
    });
  }

  function observar(id) {
    const el = $id(id);

    if (!el) return;

    ['input', 'change', 'keyup', 'blur'].forEach(evt => {
      el.addEventListener(evt, calcularTodoDebounced);
    });

    const esEditable =
      el.isContentEditable ||
      el.tagName === 'SELECT' ||
      el.tagName === 'INPUT';

    if (
      esEditable &&
      id !== IDS.apertura &&
      id !== IDS.investigacion &&
      id !== IDS.seguro &&
      id !== IDS.monto
    ) {
      new MutationObserver(calcularTodoDebounced).observe(el, {
        childList: true,
        characterData: true,
        subtree: true
      });
    }
  }

  function prepararBoton() {
    /*
      NO tocamos #btnGuardar.
      Ese botón pertenece al guardado real de carátula.
      Este JS solo usará #btnEnviarResumen si existe.
    */
    const btn = $id(IDS.btn);

    if (!btn) {
      return;
    }

    btn.addEventListener('click', e => {
      e.preventDefault();
      enviarResumen();
    });
  }

  function prepararChecks() {
    const chkSeguro = $id(IDS.incluirSeguro);
    const chkZona = $id(IDS.aplicarZona);

    if (chkSeguro) {
      chkSeguro.addEventListener('change', () => {
        const seguroEl = $id(IDS.seguro);

        if (seguroEl) {
          if (!seguroActivado()) {
            seguroEl.dataset.manual = '0';
            seguroEl.dataset.raw = '0';

            if ('value' in seguroEl && seguroEl.tagName === 'INPUT') {
              seguroEl.value = money(0);
            } else {
              seguroEl.textContent = money(0);
            }
          } else {
            seguroEl.dataset.manual = '0';
          }
        }

        calcularTodoDebounced();
      });
    }

    if (chkZona) {
      chkZona.addEventListener('change', () => {
        actualizarEstadoZona();
        calcularTodoDebounced();
      });
    }
  }

  async function init() {
    await cargarDatosIniciales();
    await cargarZonas();

    iniciarMontoEditable();
    iniciarSeguroEditable();
    prepararChecks();

    [
      IDS.zona,
      IDS.fechaBase,
      IDS.plazo,
      IDS.monto,
      IDS.montoHidden,
      IDS.producto,
      IDS.incluirSeguro,
      IDS.aplicarZona,
      IDS.tasaInput
    ].forEach(observar);

    prepararBoton();

    actualizarEstadoZona();
    limpiarSeguroSiApagado();
    pintarTasaYCat();

    setTimeout(pintarCalculos, 100);
    setTimeout(pintarCalculos, 300);
    setTimeout(pintarCalculos, 900);
  }

  document.addEventListener('DOMContentLoaded', init);

  /* =========================
     FUNCIONES GLOBALES
  ========================= */

  window.getSolicitudDesdePHP = getSolicitudPHP;
  window.pintarCalculosCaratula = pintarCalculos;
  window.enviarCaratulaAResumen = enviarResumen;
  window.getDatosCaratulaResumen = getDatosCaratula;
  window.getMontoSolicitadoNumero = getMontoSolicitadoNumero;
  window.getMontoLineaCreditoNumero = getMontoLineaCreditoNumero;
  window.getMontoTotalPagarNumero = getMontoTotalPagarNumero;
  window.actualizarFechasCaratula = actualizarFechas;

  window.actualizarSeguro = () => {
    const seguroEl = $id(IDS.seguro);

    if (seguroEl) {
      seguroEl.dataset.manual = '0';
    }

    pintarCalculos();
  };
})();

/* =========================
   BOTONES EXTRA
   ------------------------------------------------------------
   Estos NO afectan btnGuardar.
========================= */

document.getElementById('btn-regresar')?.addEventListener('click', () => {
  window.location.href = '/analisis/afore/index.html';
});

document.getElementById('btn-menu')?.addEventListener('click', () => {
  window.location.href = '/index.php';
});

document.getElementById('btnImprimir')?.addEventListener('click', function (e) {
  e.preventDefault();

  if (typeof window.pintarCalculosCaratula === 'function') {
    window.pintarCalculosCaratula();
  }

  setTimeout(() => {
    window.print();
  }, 150);
});

document.addEventListener('DOMContentLoaded', () => {
  const imgsFirma = document.querySelectorAll('#sigPrintImage, .sig-print');

  imgsFirma.forEach(img => {
    const src = img.getAttribute('src');

    if (!src || src === '#' || src === 'undefined' || src === 'null') {
      img.removeAttribute('src');
      img.style.display = 'none';
    }
  });
});