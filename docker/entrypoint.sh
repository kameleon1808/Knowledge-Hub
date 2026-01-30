#!/usr/bin/env bash
set -e

# Fail-safe: remove Vite dev server hot file in non-local (or when CLEAR_VITE_HOT_ON_BOOT=true).
# Command skips deletion in local unless CLEAR_VITE_HOT_ON_BOOT is set.
if [ -f /var/www/html/artisan ]; then
    php /var/www/html/artisan app:clear-vite-hot --ansi || true
fi

exec "$@"
