FROM php:8.1-cli
RUN docker-php-ext-install pdo pdo_mysql mysqli
WORKDIR /var/www/html
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
COPY . /var/www/html/
ENTRYPOINT ["docker-entrypoint.sh"]
