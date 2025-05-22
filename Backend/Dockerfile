# Start from PHP base image
FROM php:8.2-cli

# Install system dependencies
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
    libtesseract-dev \
    imagemagick \
    libmagickwand-dev \
    libsodium-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Optional: Arabic traineddata (in case it's not copied)
RUN apt-get update && apt-get install -y tesseract-ocr-ara tesseract-ocr-eng && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd mbstring xml fileinfo sodium

# Install ImageMagick PHP extension
RUN pecl install imagick && docker-php-ext-enable imagick

# Add Composer from official composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Copy tessdata files into the container (relative to /var/www)
COPY tessdata/ /var/www/tessdata/

# Install Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader

# Configure Puppeteer to use system Chromium
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Install Puppeteer dependencies
RUN npm install puppeteer

# Set path for Tesseract language files
ENV TESSDATA_PREFIX=/var/www/tessdata/

# Set proper permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose port (for Laravel serve)
EXPOSE 8000

# Laravel startup command for Railway
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
