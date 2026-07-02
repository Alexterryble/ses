/* ===== contrato_bene.js ===== */
(function () {
  // --- Config dinámica ---
  function getPublicBase(){
    const b = document.querySelector('base')?.href;
    if (b) return b.replace(/\/+$/, '/');

    const { origin, pathname } = window.location;
    const p = pathname.replace(/\/+$/, '/').toLowerCase();

    // Local XAMPP: /hp/public/
    if (p.includes('/hp/public/')) {
      return `${origin}/hp/public/`;
    }

    // Local XAMPP: /sempiternal/public/
    if (p.includes('/sempiternal/public/')) {
      return `${origin}/sempiternal/public/`;
    }

    // Render / Railway
    return `${origin}/`;
  }

  const PUBLIC = window.PUBLIC_BASE || window.BASE_URL || getPublicBase();

  const BENEFICIARIOS_API =
    `${PUBLIC}app/controllers/contratos/beneficiarios.php`;

  const OTRO_VALUE = '__OTRO__';
  const $ = (id) => document.getElementById(id);

  // --- Utils ---
  function reflejarBeneficiarioEnContrato(nombre) {
    const v = (nombre || '').trim() || 'SIN BENEFICIARIO';
    document.querySelectorAll('.out_beneficiario').forEach(el => {
      el.textContent = v;
    });
  }

  function getSolicId() {
    const sid = Number(new URLSearchParams(location.search).get('solicitud_id') || 0);
    return sid || null;
  }

  async function fetchBeneficiarios(sid) {
    const url = `${BENEFICIARIOS_API}?solicitud_id=${encodeURIComponent(sid)}&_=${Date.now()}`;

    const r = await fetch(url, {
      cache: 'no-store',
      credentials: 'same-origin'
    });

    const j = await r.json().catch(() => ({}));

    if (!r.ok || !j?.ok) {
      throw new Error(j?.error || 'No se pudieron obtener beneficiarios');
    }

    return j.beneficiarios || [];
  }

  // Muestra/oculta y limpia los extras si no es OTRO
  function toggleExtras() {
    const sel  = $('beneficiario_ctrl');
    const wrap = $('bene_extra_wrap');

    if (!sel || !wrap) return;

    const show = (!sel.hidden && sel.value === OTRO_VALUE);
    wrap.classList.toggle('hidden', !show);

    if (!show) {
      if ($('bene_extra_nombre')) $('bene_extra_nombre').value = '';
      if ($('bene_extra_parentesco')) $('bene_extra_parentesco').value = '';
      if ($('bene_extra_cel')) $('bene_extra_cel').value = '';
      if ($('bene_extra_mail')) $('bene_extra_mail').value = '';
    } else {
      $('bene_extra_nombre')?.focus();
    }
  }

  // ========== API pública ==========
  // Fija un beneficiario por nombre. Si no existe como opción, fuerza OTRO.
  window.setBeneficiarioValor = function (nombre) {
    const n = (nombre || '').trim();
    if (!n) return;

    const sel = $('beneficiario_ctrl');
    const inp = $('beneficiario_ctrl_input');

    if (!sel || !inp) {
      window.__benePendiente = n;
      return;
    }

    if (!sel.hidden) {
      const opt = Array.from(sel.options).find(o => o.value === n);

      if (opt) {
        sel.value = n;
        toggleExtras();
        reflejarBeneficiarioEnContrato(n);
        return;
      }

      // Forzar OTRO si no existe la opción
      if (!Array.from(sel.options).some(o => o.value === OTRO_VALUE)) {
        const optOtro = new Option('Otro (capturar)…', OTRO_VALUE);
        optOtro.dataset.fuente = 'OTRO';
        sel.append(optOtro);
      }

      sel.value = OTRO_VALUE;
      toggleExtras();

      if ($('bene_extra_nombre')) {
        $('bene_extra_nombre').value = n;
      }

      reflejarBeneficiarioEnContrato(n);
    } else {
      // Fallback input manual
      sel.hidden = false;
      inp.hidden = true;

      reflejarBeneficiarioEnContrato(n);
    }
  };

  // Prefija beneficiario proveniente de BD. Solo muestra extras si es OTRO/MANUAL o hay datos extra.
  window.prefijarBeneficiarioDesdeBD = function ({
    fuente = null,
    nombre = '',
    parentesco = '',
    celular = '',
    email = ''
  } = {}) {
    const sel = $('beneficiario_ctrl');
    const inp = $('beneficiario_ctrl_input');

    if (!sel || !inp) {
      window.__benePrefPendiente = {
        fuente,
        nombre,
        parentesco,
        celular,
        email
      };
      return;
    }

    const fuenteNorm = String(fuente || '').trim().toUpperCase();

    const debeForzarOtro =
      fuenteNorm === 'OTRO' ||
      fuenteNorm === 'MANUAL' ||
      !!parentesco ||
      !!celular ||
      !!email;

    if (debeForzarOtro) {
      // Asegurar opción OTRO
      if (!Array.from(sel.options).some(o => o.value === OTRO_VALUE)) {
        const optOtro = new Option('Otro (capturar)…', OTRO_VALUE);
        optOtro.dataset.fuente = 'OTRO';
        sel.append(optOtro);
      }

      sel.hidden = false;
      inp.hidden = true;
      sel.value = OTRO_VALUE;

      toggleExtras();

      if ($('bene_extra_nombre')) {
        $('bene_extra_nombre').value = nombre || '';
      }

      if ($('bene_extra_parentesco')) {
        $('bene_extra_parentesco').value = parentesco || '';
      }

      if ($('bene_extra_cel')) {
        $('bene_extra_cel').value = celular || '';
      }

      if ($('bene_extra_mail')) {
        $('bene_extra_mail').value = email || '';
      }

      reflejarBeneficiarioEnContrato(nombre);
    } else if (nombre) {
      // Referencia / codeudor -> no mostrar extras
      sel.hidden = false;
      inp.hidden = true;

      const opt = Array.from(sel.options).find(o => o.value === nombre);

      if (opt) {
        sel.value = opt.value;
      }

      toggleExtras();
      reflejarBeneficiarioEnContrato(nombre);
    }
  };

  // Devuelve selección actual, incluye extras si OTRO
  window.getBeneficiarioSeleccion = function () {
    const sel = $('beneficiario_ctrl');
    const inp = $('beneficiario_ctrl_input');

    const nombreExtra = $('bene_extra_nombre')?.value?.trim() || '';
    const parExtra    = $('bene_extra_parentesco')?.value || '';
    const celExtra    = $('bene_extra_cel')?.value?.trim() || '';
    const mailExtra   = $('bene_extra_mail')?.value?.trim() || '';

    if (!sel || !inp) {
      return {
        valor: '',
        fuente: null,
        numero: null,
        extra: null
      };
    }

    if (!sel.hidden) {
      const opt = sel.options[sel.selectedIndex];
      const fuente = opt?.dataset?.fuente || null;
      const numero = opt?.dataset?.numero ? Number(opt.dataset.numero) : null;

      if (sel.value === OTRO_VALUE) {
        return {
          valor: nombreExtra,
          fuente: 'OTRO',
          numero: null,
          extra: {
            nombre: nombreExtra,
            parentesco: parExtra,
            celular: celExtra,
            email: mailExtra
          }
        };
      }

      return {
        valor: sel.value || '',
        fuente,
        numero,
        extra: null
      };
    }

    return {
      valor: (inp.value || '').trim(),
      fuente: 'MANUAL',
      numero: null,
      extra: null
    };
  };

  window.getBeneficiarioValor = function () {
    return (window.getBeneficiarioSeleccion()?.valor || '').trim();
  };

  // --- UI principal: prepara el campo y la lista ---
  function poblarCampoUnico(lista) {
    const sel = $('beneficiario_ctrl');
    const inp = $('beneficiario_ctrl_input');

    if (!sel || !inp) return;

    const nombreExtra = $('bene_extra_nombre');
    const parentescoExtra = $('bene_extra_parentesco');
    const celExtra = $('bene_extra_cel');
    const mailExtra = $('bene_extra_mail');

    const prev = window.getBeneficiarioValor();

    sel.onchange = () => {
      toggleExtras();

      const s = window.getBeneficiarioSeleccion();
      reflejarBeneficiarioEnContrato(s.valor);
    };

    inp.oninput = () => {
      const s = window.getBeneficiarioSeleccion();
      reflejarBeneficiarioEnContrato(s.valor);
    };

    // Siempre usamos select con opción OTRO
    sel.hidden = false;
    inp.hidden = true;
    sel.innerHTML = '';

    sel.append(new Option('Seleccione beneficiario…', ''));

    if (Array.isArray(lista) && lista.length) {
      lista.forEach((b) => {
        const nombre = (b.nombre || '').trim();
        if (!nombre) return;

        const txt = b.parentesco ? `${nombre} — ${b.parentesco}` : nombre;
        const opt = new Option(txt, nombre);

        if (b.fuente) {
          opt.dataset.fuente = b.fuente;
        }

        if (b.numero != null) {
          opt.dataset.numero = String(b.numero);
        }

        sel.append(opt);
      });
    }

    // Opción OTRO siempre presente
    const optOtro = new Option('Otro (capturar)…', OTRO_VALUE);
    optOtro.dataset.fuente = 'OTRO';
    sel.append(optOtro);

    // Intentar restaurar valor previo
    if (prev) {
      const match = Array.from(sel.options).find(o => o.value === prev);

      if (match) {
        sel.value = prev;
      }
    }

    // Reflejo inicial
    reflejarBeneficiarioEnContrato(window.getBeneficiarioSeleccion().valor);

    // Si escriben en OTRO, reflejar al vuelo
    [nombreExtra, parentescoExtra, celExtra, mailExtra].forEach(el => {
      if (!el) return;

      el.addEventListener('input', () => {
        if (sel.value !== OTRO_VALUE) return;

        reflejarBeneficiarioEnContrato((nombreExtra?.value || '').trim());
      });
    });

    // Aplicar pendientes si cargarFirmas() se adelantó
    if (window.__benePendiente) {
      window.setBeneficiarioValor(window.__benePendiente);
      window.__benePendiente = '';
    }

    if (window.__benePrefPendiente) {
      const p = window.__benePrefPendiente;
      window.__benePrefPendiente = null;
      window.prefijarBeneficiarioDesdeBD(p);
    }

    toggleExtras();
  }

  async function cargarBeneficiarios() {
    const sid = getSolicId();

    try {
      const lista = sid ? await fetchBeneficiarios(sid) : [];
      poblarCampoUnico(lista);
    } catch (e) {
      console.warn('[beneficiarios] usando OTRO por fallback:', e?.message || e);
      poblarCampoUnico([]);
    }
  }

  document.addEventListener('DOMContentLoaded', cargarBeneficiarios);
})();

(function () {
  const OTRO_LITERAL = 'OTRO';
  const DYN_OPT_ID = '__opt_otro_dyn__';

  const $ = (id) => document.getElementById(id);

  // Catálogo amplio. Se agregan solo los que falten.
  const PARENTESCOS = [
    'ESPOSA/O',
    'CONCUBINA/O',
    'PAREJA',
    'PADRE/MADRE',
    'HIJO/A',
    'HERMANO/A',
    'ABUELO/A',
    'NIETO/A',
    'TIO/TIA',
    'SOBRINO/A',
    'PRIMO/A',
    'SUEGRO/A',
    'YERNO/NUERA',
    'CUNADO/A',
    'PADRASTRO/MADRASTRA',
    'HIJASTRO/A',
    'TUTOR/A',
    'AMIGO/A',
    'VECINO/A',
    'COMPANERO/A DE TRABAJO',
    'SIN PARENTESCO',
    'OTRO',
  ];

  function ensureParentescoOptions() {
    const sel = $('bene_extra_parentesco');
    if (!sel) return;

    const existing = new Set(
      Array.from(sel.options).map(o => (o.value || '').toUpperCase())
    );

    PARENTESCOS.forEach(v => {
      const val = String(v).toUpperCase();

      if (!existing.has(val)) {
        sel.append(new Option(v, v));
        existing.add(val);
      }
    });
  }

  function showOtroWrap(show) {
    const wrap = $('bene_extra_parentesco_otro_wrap');
    const inp  = $('bene_extra_parentesco_otro');

    if (!wrap) return;

    wrap.style.display = show ? '' : 'none';

    if (show) {
      inp?.focus();
    } else if (inp) {
      inp.value = '';
    }
  }

  function upsertDynamicOptionFromInput() {
    const sel = $('bene_extra_parentesco');
    const inp = $('bene_extra_parentesco_otro');

    if (!sel || !inp) return;

    const txt = (inp.value || '').trim();

    // Si no hay texto, mantenemos seleccionado OTRO y mostramos el input
    if (!txt) {
      const old = sel.querySelector(`option[data-dyn="${DYN_OPT_ID}"]`);

      if (old) {
        old.remove();
      }

      sel.value = OTRO_LITERAL;
      return;
    }

    // Crea/actualiza una opción dinámica con el texto y selecciónala
    let dyn = sel.querySelector(`option[data-dyn="${DYN_OPT_ID}"]`);

    if (!dyn) {
      dyn = new Option(txt, txt);
      dyn.setAttribute('data-dyn', DYN_OPT_ID);

      const otroOpt = Array.from(sel.options).find(o =>
        (o.value || '').toUpperCase() === OTRO_LITERAL
      );

      if (otroOpt) {
        sel.insertBefore(dyn, otroOpt);
      } else {
        sel.appendChild(dyn);
      }
    } else {
      dyn.textContent = txt;
      dyn.value = txt;
    }

    sel.value = txt;
  }

  function onParentescoChange() {
    const sel = $('bene_extra_parentesco');
    if (!sel) return;

    const isOtro = (sel.value || '').toUpperCase() === OTRO_LITERAL;
    showOtroWrap(isOtro);

    if (!isOtro) {
      const inp = $('bene_extra_parentesco_otro');

      if (inp) {
        inp.value = '';
      }

      const old = sel.querySelector(`option[data-dyn="${DYN_OPT_ID}"]`);

      if (old) {
        old.remove();
      }
    }
  }

  function initParentescoUI() {
    const sel = $('bene_extra_parentesco');
    const inp = $('bene_extra_parentesco_otro');

    if (!sel) return;

    // 1. Asegura catálogo
    ensureParentescoOptions();

    // 2. Eventos
    sel.addEventListener('change', onParentescoChange);

    if (inp) {
      inp.addEventListener('input', upsertDynamicOptionFromInput);
    }

    // 3. Estado inicial
    onParentescoChange();

    // 4. Si ya hay texto al cargar, crea la opción dinámica
    if (inp && inp.value && (sel.value || '').toUpperCase() === OTRO_LITERAL) {
      upsertDynamicOptionFromInput();
    }
  }

  window.upsertDynamicOptionFromInput = upsertDynamicOptionFromInput;

  document.addEventListener('DOMContentLoaded', initParentescoUI);
})();