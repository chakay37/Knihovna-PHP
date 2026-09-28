FROM php:8.3-apache

# PDO pro MySQL/MariaDB
RUN docker-php-ext-install pdo_mysql && a2enmod rewrite headers

COPY vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY . .
RUN mkdir -p storage && chown -R www-data:www-data storage
