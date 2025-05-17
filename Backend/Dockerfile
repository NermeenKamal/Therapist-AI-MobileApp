FROM php:8.2-cli

# Install essential packages + chromium + nodejs + npm
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
    npm

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip

# Copy composer from official image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Install PHP dependencies and make sure browsershot is properly added
RUN composer require spatie/browsershot && \
    composer update && \
    composer install --no-interaction --optimize-autoloader

# Install Puppeteer via npm for browsershot to use
RUN npm install puppeteer

# Configure environment for browsershot
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

EXPOSE 8000

# Set PORT environment variable if not defined
ENV PORT=8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=$PORT"]
