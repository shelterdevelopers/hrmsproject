#!/bin/sh
# Railway vars: shell sees them, PHP doesn't. Write to file for config.railway.php
# Prefer private URL (same project), fall back to public
if [ -n "$MYSQL_PRIVATE_URL" ]; then
  printf '%s' "$MYSQL_PRIVATE_URL" > /tmp/railway_mysql_url
elif [ -n "$MYSQL_URL" ]; then
  printf '%s' "$MYSQL_URL" > /tmp/railway_mysql_url
elif [ -n "$MYSQL_PUBLIC_URL" ]; then
  printf '%s' "$MYSQL_PUBLIC_URL" > /tmp/railway_mysql_url
fi
# Also write individual vars (in case only those are referenced)
if [ -n "$MYSQLHOST" ]; then
  printf 'host=%s\nuser=%s\npass=%s\ndb=%s\nport=%s\n' \
    "${MYSQLHOST}" "${MYSQLUSER}" "${MYSQLPASSWORD}" "${MYSQLDATABASE}" "${MYSQLPORT}" \
    > /tmp/railway_db_vars
fi
exec php -S 0.0.0.0:${PORT:-8080} -t /var/www/html
