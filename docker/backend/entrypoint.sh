#!/bin/bash
set -e

# Skip migrations when vendor/ is not yet installed (e.g. composer install)
if [ -f /var/www/backend/vendor/autoload.php ]; then
    php artisan migrate --force
    php artisan db:seed --force
fi

exec "$@"