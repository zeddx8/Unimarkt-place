FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli

# Ensure ONLY mpm_prefork is loaded (fixes AH00534: More than one MPM loaded)
RUN a2dismod -f mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite

# Copy application files
COPY . /var/www/html/

WORKDIR /var/www/html

# Set directory permissions for web server and uploads
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/www/html/uploads \
    && chmod -R 777 /var/www/html/uploads

# Dynamic port binding at container startup (Railway dynamically injects $PORT)
CMD ["sh", "-c", "sed -i \"s/80/$PORT/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf && exec apache2-foreground"]
