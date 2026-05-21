FROM php:8.2-apache

# PostgreSQL drayverlarini o'rnatish
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Loyha fayllarini konteynerga nusxalash
COPY . /var/www/html/

# Apache portini sozlash
EXPOSE 80
