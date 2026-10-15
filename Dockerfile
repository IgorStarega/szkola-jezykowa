FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli

RUN a2enmod rewrite

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]
