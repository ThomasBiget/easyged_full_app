FROM php:8.3-apache

# Extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libcurl4-openssl-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Active mod_rewrite et mod_headers pour les URLs propres et CORS
RUN a2enmod rewrite headers

# Configure Apache pour pointer vers /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Autorise les .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copie les fichiers de dépendances d'abord
COPY composer.json ./

# Installe les dépendances PHP
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copie le reste du code
COPY . .

# Crée le dossier uploads
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
