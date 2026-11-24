#!/bin/sh
set -e

export PORT="${PORT:-10000}"

# Render memberi PORT (default 10000). Generate config nginx dari template.
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

# Symlink storage publik (abaikan kalau sudah ada)
php artisan storage:link >/dev/null 2>&1 || true

# Migrasi database otomatis. Set RUN_MIGRATIONS=false untuk mematikan.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force || echo "WARN: migrate gagal, container tetap dijalankan"
fi

# php-fpm di background, nginx di foreground (PID 1)
php-fpm -D
exec nginx -g 'daemon off;'
