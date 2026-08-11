#!/bin/sh
set -e

APP_UID="${APP_UID:-1000}"
APP_GID="${APP_GID:-1000}"

# Ajusta el dueño de los archivos al UID/GID del host (equivalente al chown
# que antes hacía el servicio backend-init)
chown -R "${APP_UID}:${APP_GID}" /var/www/html

# Instala dependencias de Composer solo si hace falta:
# - no existe vendor/ todavía, o
# - composer.lock cambió después de la última instalación
if [ ! -d /var/www/html/vendor ] || [ /var/www/html/composer.lock -nt /var/www/html/vendor ]; then
  echo "==> Instalando dependencias de Composer..."
  su-exec "${APP_UID}:${APP_GID}" composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Cede el proceso principal (CMD del Dockerfile, ej. php-fpm) al usuario real
exec su-exec "${APP_UID}:${APP_GID}" "$@"
