# Use official PHP 8.2 Apache image
FROM php:8.2-apache

# Install dependencies (for Composer & dotenv support)
RUN apt-get update && apt-get install -y \
    unzip \
    curl \
    git \
    libzip-dev \
    && docker-php-ext-install zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock* ./
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-interaction --no-dev

# Copy rest of the app files
COPY . .

# Copy Apache config to allow .htaccess rewrites (optional)
RUN echo "<Directory /var/www/html/public>
    AllowOverride All
</Directory>" > /etc/apache2/conf-available/app.conf && \
    a2enconf app

# Set DocumentRoot to public folder
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Update Apache config to use public/ as root
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose port 80 (Render uses it)
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
