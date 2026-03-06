FROM php:8.2-apache

# Enable Apache modules commonly used with front controllers / rewrites
RUN a2enmod rewrite headers

# Install SQLite extensions for PHP
RUN docker-php-ext-install pdo_sqlite sqlite3

# (Optional but nice) show errors in container logs during demo development
RUN { \
  echo "display_errors=On"; \
  echo "display_startup_errors=On"; \
  echo "error_reporting=E_ALL"; \
} > /usr/local/etc/php/conf.d/zz-dev.ini

# Set working directory to the web root
WORKDIR /var/www/html

# Copy Apache vhost config (keeps it explicit and portable)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf