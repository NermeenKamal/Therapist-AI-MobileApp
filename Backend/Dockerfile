FROM php:8.2-cli

# تثبيت الحزم الأساسية
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    curl \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libmagickwand-dev \
    tesseract-ocr \
    supervisor \
    wget \
    libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# إعداد إضافات PHP
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# تثبيت Imagick
RUN pecl install imagick && docker-php-ext-enable imagick

# إعداد Tesseract OCR
RUN mkdir -p /usr/share/tesseract-ocr/4.00/tessdata && \
    wget -q https://github.com/tesseract-ocr/tessdata/raw/main/ara.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/ && \
    wget -q https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# إعداد Supervisor
RUN mkdir -p /var/log/supervisor && \
    mkdir -p /var/run/supervisor && \
    chown -R www-data:www-data /var/log/supervisor /var/run/supervisor

WORKDIR /var/www

# نسخ ملفات composer أولاً
COPY composer.json composer.lock ./

# تثبيت Composer والاعتمادات
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer install --no-interaction --optimize-autoloader --no-dev

# نسخ باقي ملفات المشروع
COPY . .

# صلاحيات Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# نسخ ملف إعداد Supervisor
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

USER www-data

# 🚨 تشغيل الـ migrations بدون توقف التطبيق لو حصلت مشكلة بسبب جدول موجود
CMD ["sh", "-c", "php artisan migrate --force || true && /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf"]
