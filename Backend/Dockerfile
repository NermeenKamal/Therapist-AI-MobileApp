FROM php:8.2-cli

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
    && rm -rf /var/lib/apt/lists/*

RUN pecl install imagick && docker-php-ext-enable imagick

RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

WORKDIR /var/www

RUN mkdir -p /usr/share/tesseract-ocr/4.00/tessdata && \
    wget https://github.com/tesseract-ocr/tessdata/raw/main/ara.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/ && \
    wget https://github.com/tesseract-ocr/tessdata/raw/main/eng.traineddata -P /usr/share/tesseract-ocr/4.00/tessdata/

ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-interaction --optimize-autoloader

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# انسخي ملف supervisord.conf لاحقاً
COPY ./supervisord.conf /etc/supervisord.conf

USER www-data

# تشغيل supervisor لتشغيل أكثر من خدمة
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
