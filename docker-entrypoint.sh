#!/bin/bash
# Railway injects PORT - Apache must listen on it
PORT=${PORT:-8080}
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf
exec apache2-foreground
