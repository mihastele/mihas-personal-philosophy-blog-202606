FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers expires deflate

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

COPY docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

RUN echo "upload_max_filesize = 10M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 12M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 128M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/security.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/security.ini

COPY public/ /var/www/html/
COPY includes/ /var/www/includes/
COPY config/ /var/www/config/

RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html/uploads \
    && chmod 755 /var/www/html/uploads

RUN chown -R www-data:www-data /var/www/html /var/www/includes /var/www/config

WORKDIR /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
