FROM php:8.2-apache

# Enable required Apache modules
RUN a2enmod rewrite headers

# Allow overrides to support .htaccess files
RUN echo "<Directory /var/www/html>\n\tAllowOverride All\n</Directory>" > /etc/apache2/conf-available/allow-override.conf && \
    a2enconf allow-override
# Copy application source code into the image
COPY . /var/www/html/
# Ensure the _tmp directory exists for note storage
RUN mkdir -p /var/www/html/_tmp
# Ensure www-data can write to the directory (especially _tmp)
RUN chown -R www-data:www-data /var/www/html
