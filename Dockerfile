# =====================================================
#  Dockerfile para Render - Proyecto PHP 7.4 con Composer
# =====================================================

# Imagen base con PHP 7.4 y Apache
FROM php:7.4-apache

# Habilitar módulos de Apache necesarios
RUN a2enmod rewrite

# Instalar extensiones de PHP requeridas
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Instalar Composer globalmente
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copiar los archivos del proyecto al contenedor
COPY . /var/www/html/

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Asignar permisos adecuados
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instalar dependencias PHP mediante Composer (si existe composer.json)
RUN if [ -f composer.json ]; then composer install --no-interaction --optimize-autoloader; fi

# Configurar Apache para permitir .htaccess y mod_rewrite
RUN sed -i 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/>\nAllowOverride All\n</Directory>' >> /etc/apache2/apache2.conf

# Exponer el puerto
EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
