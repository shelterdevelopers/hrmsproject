FROM php:8.1-cli
RUN docker-php-ext-install pdo pdo_mysql mysqli
WORKDIR /var/www/html
COPY . /var/www/html/
# PHP built-in server natively uses PORT - no entrypoint needed
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /var/www/html"]
