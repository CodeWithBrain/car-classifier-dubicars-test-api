# Use official PHP 8.2 Apache image
FROM php:8.2-apache

# Install system dependencies
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

# Copy composer files first (to leverage Docker caching)
COPY composer.json composer.lock* ./

# Install Composer and PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php && \
    php composer.phar install --no-interaction --no-dev

# Copy rest of your app
COPY . .

# Apache configuration to allow .htaccess rewrite rules
RUN echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
</Directory>' > /etc/apache2/conf-available/app.conf && \
    a2enconf app

# Set Apache DocumentRoot to public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# Update Apache config to use the new DocumentRoot
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Expose HTTP port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]
