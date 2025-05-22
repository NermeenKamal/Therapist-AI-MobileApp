# Start from PHP base image
FROM php:8.2-cli

# Install basic system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    zip \
    sqlite3 \
    && rm -rf /var/lib/apt/lists/*

# Install development libraries
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    libsodium-dev \
    pkg-config \
    && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm
RUN apt-get update && apt-get install -y \
    nodejs \
    npm \
    && rm -rf /var/lib/apt/lists/*

# Install Tesseract
RUN apt-get update && apt-get install -y \
    tesseract-ocr \
    libtesseract-dev \
    && rm -rf /var/lib/apt/lists/*

# Install ImageMagick
RUN apt-get update && apt-get install -y \
    imagemagick \
    libmagickwand-dev \
    && rm -rf /var/lib/apt/lists/*

# Install Chromium
RUN apt-get update && apt-get install -y \
    chromium \
    && rm -rf /var/lib/apt/lists/*

# Install tesseract language packs
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

# Copy tessdata files and ensure proper permissions
RUN mkdir -p /var/www/tessdata/
COPY tessdata/ /var/www/tessdata/
# Also copy to system location as backup
RUN cp -r /var/www/tessdata/* /usr/share/tesseract-ocr/4.00/tessdata/ || true

# Set default Tesseract path (will be overridden by Railway env var)
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# Install Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader

# Configure Puppeteer to use system Chromium
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Install Puppeteer dependencies
RUN npm install puppeteer



# Set proper permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose port (for Laravel serve)
EXPOSE 8000

# Laravel startup command for Railway
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
