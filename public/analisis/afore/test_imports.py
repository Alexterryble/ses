try:
    import pdfplumber
    print("pdfplumber OK")
except Exception as e:
    print("pdfplumber ERROR:", e)

try:
    import fitz
    print("fitz OK")
except Exception as e:
    print("fitz ERROR:", e)

try:
    from pdf2image import convert_from_path
    print("pdf2image OK")
except Exception as e:
    print("pdf2image ERROR:", e)

try:
    import pytesseract
    print("pytesseract OK")
except Exception as e:
    print("pytesseract ERROR:", e)