FROM php:7.4-apache

ARG APP_DIR=/var/www/http
RUN mkdir -p ${APP_DIR}
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
RUN docker-php-ext-configure pdo_mysql --with-pdo-mysql=mysqlnd
RUN docker-php-ext-configure mysqli --with-mysqli=mysqlnd
RUN docker-php-ext-install pdo_mysql

WORKDIR ${APP_DIR}
COPY . .

RUN apt-get update
RUN apt-get install -y zip
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN php composer.phar install

RUN cp .env.example .env
RUN php artisan key:generate

RUN chown -R www-data:www-data ${APP_DIR}
RUN a2enmod rewrite
