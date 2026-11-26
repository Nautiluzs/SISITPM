FROM php:8.3-cli

# ============================================================
# Dependencias del sistema + extensiones PHP + Node.js 20
# ============================================================
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libicu-dev \
    libexif-dev \
    curl \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip gd mbstring bcmath intl exif pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Dependencias PHP (producción)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Dependencias Node + build de Vite
RUN npm install && npm run build

# ============================================================
# Variables SOLO para el build (no hay .env ni BDD real aún)
# ============================================================
ENV CACHE_STORE=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync

# SQLite dummy para que artisan no falle durante el build
RUN touch /var/www/html/database/database.sqlite

# Limpiar cachés generadas durante el build
RUN php artisan config:clear || true && \
    php artisan cache:clear || true && \
    php artisan route:clear || true && \
    php artisan view:clear || true

# Permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

EXPOSE 8000

# ============================================================
# ARRANQUE PARA RAILWAY (Base de datos limpia + Seeders)
# 1. Limpia cachés
# 2. Borra todas las tablas y vuelve a migrar todo desde cero
# 3. Ejecuta los seeders
# 4. Levanta el servidor
# ============================================================
CMD php artisan config:clear && \
    php artisan migrate:fresh --force --seed && \
    php artisan serve --host=0.0.0.0 --port=${PORT:-8000}