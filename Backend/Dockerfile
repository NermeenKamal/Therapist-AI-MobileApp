# تحديد الـ PHP وApache كـ base image
FROM php:8.0-apache

# تفعيل الـ mod_rewrite الخاص بـ Apache (مهم لـ Laravel)
RUN a2enmod rewrite

# تثبيت التبعيات المطلوبة
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev zip git
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install gd
RUN docker-php-ext-install pdo pdo_mysql

# تثبيت Composer
RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# نسخ كل الملفات إلى مجلد العمل داخل الـ Docker container
COPY . /var/www/html

# تحديد مجلد العمل
WORKDIR /var/www/html

# تثبيت تبعيات Laravel
RUN composer install

# تعيين الأمر الافتراضي لتشغيل Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=10000"]
