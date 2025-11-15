FROM php:8.2-apache

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project files
COPY . /var/www/html/

# Set document root to htdocs
ENV APACHE_DOCUMENT_ROOT /var/www/html/htdocs

# Update Apache VirtualHost
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/000-default.conf

RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf

# Fix file permissions
RUN chown -R www-data:www-data /var/www/html
