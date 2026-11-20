FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Clean all MPM modules and strictly enable prefork + rewrite for PHP
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite

# Copy application files
COPY . /var/www/html/

WORKDIR /var/www/html

# Set directory permissions for web server and uploads
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

# Runtime startup: purge any conflicting MPMs and bind Apache to Railway's dynamic $PORT
CMD ["sh", "-c", "rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* && sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf && exec apache2-foreground"]
