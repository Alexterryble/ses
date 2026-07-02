#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import sys
import os
import re
import json
import traceback
import shutil

try:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    sys.stderr.reconfigure(encoding="utf-8", errors="replace")
except Exception:
    pass


def safe_text(value):
    if value is None:
        return ""
    return str(value).replace("\ufffd", " ").replace("\x00", " ")


def print_json(obj):
    output = json.dumps(obj, ensure_ascii=False, indent=2)
    output = safe_text(output)
    sys.stdout.write(output + "\n")


def clean_spaces(text):
    text = safe_text(text)
    text = text.replace("\r", "\n")
    text = re.sub(r"[ \t]+", " ", text)
    text = re.sub(r"\n{2,}", "\n", text)
    return text.strip()


def clean_line(line):
    return re.sub(r"\s+", " ", safe_text(line)).strip()


def parse_amount(raw):
    if raw is None:
        return None

    val = str(raw).strip()
    val = val.replace("$", "").replace("¢", "").replace("§", "").replace("S", "")
    val = val.replace(" ", "")

    if "," in val and "." in val:
        val = val.replace(",", "")
    elif "," in val and "." not in val:
        val = val.replace(".", "").replace(",", ".")
    else:
        val = val.replace(",", "")

    try:
        return float(val)
    except Exception:
        return None


def find_money_amounts(text):
    pattern = r"""
        [\$¢§S]?\s*
        (?:
            \d{1,3}(?:,\d{3})+\.\d{2}
            |
            \d+\.\d{2}
        )
    """
    matches = re.findall(pattern, text or "", flags=re.VERBOSE)
    return [m.strip() for m in matches if m and m.strip()]


def has_useful_keywords(text):
    if not text:
        return False

    up = text.upper()
    checks = [
        "IMSS 1997",
        "SAR IMSS 1992",
        "SAR INFONAVIT 1992",
        "INFONAVIT 1997",
        "DETALLE DEL SALDO FINAL",
        "DETALLE DE TU CUENTA DE AFORE",
        "TU AHORRO PARA EL RETIRO",
        "TU AHORRO PARA LA VIVIENDA",
        "SAR 92",
        "AHORRO PARA EL RETIRO",
        "AHORRO PARA LA VIVIENDA",
    ]
    return any(word in up for word in checks)


def is_text_too_short(text):
    if not text:
        return True

    stripped = clean_spaces(text)
    if len(stripped) < 120:
        return True

    lines = [clean_line(x) for x in stripped.split("\n") if clean_line(x)]
    return len(lines) <= 4


def should_force_ocr(text):
    if not text:
        return False

    up = text.upper()

    clues = [
        "DETALLE DE TU CUENTA DE AFORE",
        "TU CUENTA DE AFORE SE",
        "TU AHORRO PARA EL RETIRO",
        "SAR 92",
        "TU AHORRO PARA LA VIVIENDA",
        "TABLA DE COMISIONES DE LAS SIEFORES",
    ]

    return any(c in up for c in clues)


def find_tesseract_path():
    candidates = [
        r"C:\Program Files\Tesseract-OCR\tesseract.exe",
        r"C:\Program Files (x86)\Tesseract-OCR\tesseract.exe",
        os.path.expandvars(r"%LOCALAPPDATA%\Programs\Tesseract-OCR\tesseract.exe"),
    ]

    for path in candidates:
        if path and os.path.isfile(path):
            return path

    path_from_env = shutil.which("tesseract")
    if path_from_env:
        return path_from_env

    return None


def find_poppler_bin():
    candidates = [
        r"C:\poppler\Library\bin",
        r"C:\poppler\bin",
        r"C:\Program Files\poppler\Library\bin",
        r"C:\Program Files\poppler\bin",
        r"C:\Program Files (x86)\poppler\Library\bin",
        r"C:\Program Files (x86)\poppler\bin",
        r"C:\Users\INTEL\AppData\Local\Microsoft\WinGet\Packages\oschwartz10612.Poppler_Microsoft.Winget.Source_8wekyb3d8bbwe\poppler-25.07.0\Library\bin",
    ]

    for path in candidates:
        pdfinfo_exe = os.path.join(path, "pdfinfo.exe")
        if os.path.isdir(path) and os.path.isfile(pdfinfo_exe):
            return path

    pdfinfo_path = shutil.which("pdfinfo")
    if pdfinfo_path:
        return os.path.dirname(pdfinfo_path)

    return None


def extract_text_pdfplumber(pdf_path):
    try:
        import pdfplumber
        text_parts = []
        with pdfplumber.open(pdf_path) as pdf:
            for page in pdf.pages:
                txt = page.extract_text() or ""
                txt = safe_text(txt)
                if txt.strip():
                    text_parts.append(txt)
        return "\n".join(text_parts).strip()
    except Exception:
        return ""


def extract_text_pymupdf(pdf_path):
    try:
        import fitz
        doc = fitz.open(pdf_path)
        text_parts = []
        for page in doc:
            txt = page.get_text("text") or ""
            txt = safe_text(txt)
            if txt.strip():
                text_parts.append(txt)
        doc.close()
        return "\n".join(text_parts).strip()
    except Exception:
        return ""


def extract_text_ocr(pdf_path):
    info = {
        "text": "",
        "error": "",
        "tesseract_path": "",
        "poppler_path": ""
    }

    try:
        from pdf2image import convert_from_path
        import pytesseract

        tesseract_path = find_tesseract_path()
        poppler_path = find_poppler_bin()

        if tesseract_path:
            pytesseract.pytesseract.tesseract_cmd = tesseract_path

        info["tesseract_path"] = tesseract_path or ""
        info["poppler_path"] = poppler_path or ""

        if not tesseract_path:
            info["error"] = "No se encontró Tesseract instalado."
            return info

        images = convert_from_path(
            pdf_path,
            dpi=300,
            poppler_path=poppler_path if poppler_path else None
        )

        text_parts = []
        for img in images:
            txt = pytesseract.image_to_string(img, lang="spa+eng", config="--psm 6")
            txt = safe_text(txt)
            if txt.strip():
                text_parts.append(txt)

        info["text"] = "\n".join(text_parts).strip()
        return info

    except Exception as e:
        info["error"] = safe_text(str(e))
        return info


def normalize_text(text):
    text = clean_spaces(text)
    text = text.replace("|", " ")
    text = text.replace("•", " ")
    text = text.replace("·", " ")
    text = text.replace("“", '"').replace("”", '"')
    text = text.replace("’", "'").replace("‘", "'")
    text = text.replace("—", "-").replace("–", "-")

    text = re.sub(r"[,]{2,}", ",", text)
    text = re.sub(r"[;:]{2,}", " ", text)
    text = re.sub(r"\s{2,}", " ", text)

    text = text.replace("1MSS", "IMSS")
    text = text.replace("1NFONAVIT", "INFONAVIT")
    text = text.replace("1SSSTE", "ISSSTE")
    text = text.replace("SAR1MSS", "SAR IMSS")
    text = text.replace("SAR1NFONAVIT", "SAR INFONAVIT")

    text = text.replace("1 9 9 7", "1997")
    text = text.replace("1 9 9 2", "1992")

    text = re.sub(r"IMSS\s*1?\s*9\s*9\s*7", "IMSS 1997", text, flags=re.IGNORECASE)
    text = re.sub(r"SAR\s*IMSS\s*1?\s*9\s*9\s*2", "SAR IMSS 1992", text, flags=re.IGNORECASE)
    text = re.sub(r"SAR\s*INFONAVIT\s*1?\s*9\s*9\s*2", "SAR INFONAVIT 1992", text, flags=re.IGNORECASE)
    text = re.sub(r"INFONAVIT\s*1?\s*9\s*9\s*7", "INFONAVIT 1997", text, flags=re.IGNORECASE)

    text = re.sub(r"SAR\s*92", "SAR 92", text, flags=re.IGNORECASE)
    text = re.sub(r"TU\s+AHORRO\s+PARA\s+EL\s+RETIRO", "TU AHORRO PARA EL RETIRO", text, flags=re.IGNORECASE)
    text = re.sub(r"TU\s+AHORRO\s+PARA\s+LA\s+VIVIENDA", "TU AHORRO PARA LA VIVIENDA", text, flags=re.IGNORECASE)
    text = re.sub(r"DETALLE\s+DE\s+TU\s+CUENTA\s+DE\s+AFORE", "DETALLE DE TU CUENTA DE AFORE", text, flags=re.IGNORECASE)

    return text.strip()


def get_lines(text):
    return [clean_line(line) for line in text.split("\n") if clean_line(line)]


def normalize_label_text(text):
    text = (text or "").upper()
    text = text.replace("1MSS", "IMSS")
    text = text.replace("1NFONAVIT", "INFONAVIT")
    text = text.replace("1SSSTE", "ISSSTE")
    text = re.sub(r"\s+", " ", text).strip()
    return text


def extract_amount_from_same_line(line, label):
    up = normalize_label_text(line)
    label_up = normalize_label_text(label)

    if label_up not in up:
        return None

    tail = up.split(label_up, 1)[1]
    nums = re.findall(
        r'([0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2}|[0-9]+\.[0-9]{2})',
        tail
    )
    if nums:
        return parse_amount(nums[0])

    return None


def extract_block_between(text, start_marker, end_markers):
    if not text:
        return ""

    up = text.upper()
    start = up.find(start_marker.upper())
    if start == -1:
        return ""

    tail = text[start:]
    tail_up = tail.upper()

    end = len(tail)
    for marker in end_markers:
        pos = tail_up.find(marker.upper())
        if pos != -1 and pos < end:
            end = pos

    return tail[:end].strip()


def extract_money_after_label_in_text(text, label):
    if not text:
        return None

    pattern = rf"""
        (?:^|\n)\s*
        [e€¢§\-\*\•\·]?\s*
        {re.escape(label)}
        \s*
        [:$]?\s*
        ([0-9]{{1,3}}(?:,[0-9]{{3}})*\.[0-9]{{2}}|[0-9]+\.[0-9]{{2}})
    """

    m = re.search(pattern, text, flags=re.IGNORECASE | re.VERBOSE)
    if m:
        return parse_amount(m.group(1))

    return None


def extract_detail_block(text):
    if not text:
        return ""

    up = text.upper()
    start = up.find("DETALLE DE TU CUENTA DE AFORE")
    if start == -1:
        return ""

    tail = text[start:]

    cut_markers = [
        "LLÁMANOS O ESCRÍBENOS",
        "LLAMANOS O ESCRIBENOS",
        "MÁS INFORMACIÓN EN",
        "MAS INFORMACION EN",
        "UNIDAD ESPECIALIZADA DE ATENCIÓN AL PÚBLICO",
        "UNIDAD ESPECIALIZADA DE ATENCION AL PUBLICO",
        "WWW.GOB.MX/CONSAR",
    ]

    end = len(tail)
    tail_up = tail.upper()

    for marker in cut_markers:
        pos = tail_up.find(marker)
        if pos != -1 and pos < end:
            end = pos

    return tail[:end].strip()


# =========================
# FORMATO A: Principal
# =========================

def extract_from_multi_header_row(lines):
    labels = ["IMSS 1997", "SAR IMSS 1992", "SAR INFONAVIT 1992"]

    for i, line in enumerate(lines):
        up = normalize_label_text(line)

        if all(label in up for label in labels):
            value_line = None

            for j in range(1, 4):
                idx = i + j
                if idx < len(lines):
                    nums = find_money_amounts(lines[idx])
                    if len(nums) >= 4:
                        value_line = lines[idx]
                        break

            if not value_line:
                continue

            nums = [parse_amount(x) for x in find_money_amounts(value_line)]

            return {
                "imss_1997": nums[0] if len(nums) > 0 else None,
                "sar_imss_1992": nums[1] if len(nums) > 1 else None,
                "sar_infonavit_1992": nums[3] if len(nums) > 3 else None,
            }

    return {
        "imss_1997": None,
        "sar_imss_1992": None,
        "sar_infonavit_1992": None,
    }


def extract_infonavit_1997_principal(lines):
    for i, line in enumerate(lines):
        up = normalize_label_text(line)

        if "INFONAVIT 1997" in up:
            same = extract_amount_from_same_line(line, "INFONAVIT 1997")
            if same is not None:
                return same

            for j in range(1, 4):
                idx = i + j
                if idx < len(lines):
                    nums = [parse_amount(x) for x in find_money_amounts(lines[idx])]
                    if nums:
                        return nums[-1]

    return None


def extract_format_principal(lines):
    multi = extract_from_multi_header_row(lines)

    return {
        "imss_1997": multi.get("imss_1997"),
        "sar_imss_1992": multi.get("sar_imss_1992"),
        "sar_infonavit_1992": multi.get("sar_infonavit_1992"),
        "infonavit_1997": extract_infonavit_1997_principal(lines),
    }


# =========================
# FORMATO B: Banorte texto
# =========================

def extract_format_banorte_detail_regex(text):
    block = extract_detail_block(text)

    if not block:
        return {
            "imss_1997": None,
            "sar_imss_1992": None,
            "sar_infonavit_1992": None,
            "infonavit_1997": None,
        }

    return {
        "imss_1997": extract_money_after_label_in_text(block, "IMSS 1997"),
        "sar_imss_1992": extract_money_after_label_in_text(block, "SAR IMSS 1992"),
        "sar_infonavit_1992": extract_money_after_label_in_text(block, "SAR INFONAVIT 1992"),
        "infonavit_1997": extract_money_after_label_in_text(block, "INFONAVIT 1997"),
    }


# =========================
# FORMATO C: Banorte visual SAR 92
# =========================

def ocr_crop_for_sar92(pdf_path):
    """
    OCR dirigido a la zona visual del bloque SAR 92.
    Regresa texto recortado y debug.
    """
    info = {
        "text": "",
        "error": "",
        "crop_box": None,
    }

    try:
        from pdf2image import convert_from_path
        import pytesseract
        from PIL import ImageOps, ImageFilter

        tesseract_path = find_tesseract_path()
        poppler_path = find_poppler_bin()

        if tesseract_path:
            pytesseract.pytesseract.tesseract_cmd = tesseract_path

        if not tesseract_path:
            info["error"] = "No se encontró Tesseract instalado."
            return info

        pages = convert_from_path(
            pdf_path,
            dpi=300,
            first_page=1,
            last_page=1,
            poppler_path=poppler_path if poppler_path else None
        )
        if not pages:
            info["error"] = "No se pudo rasterizar el PDF."
            return info

        img = pages[0]
        w, h = img.size

        # zona aproximada donde aparece DETALLE DE TU CUENTA / SAR 92
        left = int(w * 0.02)
        top = int(h * 0.48)
        right = int(w * 0.92)
        bottom = int(h * 0.88)

        crop = img.crop((left, top, right, bottom))
        crop = ImageOps.grayscale(crop)
        crop = ImageOps.autocontrast(crop)
        crop = crop.filter(ImageFilter.SHARPEN)

        info["crop_box"] = [left, top, right, bottom]

        txt = pytesseract.image_to_string(crop, lang="spa+eng", config="--psm 6")
        info["text"] = safe_text(txt).strip()
        return info

    except Exception as e:
        info["error"] = safe_text(str(e))
        return info


def extract_sar_imss_1992_from_visual_block(pdf_path, current_results=None):
    """
    Último recurso: OCR solo a la zona visual del detalle de cuenta.
    Busca bloque SAR 92 y devuelve el primer monto no cero antes de SAR ISSSTE 1992.
    """
    if current_results is None:
        current_results = {}

    ocr_info = ocr_crop_for_sar92(pdf_path)
    text = ocr_info.get("text", "")
    if not text:
        return None, ocr_info

    text = normalize_text(text)

    sar_block = extract_block_between(
        text,
        "SAR 92",
        [
            "TU AHORRO VOLUNTARIO",
            "TU AHORRO PARA LA VIVIENDA",
            "AHORRO VOLUNTARIO",
            "AHORRO PARA LA VIVIENDA",
        ]
    )

    if not sar_block:
        # si no detecta SAR 92, intenta desde SAR hasta TU AHORRO VOLUNTARIO
        sar_block = extract_block_between(
            text,
            "SAR",
            [
                "TU AHORRO VOLUNTARIO",
                "TU AHORRO PARA LA VIVIENDA",
                "AHORRO VOLUNTARIO",
                "AHORRO PARA LA VIVIENDA",
            ]
        )

    if not sar_block:
        return None, ocr_info

    # intento exacto
    direct = extract_money_after_label_in_text(sar_block, "SAR IMSS 1992")
    if direct is not None and abs(direct) > 0.000001:
        return direct, ocr_info

    nums = [parse_amount(x) for x in find_money_amounts(sar_block)]
    nums = [n for n in nums if n is not None]

    if not nums:
        return None, ocr_info

    used = set()
    for k in ["imss_1997", "sar_infonavit_1992", "infonavit_1997"]:
        v = current_results.get(k)
        if v is not None:
            used.add(round(float(v), 2))

    # preferir monto no cero y no repetido
    for n in nums:
        rn = round(float(n), 2)
        if abs(n) > 0.000001 and rn not in used:
            return n, ocr_info

    return None, ocr_info


def merge_results(primary, fallback):
    merged = dict(primary)
    for k, v in fallback.items():
        if merged.get(k) is None and v is not None:
            merged[k] = v
    return merged


def all_main_fields_null(results):
    keys = ["imss_1997", "sar_imss_1992", "sar_infonavit_1992", "infonavit_1997"]
    return all(results.get(k) is None for k in keys)


def extract_data(text, pdf_path=None, debug=None):
    lines = get_lines(text)

    principal = extract_format_principal(lines)
    banorte_regex = extract_format_banorte_detail_regex(text)

    merged = merge_results(principal, banorte_regex)

    # fallback visual solo para SAR IMSS 1992
    if merged.get("sar_imss_1992") is None and pdf_path:
        val, visual_debug = extract_sar_imss_1992_from_visual_block(pdf_path, merged)
        if val is not None:
            merged["sar_imss_1992"] = val
        if debug is not None:
            debug["sar92_visual_ocr_preview"] = visual_debug.get("text", "")[:1200]
            debug["sar92_visual_crop_box"] = visual_debug.get("crop_box")
            if visual_debug.get("error"):
                debug["sar92_visual_ocr_error"] = visual_debug.get("error")

    return merged


def process_pdf(pdf_path):
    debug = {
        "method": None,
        "pdf_path": pdf_path,
        "text_preview": "",
        "used_ocr": False,
        "case_detected": "",
        "reason": "",
        "ocr_error": "",
        "text_length": 0,
        "tesseract_path": "",
        "poppler_path": ""
    }

    text = extract_text_pdfplumber(pdf_path)
    if text:
        debug["method"] = "pdfplumber"
        debug["case_detected"] = "text_found_pdfplumber"

    if not text:
        text = extract_text_pymupdf(pdf_path)
        if text:
            debug["method"] = "pymupdf"
            debug["case_detected"] = "text_found_pymupdf"

    normalized = normalize_text(text) if text else ""
    debug["text_length"] = len(normalized)

    need_ocr = False

    if not normalized:
        need_ocr = True
        debug["reason"] = "No hubo texto extraíble en la capa del PDF."
    elif is_text_too_short(normalized):
        need_ocr = True
        debug["reason"] = "El texto extraído es demasiado corto o incompleto."
    elif not has_useful_keywords(normalized):
        need_ocr = True
        debug["reason"] = "El texto existe, pero no contiene etiquetas clave de la tabla."
    elif should_force_ocr(normalized):
        need_ocr = True
        debug["reason"] = "Formato por columnas detectado; se fuerza OCR para mejorar el orden del texto."

    if need_ocr:
        ocr_result = extract_text_ocr(pdf_path)
        ocr_text = ocr_result.get("text", "")
        ocr_error = ocr_result.get("error", "")
        debug["tesseract_path"] = ocr_result.get("tesseract_path", "")
        debug["poppler_path"] = ocr_result.get("poppler_path", "")

        if ocr_error:
            debug["ocr_error"] = ocr_error

        if ocr_text:
            text = ocr_text
            normalized = normalize_text(text)
            debug["used_ocr"] = True
            debug["text_length"] = len(normalized)

            if debug["method"]:
                debug["method"] = debug["method"] + " + ocr"
                debug["case_detected"] = "text_partial_then_ocr"
            else:
                debug["method"] = "ocr"
                debug["case_detected"] = "scanned_pdf_or_image_based"

    if not normalized:
        return {
            "success": False,
            "error": "No se pudo extraer texto útil del PDF.",
            "data": None,
            "debug": debug
        }

    debug["text_preview"] = normalized[:3500]

    data = extract_data(normalized, pdf_path=pdf_path, debug=debug)

    if all_main_fields_null(data):
        if not debug["reason"]:
            debug["reason"] = "Se extrajo texto, pero no se localizaron las etiquetas o montos esperados."

    return {
        "success": True,
        "data": data,
        "debug": debug
    }


def main():
    try:
        if len(sys.argv) < 2:
            print_json({
                "success": False,
                "error": "Debes indicar la ruta del PDF."
            })
            return

        pdf_path = sys.argv[1]

        if not os.path.isfile(pdf_path):
            print_json({
                "success": False,
                "error": "El archivo PDF no existe.",
                "pdf_path": pdf_path
            })
            return

        result = process_pdf(pdf_path)
        print_json(result)

    except Exception as e:
        print_json({
            "success": False,
            "error": safe_text(str(e)),
            "trace": safe_text(traceback.format_exc())
        })


if __name__ == "__main__":
    main()