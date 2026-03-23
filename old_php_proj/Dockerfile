FROM php:8.2-apache

# Enable Apache modules
RUN a2enmod rewrite headers

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Set timezone
ENV TZ=Asia/Riyadh
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Set document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/attendance-system
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Allow .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Create cache and logs directories
RUN mkdir -p /var/www/html/attendance-system/storage/cache \
    /var/www/html/attendance-system/storage/logs \
    /var/www/html/attendance-system/storage/rate_limits \
    && chown -R www-data:www-data /var/www/html/attendance-system/storage

EXPOSE 80

CMD ["apache2-foreground"]
