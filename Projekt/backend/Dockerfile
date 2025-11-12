# Use the official PHP 8.1 image as the base image
FROM php:8.1-cli

# Set the working directory in the container
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring zip

# Copy the application files to the working directory
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install project dependencies using Composer
RUN composer install --optimize-autoloader --no-dev

# Expose the desired port for the Laravel development server
EXPOSE 9000

# Run the migration command inside the Docker container
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=9000