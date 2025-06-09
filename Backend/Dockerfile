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

# إعداد Tesseract OCR للغتين العربية والإنجليزية
RUN mkdir -p /usr/share/tesseract-ocr/4.00/tessdata && \
    wget -q https://github.com/tesseract-ocr/tessdata/raw/main/ara.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/ && \
    wget -q https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/

ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# إعداد مجلدات Supervisor
RUN mkdir -p /var/log/supervisor && \
    mkdir -p /var/run/supervisor && \
    chown -R www-data:www-data /var/log/supervisor /var/run/supervisor

WORKDIR /var/www

# نسخ كل ملفات المشروع
COPY . .

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# تثبيت الاعتمادات بعد نسخ كل الملفات لضمان وجود artisan
RUN composer install --no-interaction --optimize-autoloader --no-dev

# ضبط صلاحيات التخزين
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# نسخ إعدادات Supervisor
COPY ./supervisord.conf /etc/supervisor/conf.d/supervisord.conf

USER www-data

# أمر التشغيل النهائي
CMD ["sh", "-c", "php artisan migrate --force && /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf"]
