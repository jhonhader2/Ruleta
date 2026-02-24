#!/bin/sh
set -e

# Crear config/database.php desde ejemplo si no existe (usa variables de entorno)
if [ ! -f /var/www/html/config/database.php ]; then
  cp /var/www/html/config/database.example.php /var/www/html/config/database.php
fi

# Asegurar que data sea escribible (rate limit, etc.)
mkdir -p /var/www/html/data
chown -R www-data:www-data /var/www/html/data

exec apache2-foreground
