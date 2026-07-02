# parse_periodos.py
import sys, re, json
from datetime import datetime, timedelta, date

# ----- Forzar UTF-8 (Windows-friendly) -----
try:
    sys.stdout.reconfigure(encoding="utf-8")
    sys.stderr.reconfigure(encoding="utf-8")
except Exception:
    pass


def jprint(obj):
    print(json.dumps(obj, ensure_ascii=True), flush=True)


def ddmmyyyy_to_date(s: str) -> date:
    return datetime.strptime(s, "%d/%m/%Y").date()


def date_to_ddmmyyyy(d: date) -> str:
    return d.strftime("%d/%m/%Y")


def days_inclusive(a: date, b: date) -> int:
    return (b - a).days + 1


def norm_text(t: str) -> str:
    if not t:
        return ""
    t = t.replace("\u00A0", " ").replace("\u2007", " ").replace("\u202F", " ")
    t = re.sub(r"[ \t]+", " ", t)
    return t


def extract_fecha_emision_reporte(text: str) -> dict:
    out = {
        "fecha_emision": "",
        "fecha_emision_iso": "",
        "dia": "",
        "mes": "",
        "anio": ""
    }

    if not text:
        return out

    t = norm_text(text)

    patrones = [
        # Fecha de emisión del reporte 10 / 10 / 2025
        r"Fecha\s+de\s+emisi[oó]n\s+del\s+reporte[\s\S]{0,120}?(\d{1,2})\s*/\s*(\d{1,2})\s*/\s*(\d{4})",

        # Fecha de emisión del reporte 10 10 2025
        r"Fecha\s+de\s+emisi[oó]n\s+del\s+reporte[\s\S]{0,120}?(\d{1,2})\s+(\d{1,2})\s+(\d{4})",

        # Fecha de emisión del reporte 10-10-2025
        r"Fecha\s+de\s+emisi[oó]n\s+del\s+reporte[\s\S]{0,120}?(\d{1,2})\s*-\s*(\d{1,2})\s*-\s*(\d{4})",
    ]

    for patron in patrones:
        m = re.search(patron, t, re.IGNORECASE)
        if m:
            dia = int(m.group(1))
            mes = int(m.group(2))
            anio = int(m.group(3))

            try:
                fecha = date(anio, mes, dia)
                out["fecha_emision"] = fecha.strftime("%d/%m/%Y")
                out["fecha_emision_iso"] = fecha.strftime("%Y-%m-%d")
                out["dia"] = f"{dia:02d}"
                out["mes"] = f"{mes:02d}"
                out["anio"] = str(anio)
                return out
            except ValueError:
                continue

    return out




def clean_nombre(nombre: str) -> str:
    if not nombre:
        return ""

    n = re.sub(r"\s+", " ", nombre).strip()

    # Quita placeholders tipo: "DD MM YYYY", "DD/MM/YYYY", "DD-MM-YYYY"
    n = re.sub(r"\bDD\b\s*[/\-]?\s*\bMM\b\s*[/\-]?\s*\bYYYY\b", "", n, flags=re.IGNORECASE).strip()
    n = re.sub(r"\bDD\b\s+\bMM\b\s+\bAAAA\b", "", n, flags=re.IGNORECASE).strip()

    # OCR raro: "D D M M Y Y Y Y"
    n = re.sub(r"\bD\s*D\s*M\s*M\s*Y\s*Y\s*Y\s*Y\b", "", n, flags=re.IGNORECASE).strip()

    # Si se pegó una fecha REAL al final
    n = re.sub(r"\b\d{2}[/-]\d{2}[/-]\d{4}\b$", "", n).strip()
    n = re.sub(r"\b\d{2}\s+\d{2}\s+\d{4}\b$", "", n).strip()

    n = re.sub(r"\s+", " ", n).strip()
    return n


# =========================
# Persona + semanas
# =========================
def extract_persona_and_weeks(text: str) -> dict:
    out = {
        "curp": "",
        "nss": "",
        "nombre": "",
        "semanas_totales": "",
        "semanas_imss": "",
        "semanas_descontadas": "",
        "semanas_reintegradas": "",
    }

    if not text:
        return out

    t = text.upper()

    # CURP
    m = re.search(r"\b([A-Z]{4}\d{6}[HM][A-Z]{5}\d{2})\b", t)
    if m:
        out["curp"] = m.group(1)

    # NSS (tolerante)
    m = re.search(r"\bNSS\b[^0-9]{0,30}([0-9][0-9\-\s]{8,25}[0-9])\b", t)
    if m:
        digits = re.sub(r"\D+", "", m.group(1))
        if len(digits) >= 10:
            out["nss"] = digits
    else:
        m = re.search(r"\b(\d{11})\b", t)
        if m:
            out["nss"] = m.group(1)

    # Nombre
    m = re.search(r"ESTIMAD[OA]\(A\),?\s*[\r\n]+\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{5,120})", t)
    nombre = ""
    if m:
        nombre = m.group(1)
    else:
        m2 = re.search(r"\n\s*([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]{5,120})\s*\n\s*NSS\b", t)
        if m2:
            nombre = m2.group(1)

    nombre = re.sub(r"\s+", " ", (nombre or "")).strip()
    out["nombre"] = clean_nombre(nombre)

    # Total semanas
    m = re.search(r"TOTAL\s+DE\s+SEMANAS\s+COTIZADAS[\s\S]{0,120}?(\d{1,6})", t)
    if m:
        out["semanas_totales"] = m.group(1)

    # Detalle semanas
    sec = re.search(r"TU\s+DETALLE\s+DE\s+SEMANAS\s+COTIZADAS([\s\S]{0,1500})", t)
    if sec:
        chunk = sec.group(1)
        pos = chunk.find("SEMANAS COTIZADAS IMSS")
        if pos != -1:
            chunk = chunk[pos:pos + 900]

        nums = []
        for s in re.findall(r"(?<!\d)(\d{1,6})(?!\d)", chunk):
            try:
                n = int(s)
                if 0 <= n <= 6000:
                    nums.append(n)
            except Exception:
                pass

        if len(nums) >= 3:
            out["semanas_imss"] = str(nums[0])
            out["semanas_descontadas"] = str(nums[1])
            out["semanas_reintegradas"] = str(nums[2])
        else:
            def pick(label, stop1, stop2):
                rgx = re.compile(
                    rf"{label}(?:(?!{stop1}|{stop2}).){{0,250}}?(\d{{1,6}})",
                    re.DOTALL
                )
                m2 = rgx.search(chunk)
                if not m2:
                    return ""
                v = m2.group(1)
                try:
                    n = int(v)
                    return str(n) if 0 <= n <= 6000 else ""
                except Exception:
                    return ""

            out["semanas_imss"] = out["semanas_imss"] or pick(
                r"SEMANAS\s+COTIZADAS\s+IMSS",
                r"SEMANAS\s+DESCONTADAS",
                r"SEMANAS\s+REINTEGRADAS"
            )
            out["semanas_descontadas"] = out["semanas_descontadas"] or pick(
                r"SEMANAS\s+DESCONTADAS",
                r"SEMANAS\s+COTIZADAS\s+IMSS",
                r"SEMANAS\s+REINTEGRADAS"
            )
            out["semanas_reintegradas"] = out["semanas_reintegradas"] or pick(
                r"SEMANAS\s+REINTEGRADAS",
                r"SEMANAS\s+COTIZADAS\s+IMSS",
                r"SEMANAS\s+DESCONTADAS"
            )

    return out


# =========================
# Identificación simple
# =========================
def _clean_line(s: str) -> str:
    return re.sub(r"\s+", " ", (s or "").strip())


def extraer_identificacion(full_text: str) -> dict:
    t = full_text or ""
    tn = t.replace("\r", "\n")

    m_curp = re.search(r"\b([A-Z]{4}\d{6}[HM][A-Z]{5}[A-Z0-9]{2})\b", t.upper())
    curp = m_curp.group(1) if m_curp else ""

    m_nss = re.search(r"\bNSS\b\s*[:\-]?\s*([0-9][0-9\s]{9,20})", tn, re.IGNORECASE)
    nss = ""
    if m_nss:
        nss = re.sub(r"\D", "", m_nss.group(1))
        if len(nss) != 11:
            m11 = re.search(r"\b(\d{11})\b", tn)
            nss = m11.group(1) if m11 else nss

    nombre = ""
    m_est = re.search(r"Estimado\(a\)\s*,\s*\n\s*([^\n]+)", tn, re.IGNORECASE)
    if m_est:
        nombre = _clean_line(m_est.group(1))
        nombre = clean_nombre(nombre)
    else:
        m_nom = re.search(r"\bNombre\b\s*[:\-]?\s*([^\n]+)", tn, re.IGNORECASE)
        if m_nom:
            nombre = _clean_line(m_nom.group(1))
            nombre = clean_nombre(nombre)

    total_semanas = ""
    m_sem = re.search(r"Total\s+de\s+semanas\s+cotizadas\s*[:\s]*\n?\s*(\d{1,6})", tn, re.IGNORECASE)
    if m_sem:
        total_semanas = m_sem.group(1)
    else:
        m_sem2 = re.search(r"Total\s+de\s+semanas\s+cotizadas[\s\S]{0,100}?(\d{1,6})", tn, re.IGNORECASE)
        total_semanas = m_sem2.group(1) if m_sem2 else ""

    return {
        "curp": curp,
        "nss": nss,
        "nombre": nombre,
        "total_semanas": total_semanas
    }


def parse_salary_str(s: str):
    if s is None:
        return None
    s = str(s).strip().replace(" ", "").replace("$", "")
    if s == "":
        return None

    # 1234,56 -> 1234.56
    if s.count(",") == 1 and s.count(".") == 0:
        s = s.replace(",", ".")
    else:
        # 1,234.56 -> 1234.56
        s = s.replace(",", "")

    try:
        return float(s)
    except Exception:
        return None


def extract_text_pdfplumber(pdf_path):
    import pdfplumber
    parts = []
    with pdfplumber.open(pdf_path) as pdf:
        for p in pdf.pages:
            parts.append(p.extract_text() or "")
    return "\n".join(parts).strip()


def extract_text_pymupdf(pdf_path):
    import fitz  # PyMuPDF
    doc = fitz.open(pdf_path)
    parts = []
    for page in doc:
        parts.append(page.get_text("text") or "")
    return "\n".join(parts).strip()


def ocr_text(pdf_path):
    from pdf2image import convert_from_path
    import pytesseract
    images = convert_from_path(pdf_path, dpi=250)
    out = []
    for img in images:
        out.append(pytesseract.image_to_string(img, lang="spa"))
    return "\n".join(out).strip()


# -------------------------
# Partir el texto por bloques de patrón
# -------------------------
def split_employer_blocks(text: str):
    t = text
    rgx = re.compile(r"Nombre\s+del\s+patr[oó]n", re.IGNORECASE)
    idxs = [m.start() for m in rgx.finditer(t)]
    if not idxs:
        return [t]

    blocks = []
    for i, st in enumerate(idxs):
        ed = idxs[i + 1] if i + 1 < len(idxs) else len(t)
        blocks.append(t[st:ed])
    return blocks


# -------------------------
# Parse Alta/Baja/SBC
# -------------------------
def parse_ab_block(block: str):
    b = norm_text(block)

    rgx = re.compile(
        r"Fecha de alta\s*(?P<alta>\d{2}/\d{2}/\d{4}).{0,600}?"
        r"Fecha de baja\s*(?P<baja>Vigente|\d{2}/\d{2}/\d{4}).{0,900}?"
        r"Salario Base de Cotizaci[oó]n\s*\*?.{0,140}?\$\s*(?P<sal>[0-9]+(?:[.,][0-9]+)?)",
        re.IGNORECASE | re.DOTALL
    )

    m = rgx.search(b)
    if not m:
        return None

    ini = ddmmyyyy_to_date(m.group("alta"))
    baja = m.group("baja").strip()
    es_vigente = baja.lower() == "vigente"
    fin = datetime.now().date() if baja.lower() == "vigente" else ddmmyyyy_to_date(baja)
    sal = parse_salary_str(m.group("sal"))

    ctx = b[max(0, m.start() - 140):min(len(b), m.end() + 140)].lower()
    if "uma" in ctx or "tope" in ctx:
        sal = None

    return {
    "inicio": ini,
    "fin": fin,
    "salario": sal,
    "vigente": es_vigente
}


# -------------------------
# Parse Movimientos dentro de un bloque
# -------------------------
def parse_movs_block(block: str):
    b = norm_text(block)
    SAL = r"(?:\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{1,2})|\d+(?:[.,]\d{1,2})?)"

    rgx = re.compile(
        rf"\b(?P<tipo>BAJA|REINGRESO|MODIFICACION\s+DE\s+SALARIO)\b\s*"
        rf"(?P<fecha>\d{{2}}/\d{{2}}/\d{{4}})"
        rf"(?:\s*\$?\s*(?P<salario>{SAL}))?",
        re.IGNORECASE
    )

    movs = []
    for m in rgx.finditer(b):
        tipo = re.sub(r"\s+", " ", m.group("tipo").upper()).strip()
        fecha = m.group("fecha")
        salario = parse_salary_str(m.group("salario")) if m.group("salario") else None
        movs.append({"fecha": fecha, "tipo": tipo, "salario": salario})

    movs.sort(key=lambda x: ddmmyyyy_to_date(x["fecha"]))
    return movs


# -------------------------
# Construir sub-intervalos por patrón usando cambios
# -------------------------
def build_salary_intervals_for_block(ab, movs):
    if not ab:
        return []

    ini = ab["inicio"]
    fin = ab["fin"]
    base_sal = ab.get("salario", None)

    changes = {}
    if base_sal is not None:
        changes[ini] = base_sal

    for mv in movs:
        f = ddmmyyyy_to_date(mv["fecha"])
        if f < ini or f > fin:
            continue
        t = mv["tipo"]
        s = mv.get("salario")
        if t in ("MODIFICACION DE SALARIO", "REINGRESO") and s is not None:
            changes[f] = float(s)

    if changes:
        first_date = min(changes.keys())
        first_sal = changes[first_date]
        if first_date != ini:
            changes[ini] = first_sal

    if not changes:
        return [{
    "inicio": ini,
    "fin": fin,
    "salario": None,
    "vigente": ab.get("vigente", False)
}]

    starts = sorted(changes.keys())
    intervals = []
    for i, sdt in enumerate(starts):
        edt = (starts[i + 1] - timedelta(days=1)) if i + 1 < len(starts) else fin
        if edt < sdt:
            continue
        if sdt < ini:
            sdt = ini
        if edt > fin:
            edt = fin
        if edt < sdt:
            continue
        intervals.append({
    "inicio": sdt,
    "fin": edt,
    "salario": changes[sdt],
    "vigente": ab.get("vigente", False)
})

    return intervals


def fallback_intervals_from_movs(movs):
    intervals = []
    active = False
    cur_start = None
    cur_sal = None
    hoy = datetime.now().date()

    for mv in movs:
        f = ddmmyyyy_to_date(mv["fecha"])
        t = mv["tipo"]
        s = mv.get("salario")

        if t == "REINGRESO":
            if active and cur_start and f > cur_start:
                intervals.append({"inicio": cur_start, "fin": f - timedelta(days=1), "salario": cur_sal})
            active = True
            cur_start = f
            if s is not None:
                cur_sal = s

        elif t == "MODIFICACION DE SALARIO":
            if active and cur_start:
                if f > cur_start:
                    intervals.append({"inicio": cur_start, "fin": f - timedelta(days=1), "salario": cur_sal})
                cur_start = f
                if s is not None:
                    cur_sal = s

        elif t == "BAJA":
            if active and cur_start and f >= cur_start:
                if s is not None and cur_sal is None:
                    cur_sal = s
                intervals.append({"inicio": cur_start, "fin": f, "salario": cur_sal})
            active = False
            cur_start = None
            cur_sal = None

    if active and cur_start and hoy >= cur_start:
            intervals.append({
        "inicio": cur_start,
        "fin": hoy,
        "salario": cur_sal,
        "vigente": True
    })

    return intervals


# -------------------------
# Segmentación (sin duplicar días) + suma de salarios concurrentes por patrón
# -------------------------
def segmentar_intervalos_sum(intervalos):
    if not intervalos:
        return []

    boundaries = set()
    for it in intervalos:
        boundaries.add(it["inicio"])
        boundaries.add(it["fin"] + timedelta(days=1))
    boundaries = sorted(boundaries)

    segs = []
    for i in range(len(boundaries) - 1):
        seg_ini = boundaries[i]
        seg_fin = boundaries[i + 1] - timedelta(days=1)
        if seg_fin < seg_ini:
            continue

        activos = [it for it in intervalos if it["inicio"] <= seg_ini <= it["fin"]]
        if not activos:
            continue

        def key(it):
            sal = it["salario"] if it.get("salario") is not None else -1e18
            return (it["inicio"], sal)

        ganador = max(activos, key=key)
        salario_base = ganador.get("salario")

        # ===== AQUÍ ESTÁ LA CORRECCIÓN =====
        # Sumar salarios concurrentes por patrón (src), aunque sean iguales
        sal_by_src = {}
        for a in activos:
            if a.get("salario") is None:
                continue
            sid = a.get("src", "unknown")
            v = round(float(a["salario"]), 2)
            # Si por alguna razón un mismo src aporta más de un intervalo activo aquí, usamos el mayor
            if sid not in sal_by_src:
                sal_by_src[sid] = v
            else:
                sal_by_src[sid] = max(sal_by_src[sid], v)

        salarios = list(sal_by_src.values())
        salarios.sort()

        salario_display = " + ".join([("{:g}".format(s)) for s in salarios]) if salarios else ""
        salario_suma = float(sum(salarios)) if salarios else 0.0

        segs.append({
            "inicio": seg_ini,
            "fin": seg_fin,
            "salario_base": salario_base,
            "salarios": salarios,              # ahora son salarios por patrón
            "salario_display": salario_display,
            "salario_suma": salario_suma,
            "vigente": any(a.get("vigente", False) for a in activos)
        })

    merged = []
    for s in segs:
        if not merged:
            merged.append(s)
            continue
        last = merged[-1]
        if (last["salario_display"] == s["salario_display"]
                and last.get("salario_base") == s.get("salario_base")
                 and last.get("vigente", False) == s.get("vigente", False)
                and last["fin"] + timedelta(days=1) == s["inicio"]):
            last["fin"] = s["fin"]
        else:
            merged.append(s)

    return merged



def split_vigentes_por_fecha_emision(segmentos, fecha_emision):
    if not segmentos or not fecha_emision:
        return segmentos

    out = []

    for s in segmentos:
        ini = s["inicio"]
        fin = s["fin"]
        vigente = bool(s.get("vigente", False))

        # Solo divide si es vigente y cruza la fecha de emisión
        if vigente and ini <= fecha_emision < fin:
            s1 = dict(s)
            s1["inicio"] = ini
            s1["fin"] = fecha_emision
            s1["vigente_actual"] = False
            s1["hasta_label"] = date_to_ddmmyyyy(fecha_emision)

            s2 = dict(s)
            s2["inicio"] = fecha_emision + timedelta(days=1)
            s2["fin"] = fin
            s2["vigente_actual"] = True
            s2["hasta_label"] = "Actual"

            out.append(s1)
            out.append(s2)
        else:
            sx = dict(s)
            sx["vigente_actual"] = vigente and fin > fecha_emision
            sx["hasta_label"] = "Actual" if sx["vigente_actual"] else date_to_ddmmyyyy(fin)
            out.append(sx)

    return out


def tramos_hasta_1750(segmentos):
    if not segmentos:
        return [], 0

    seg_desc = sorted(segmentos, key=lambda x: x["fin"], reverse=True)
    objetivo = 1750
    acumulado = 0
    tramos = []

    for s in seg_desc:
        if acumulado >= objetivo:
            break

        d = days_inclusive(s["inicio"], s["fin"])
        restante = objetivo - acumulado

        if d <= restante:
            acumulado += d
            tramos.append({
                "desde": date_to_ddmmyyyy(s["inicio"]),
                "hasta": date_to_ddmmyyyy(s["fin"]),
                "hasta_label": s.get("hasta_label", date_to_ddmmyyyy(s["fin"])),
"vigente_actual": bool(s.get("vigente_actual", False)),
                "dias": d,
                "acumulado": acumulado,
                "salario": s.get("salario_base"),
                "salario_display": s.get("salario_display", ""),
                "salario_suma": s.get("salario_suma", 0.0),
            })
        else:
            new_ini = s["fin"] - timedelta(days=restante - 1)
            acumulado += restante
            tramos.append({
                "desde": date_to_ddmmyyyy(new_ini),
                "hasta": date_to_ddmmyyyy(s["fin"]),
                "hasta_label": s.get("hasta_label", date_to_ddmmyyyy(s["fin"])),
"vigente_actual": bool(s.get("vigente_actual", False)),
                "dias": restante,
                "acumulado": acumulado,
                "salario": s.get("salario_base"),
                "salario_display": s.get("salario_display", ""),
                "salario_suma": s.get("salario_suma", 0.0),
            })
            break

    return tramos, acumulado


def main():
    pdf_path = sys.argv[1] if len(sys.argv) > 1 else None
    if not pdf_path:
        return jprint({"error": "Falta ruta del PDF"})

    warning = None
    debug = {"metodo": None, "text_len": 0, "preview": ""}

    try:
        text = ""
        try:
            text = extract_text_pdfplumber(pdf_path)
            debug["metodo"] = "pdfplumber"
        except Exception:
            text = ""

        if not text:
            try:
                text = extract_text_pymupdf(pdf_path)
                debug["metodo"] = "pymupdf"
            except Exception:
                text = ""

        text = norm_text(text)
        debug["text_len"] = len(text)
        debug["preview"] = text[:1200]

        if len(text.strip()) < 30:
            warning = "Texto casi vacío. Se forzó OCR."
            text = norm_text(ocr_text(pdf_path))
            debug["metodo"] = (debug["metodo"] or "normal") + " + OCR"
            debug["text_len"] = len(text)
            debug["preview"] = text[:1200]

        identificacion = extraer_identificacion(text)
        persona = extract_persona_and_weeks(text)
        fecha_emision_reporte = extract_fecha_emision_reporte(text)

        blocks = split_employer_blocks(text)

        all_movs = []
        alta_baja_out = []
        intervals = []

        # ===== AQUÍ ESTÁ LA CORRECCIÓN: src por bloque =====
        for bi, block in enumerate(blocks):
            src = f"block_{bi}"

            ab = parse_ab_block(block)
            movs = parse_movs_block(block)

            all_movs.extend(movs)

            if ab:
                alta_baja_out.append({
                    "inicio": date_to_ddmmyyyy(ab["inicio"]),
                    "fin": date_to_ddmmyyyy(ab["fin"]),
                    "salario": ab.get("salario"),
                })

                for it in build_salary_intervals_for_block(ab, movs):
                    it["src"] = src
                    intervals.append(it)

            else:
                if movs:
                    for it in fallback_intervals_from_movs(movs):
                        it["src"] = src
                        intervals.append(it)

        # ===== AQUÍ ESTÁ LA CORRECCIÓN: deduplicado por src =====
        seen = set()
        uniq_int = []
        for it in intervals:
            k = (
                it.get("src", "unknown"),
                it["inicio"],
                it["fin"],
                round(it["salario"], 2) if it.get("salario") is not None else None
            )
            if k in seen:
                continue
            seen.add(k)
            uniq_int.append(it)

        segmentos = segmentar_intervalos_sum(uniq_int)

        fecha_emision_reporte = extract_fecha_emision_reporte(text)
        fecha_emision_dt = None

        if fecha_emision_reporte.get("fecha_emision"):
            try:
                fecha_emision_dt = ddmmyyyy_to_date(fecha_emision_reporte["fecha_emision"])
            except Exception:
                fecha_emision_dt = None

        segmentos = split_vigentes_por_fecha_emision(segmentos, fecha_emision_dt)

        periodos_out = [{
            "desde": date_to_ddmmyyyy(s["inicio"]),
            "hasta": date_to_ddmmyyyy(s["fin"]),
 "hasta_label": s.get("hasta_label", date_to_ddmmyyyy(s["fin"])),
    "vigente_actual": bool(s.get("vigente_actual", False)),
            "dias": days_inclusive(s["inicio"], s["fin"]),
            "salario": s.get("salario_base"),
            "salario_display": s.get("salario_display", ""),
            "salarios_activos": s.get("salarios", []),
            "salario_suma": s.get("salario_suma", 0.0),
        } for s in segmentos]

        tramos, usados = tramos_hasta_1750(segmentos)
        dias_unicos = sum(p["dias"] for p in periodos_out)
        faltan = max(0, 1750 - usados)

        return jprint({
            "warning": warning,
            "debug": debug,
             "fecha_emision_reporte": fecha_emision_reporte,
            "persona": persona,
            "identificacion": identificacion,
            "movimientos": all_movs,
            "alta_baja": alta_baja_out,
            "periodos_sin_empalme": periodos_out,
            "tramos_1750": tramos,
            "totales": {
                "dias_unicos": dias_unicos,
                "dias_usados": usados,
                "faltan_para_1750": faltan
            },
        })

    except Exception as e:
        return jprint({"error": str(e), "debug": debug})


if __name__ == "__main__":
    main()
