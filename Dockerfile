FROM php:8.2-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    chromium \
    chromium-driver \
    supervisor \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql gd zip pcntl \
    && rm -rf /var/lib/apt/lists/*

# Habilitar mod_rewrite (Laravel lo necesita)
RUN a2enmod rewrite

# Composer, para instalar paquetes dentro del contenedor (vendor/ vive en un volumen)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar el proyecto al contenedor
COPY . .

COPY config/courier-supervisord.conf /etc/supervisor/conf.d/portal4n.conf

# Permisos (simplificado para desarrollo)
RUN mkdir -p storage/app/private storage/app/public storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html


# Configurar Apache para Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/portal4n.conf"]
