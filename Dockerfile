FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite headers

# Allow overrides to support .htaccess files
RUN echo "<Directory /var/www/html>\n\tAllowOverride All\n</Directory>" > /etc/apache2/conf-available/allow-override.conf && \
    a2enconf allow-override

# Ensure www-data can write to the directory (especially _tmp)
RUN chown -R www-data:www-data /var/www/html
