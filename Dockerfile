# ---------------------------
# Stage 1: composer builder
# ---------------------------
FROM composer:2 AS composer-builder
WORKDIR /app

# Copiar archivos que necesita composer
COPY composer.json composer.lock* /app/

# Si composer.lock existe, usará las versiones fijadas; si no, funcionará también
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader || true

# ---------------------------
# Stage 2: runtime PHP 8.2 + Apache
# ---------------------------
FROM php:8.2-apache

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Instalar dependencias del sistema necesarias para extensiones PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
 && rm -rf /var/lib/apt/lists/*

# Configurar y compilar extensiones PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_mysql \
    mysqli \
    zip \
    gd \
    mbstring \
    intl \
    xml \
    opcache

# Copiar el binario de composer por si se necesita en runtime
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar el código fuente
COPY . /var/www/html/

# Copiar vendor desde la etapa composer-builder (si existió)
COPY --from=composer-builder /app/vendor /var/www/html/vendor

WORKDIR /var/www/html

# Permisos
RUN chown -R www-data:www-data /var/www/html \
 && chmod -R 755 /var/www/html

# Permitir .htaccess y rewrite
RUN sed -i 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
 && printf '\n<Directory /var/www/html/>\n    AllowOverride All\n</Directory>\n' >> /etc/apache2/apache2.conf

EXPOSE 80
CMD ["apache2-foreground"]
