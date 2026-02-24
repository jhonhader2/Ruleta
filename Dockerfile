FROM php:8.2-apache

# Habilitar mod_rewrite para .htaccess
RUN a2enmod rewrite headers

# Configurar Apache: AllowOverride para .htaccess
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -i '/<Directory \${APACHE_DOCUMENT_ROOT}>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Extensiones PHP para MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Evitar salida HTML de errores (API debe devolver solo JSON)
RUN echo "display_errors = Off\ndisplay_startup_errors = Off\nlog_errors = On" > /usr/local/etc/php/conf.d/99-production.ini

COPY --chown=www-data:www-data . /var/www/html/

# Permisos para data (rate limit, etc.)
RUN mkdir -p /var/www/html/data && chown www-data:www-data /var/www/html/data

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
EXPOSE 80
