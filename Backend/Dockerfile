FROM php:8.2-cli

# Install essential packages + tesseract + imagemagick
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
    npm \
    tesseract-ocr \
    tesseract-ocr-ara \
    libtesseract-dev \
    imagemagick

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy all project files
COPY . .

# Install PHP dependencies
RUN composer require spatie/browsershot && \
    composer update && \
    composer install --no-interaction --optimize-autoloader

# Install Puppeteer for Browsershot
RUN npm install puppeteer

# Configure Puppeteer
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Optional Tesseract ENV
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# Expose port
EXPOSE 8000

# Start Laravel server
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
