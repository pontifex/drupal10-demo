#!/bin/bash
set -e

# Support Railway dynamic PORT
if [ -n "$PORT" ]; then
  echo "Configuring Apache to listen on port $PORT..."
  sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
  sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:$PORT>/" /etc/apache2/sites-available/*.conf
fi

# Ensure only mpm_prefork is enabled along with caching/compression modules
a2dismod -f mpm_event mpm_worker >/dev/null 2>&1 || true
a2enmod mpm_prefork rewrite expires headers deflate >/dev/null 2>&1 || true

# If MySQL/MariaDB host is provided (Railway or Docker Compose)
DB_HOST="${MYSQLHOST:-${MYSQL_HOST:-${DB_HOST:-}}}"
DB_PORT="${MYSQLPORT:-${MYSQL_PORT:-${DB_PORT:-3306}}}"
DB_USER="${MYSQLUSER:-${MYSQL_USER:-${DB_USER:-drupal}}}"
DB_PASS="${MYSQLPASSWORD:-${MYSQL_PASSWORD:-${DB_PASSWORD:-drupal}}}"
DB_NAME="${MYSQLDATABASE:-${MYSQL_DATABASE:-${DB_NAME:-drupal}}}"

if [ -z "$DB_HOST" ] && [ -n "${MYSQL_URL:-${DATABASE_URL:-}}" ]; then
  URL="${MYSQL_URL:-${DATABASE_URL}}"
  DB_HOST=$(php -r '$p=parse_url($argv[1]); echo $p["host"]??"";' "$URL")
  DB_PORT=$(php -r '$p=parse_url($argv[1]); echo $p["port"]??3306;' "$URL")
  DB_USER=$(php -r '$p=parse_url($argv[1]); echo $p["user"]??"";' "$URL")
  DB_PASS=$(php -r '$p=parse_url($argv[1]); echo $p["pass"]??"";' "$URL")
  DB_NAME=$(php -r '$p=parse_url($argv[1]); echo ltrim($p["path"]??"","/");' "$URL")
fi

if [ -n "$DB_HOST" ]; then

  echo "Checking database connection to $DB_HOST:$DB_PORT ($DB_NAME)..."
  MYSQL_CMD="mysql --ssl-verify-server-cert=0 -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS"
  MAX_TRIES=30
  COUNT=0
  until $MYSQL_CMD -e "USE $DB_NAME;" >/dev/null 2>&1 || [ $COUNT -eq $MAX_TRIES ]; do
    echo "Waiting for database to become available ($COUNT/$MAX_TRIES)..."
    sleep 2
    COUNT=$((COUNT + 1))
  done

  if [ $COUNT -lt $MAX_TRIES ]; then
    echo "Database connection successful!"
    TABLE_COUNT=$($MYSQL_CMD -D "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    if [ "$TABLE_COUNT" -le 1 ]; then
      if [ -f /opt/drupal/docker/init-db.sql.gz ]; then
        echo "Database is empty. Importing init-db.sql.gz..."
        gunzip -c /opt/drupal/docker/init-db.sql.gz | $MYSQL_CMD -D "$DB_NAME"
        echo "Database import complete!"
      elif [ -f /opt/drupal/docker/init-db.sql ]; then
        echo "Database is empty. Importing init-db.sql..."
        $MYSQL_CMD -D "$DB_NAME" < /opt/drupal/docker/init-db.sql
        echo "Database import complete!"
      fi
    else
      echo "Database already contains tables ($TABLE_COUNT), running pending updates..."
      /opt/drupal/vendor/bin/drush updb -y || true
    fi
    /opt/drupal/vendor/bin/drush cr || true
  else
    echo "Warning: Database did not become ready in time, continuing anyway..."
  fi
fi

# Ensure files directory permissions
mkdir -p /opt/drupal/web/sites/default/files
chown -R www-data:www-data /opt/drupal/web/sites/default/files

# Ensure robots.txt contains sitemap
if [ -f /opt/drupal/web/robots.txt ] && ! grep -qi "sitemap:" /opt/drupal/web/robots.txt; then
  echo -e "\n# Sitemap XML\nSitemap: /sitemap.xml" >> /opt/drupal/web/robots.txt
fi

exec "$@"
