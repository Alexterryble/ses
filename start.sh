#!/bin/sh
set -eu

echo "=== Sanitizando MPM (runtime) ==="

rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true

for f in /etc/apache2/apache2.conf /etc/apache2/conf-enabled/*.conf /etc/apache2/sites-enabled/*.conf /etc/apache2/mods-enabled/*.load; do
  [ -f "$f" ] || continue
  sed -i "/^[[:space:]]*LoadModule[[:space:]]\\+mpm_/d" "$f" || true
done

a2enmod mpm_prefork >/dev/null 2>&1 || true

echo "=== mods-enabled (mpm) ==="
ls -la /etc/apache2/mods-enabled/ | grep mpm || true

exec apache2-foreground
