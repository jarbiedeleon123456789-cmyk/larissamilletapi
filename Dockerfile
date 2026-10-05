FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql \
 && a2enmod rewrite headers \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . /var/www/html/

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot ${APACHE_DOCUMENT_ROOT}|g' /etc/apache2/sites-available/000-default.conf \
 && sed -i 's|<Directory /var/www/html>|<Directory ${APACHE_DOCUMENT_ROOT}>|g' /etc/apache2/apache2.conf \
 && sed -i 's/\r$//' docker-entrypoint.sh \
 && chmod +x docker-entrypoint.sh \
 && mkdir -p runtime/cache runtime/logs runtime/session \
 && chown -R www-data:www-data /var/www/html

EXPOSE 10000
CMD ["./docker-entrypoint.sh"]
