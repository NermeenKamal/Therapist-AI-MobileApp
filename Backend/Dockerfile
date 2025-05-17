FROM php:8.2-cli

# تثبيت الحزم الأساسية + chromium + nodejs + npm
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    zip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    sqlite3 \
    libsqlite3-dev \
    chromium \
    nodejs \
    npm

# تثبيت إضافات PHP المطلوبة
RUN docker-php-ext-install pdo pdo_mysql zip

# نسخ composer من الصورة الرسمية
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# تثبيت مكتبات PHP
RUN composer install --no-interaction --optimize-autoloader

# تثبيت Puppeteer عبر npm
RUN npm install puppeteer

EXPOSE 8000

# تعيين متغير البيئة PORT لو مش معرف
ENV PORT=8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
