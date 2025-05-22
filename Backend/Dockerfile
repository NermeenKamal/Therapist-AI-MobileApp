# Start from PHP base image
FROM php:8.2-cli

# Set non-interactive mode for apt
ENV DEBIAN_FRONTEND=noninteractive

# Install minimal required packages first
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip git curl zip sqlite3 \
    && rm -rf /var/lib/apt/lists/* && apt-get clean

# Install PHP development libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev libpng-dev libonig-dev libxml2-dev \
    libsqlite3-dev libsodium-dev pkg-config \
    && rm -rf /var/lib/apt/lists/* && apt-get clean

# Install Node.js (using NodeSource repository for latest version)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

# Install Tesseract and ImageMagick
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr tesseract-ocr-ara tesseract-ocr-eng \
    libtesseract-dev imagemagick libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/* && apt-get clean

# Install Chromium (minimal installation)
RUN apt-get update && apt-get install -y --no-install-recommends \
    chromium-browser \
    && rm -rf /var/lib/apt/lists/* && apt-get clean

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd mbstring xml fileinfo sodium

# Install ImageMagick PHP extension
RUN pecl install imagick && docker-php-ext-enable imagick

# Add Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configure Puppeteer
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser

# Install Puppeteer
RUN npm install --production puppeteer

# Set Tesseract path
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# Set proper permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose port
EXPOSE 8000

# Start Laravel
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
