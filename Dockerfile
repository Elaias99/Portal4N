FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql gd

# Habilitar mod_rewrite (Laravel lo necesita)
RUN a2enmod rewrite

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto al contenedor
COPY . .

# Permisos (simplificado para desarrollo)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html


# Configurar Apache para Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
