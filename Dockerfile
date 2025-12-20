# This is an alternative to installing XAMPP,
# Build custom docker image for running IORPMS, combine with mysql docker image to successfully run the system

FROM php:8.2-apache

RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

EXPOSE 80