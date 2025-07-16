FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libssl-dev pkg-config libcurl4-openssl-dev unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copier le contenu du dossier public dans le webroot
COPY ./public/ /var/www/html/
# Copier les fichiers nécessaires pour le seed
COPY ./seed.php /var/www/html/
# Copier composer.json et installer les dépendances
COPY ./composer.json /var/www/html/
WORKDIR /var/www/html
RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html
