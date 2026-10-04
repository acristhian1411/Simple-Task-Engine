#!/bin/sh
set -e

APP_UID="${APP_UID:-1000}"
APP_GID="${APP_GID:-1000}"

# Directorios que Laravel escribe en runtime → dueño = usuario del host
chown -R "${APP_UID}:${APP_GID}" /var/www/html/storage /var/www/html/bootstrap/cache

# Asegura un grupo y un usuario con el GID/UID del host (alpine usa busybox)
if ! getent group "${APP_GID}" >/dev/null 2>&1; then
  addgroup -g "${APP_GID}" app
fi
if ! getent passwd "${APP_UID}" >/dev/null 2>&1; then
  adduser -D -u "${APP_UID}" -G app app
fi

# El pool de php-fpm corre como ese usuario (zz-user.conf se carga último y pisa www.conf)
GROUP_NAME="$(getent group "${APP_GID}" | cut -d: -f1)"
USER_NAME="$(getent passwd "${APP_UID}" | cut -d: -f1)"
cat > /usr/local/etc/php-fpm.d/zz-user.conf <<EOF
[www]
user = ${USER_NAME}
group = ${GROUP_NAME}
EOF

exec "$@"
