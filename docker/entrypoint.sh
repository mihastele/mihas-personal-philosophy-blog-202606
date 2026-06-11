#!/bin/bash
set -e

echo "Fixing upload directory permissions..."
mkdir -p /var/www/html/uploads
mkdir -p /var/www/html/custom_posts
chown -R www-data:www-data /var/www/html/uploads
chown -R www-data:www-data /var/www/html/custom_posts
chmod 755 /var/www/html/uploads
chmod 755 /var/www/html/custom_posts

echo "Starting Apache..."
exec apache2-foreground
