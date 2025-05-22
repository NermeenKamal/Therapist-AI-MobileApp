# Start from PHP base image
FROM php:8.2-cli

# Set working directory early
WORKDIR /var/www

# Install basic system dependencies (split + optimized)
RUN apt-get update && apt-get install -y --no-install-recommends \
    unzip \
    git \
    curl && \
    rm -rf /var/lib/apt/lists/*

RUN apt-get update && apt-get install -y --no-install-recommends \
    zip \
    sqlite3 && \
    rm -rf /var/lib/apt/lists/*

# Install development libraries
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libsqlite3-dev \
    libsodium-dev \
    pkg-config && \
    rm -rf /var/lib/apt/lists/*

# Install Node.js and npm (split for clarity)
RUN apt-get update && apt-get install -y --no-install-recommends \
    nodejs && \
    apt-get install -y --no-install-recommends \
    npm && \
    rm -rf /var/lib/apt/lists/*

# Install Tesseract
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr \
    libtesseract-dev && \
    rm -rf /var/lib/apt/lists/*

# Install Tesseract language packs
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr-ara \
    tesseract-ocr-eng && \
    rm -rf /var/lib/apt/lists/*

# Install ImageMagick
RUN apt-get update && apt-get install -y --no-install-recommends \
    imagemagick \
    libmagickwand-dev && \
    rm -rf /var/lib/apt/lists/*

# Install Chromium (headless browser)
RUN apt-get update && apt-get install -y --no-install-recommends \
    chromium && \
    rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip gd mbstring xml fileinfo sodium

# Install imagick extension
RUN pecl install imagick && docker-php-ext-enable imagick

# Add Composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy project files
COPY . .

# Copy tessdata and ensure permissions
RUN mkdir -p /var/www/tessdata/
COPY tessdata/ /var/www/tessdata/
RUN cp -r /var/www/tessdata/* /usr/share/tesseract-ocr/4.00/tessdata/ || true

# Set default Tesseract path
ENV TESSDATA_PREFIX=/usr/share/tesseract-ocr/4.00/tessdata/

# Install Laravel dependencies
RUN composer install --no-interaction --optimize-autoloader

# Puppeteer Chromium configuration
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Install Puppeteer
RUN npm install puppeteer

# Permissions
RUN chown -R www-data:www-data /var/www && \
    chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# Expose Laravel port
EXPOSE 8000

# Start Laravel
CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
