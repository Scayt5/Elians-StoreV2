# Usamos la imagen oficial de PHP con Apache
FROM php:8.2-apache

# 1. Instalamos dependencias del sistema
# Agregamos 'libicu-dev' que es necesaria para la librería de texto (intl)
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# 2. Configuramos e instalamos la extensión GD (Gráficos) y la extensión INTL (Texto/Moneda)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd intl

# 3. Instalamos las extensiones para MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# 4. Activamos módulos de Apache (URL limpias)
RUN a2enmod rewrite