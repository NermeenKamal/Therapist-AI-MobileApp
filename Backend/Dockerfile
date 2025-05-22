# استخدم صورة PHP الأساسية
FROM php:8.2-cli

# استخدم env لإيقاف interactivity
ENV DEBIAN_FRONTEND=noninteractive

# تحديث النظام وتثبيت الأساسيات على دفعات صغيرة لتقليل الاستهلاك
RUN apt-get update && apt-get install -y --no-install-recommends unzip && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y --no-install-recommends git && rm -rf /var/lib/apt/lists/*
RUN apt-get update && apt-get install -y --no-install-recommends curl zip sqlite3 && rm -rf /var/lib/apt/lists/*

# تثبيت المكتبات الخاصة بـ Laravel & GD & Zip
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    libsodium-dev \
    pkg-config \
    && docker-php-ext-install pdo pdo_mysql zip gd mbstring xml fileinfo sodium \
    && rm -rf /var/lib/apt/lists/*

# تهيئة Tesseract و ImageMagick
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr \
    libtesseract-dev \
    tesseract-ocr-ara \
    tesseract-ocr-eng \
    imagemagick \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# تفعيل imagick extension
RUN pecl install imagick && docker-php-ext-enable imagick

# تثبيت Node.js و npm
RUN apt-get update && apt-get install -y --no-install-recommends nodejs npm && rm -rf /var/lib/apt/lists/*

# تثبيت Puppeteer فقط بدون Chromium (هتشغل النظامي)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# تثبيت Chromium لاستخدام Puppeteer
RUN apt-get update && apt-get install -y --no-install-recommends chromium && rm -rf /var/lib/apt/lists/*

# نسخ Composer من صورة رسمية (أسرع وأخف)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تعيين مجلد العمل
WORKDIR /var/www

# نسخ ملفات المشروع (بدون tessdata لو مش موجود)
COPY . .

# فقط انسخ tessdata لو موجود (خطوة اختيارية)
RUN mkdir -p /var/www/tessdata
RUN [ -d "./tessdata" ] && cp -r ./tessdata/* /usr/share/tesseract-ocr/4.00/tessdata/ || echo "no tessdata found"

# تعيين مسار tessdata في البيئة
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# تثبيت Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader

# تثبيت Puppeteer
RUN npm install puppeteer

# إعطاء صلاحيات التخزين والتخزين المؤقت
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# فتح المنفذ
EXPOSE 8000

# أمر التشغيل الأساسي للـ Railway
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
