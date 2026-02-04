#!/bin/sh
# Railway injects MySQL vars into container - shell can see them, PHP often cannot.
# Write to file so config.railway.php can read them.
if [ -n "$MYSQL_PRIVATE_URL" ]; then
  printf '%s' "$MYSQL_PRIVATE_URL" > /tmp/railway_mysql_url
elif [ -n "$MYSQL_URL" ]; then
  printf '%s' "$MYSQL_URL" > /tmp/railway_mysql_url
fi
exec php -S 0.0.0.0:${PORT:-8080} -t /var/www/html
