FROM php:8.2-cli

# تثبيت الأدوات والمكتبات المطلوبة
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
    wget \
    && rm -rf /var/lib/apt/lists/*

# تثبيت Imagick لـ PHP
RUN pecl install imagick && docker-php-ext-enable imagick

# تثبيت بعض إضافات PHP المطلوبة غالبًا مع Laravel
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# إعداد مجلد العمل
WORKDIR /var/www

# تحميل ملفات اللغة العربية والإنجليزية لـ Tesseract
RUN mkdir -p /usr/share/tesseract-ocr/4.00/tessdata && \
    wget https://github.com/tesseract-ocr/tessdata/raw/main/ara.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/ && \
    wget https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/

# تعيين مسار tessdata
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# نسخ ملفات المشروع إلى داخل الحاوية
COPY . .
# تثبيت Composer يدويًا داخل الحاوية
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-interaction --optimize-autoloader


# تعيين صلاحيات مجلدات Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# تعيين المستخدم
USER www-data




CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]

