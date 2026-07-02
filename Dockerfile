# Usa la imagen oficial de PHP con Apache
FROM php:8.2-apache

# ✅ Instala Python + venv + deps del sistema (OCR opcional)
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3 python3-pip python3-venv \
    poppler-utils \
    tesseract-ocr tesseract-ocr-spa \
    && rm -rf /var/lib/apt/lists/*

# ✅ Crea un venv para evitar "externally-managed-environment"
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# ✅ Instala librerías Python que usa parse_periodos.py
RUN pip install --no-cache-dir --upgrade pip setuptools wheel && \
    pip install --no-cache-dir pdfplumber pymupdf pdf2image pytesseract pillow

# Instala las extensiones necesarias de PHP para MySQL
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Habilita módulos de Apache si usas .htaccess o rewrite
RUN a2enmod rewrite

# ✅ Aumentar límites de subida PHP (Railway)
RUN echo "upload_max_filesize=10M\npost_max_size=12M\nmemory_limit=256M\nmax_execution_time=120\nmax_input_time=120" > /usr/local/etc/php/conf.d/uploads.ini


# ✅ Forzar un solo MPM (mod_php requiere prefork)
# 1) Deshabilita todos (por si acaso)
# 2) Borra cualquier symlink residual en mods-enabled
# 3) Habilita SOLO prefork
RUN a2dismod mpm_event mpm_worker mpm_prefork || true \
 && rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
 && a2enmod mpm_prefork

# ✅ DIAGNÓSTICO: ver qué MPM queda habilitado y si hay otros LoadModule escondidos
# (Esto aparece en Build Logs)
RUN echo "=== mods-enabled (mpm) ===" \
 && ls -la /etc/apache2/mods-enabled/ | grep mpm || true \
 && echo "=== LoadModule mpm_ en /etc/apache2 ===" \
 && grep -R "LoadModule mpm_" -n /etc/apache2 || true

# ✅ PLAN B (para que NO pueda cargarse event/worker aunque algún conf lo intente)
RUN rm -f /usr/lib/apache2/modules/mod_mpm_event.so /usr/lib/apache2/modules/mod_mpm_worker.so || true

# Establece el directorio raíz de documentos como /var/www/html/public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Ajusta la configuración de Apache para usar el nuevo directorio raíz
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# ✅ Copiar el script de arranque para Railway
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh
CMD ["/usr/local/bin/start.sh"]


# Copia todo el contenido del proyecto al contenedor
COPY . /var/www/html/

# Asigna permisos al usuario de Apache
RUN chown -R www-data:www-data /var/www/html/

# Expone el puerto HTTP
EXPOSE 80
