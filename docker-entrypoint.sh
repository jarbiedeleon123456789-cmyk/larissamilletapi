#!/bin/sh
set -e

# Render tells us which port to listen on.
PORT="${PORT:-80}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p runtime/cache runtime/logs runtime/session
chown -R www-data:www-data runtime

# Run pending migrations against the database (never block startup).
if [ -n "$DB_HOST" ]; then
  echo "Running migrations..."
  MIGRATION_ENABLED=true php lava migration run || echo "Migration step failed - check DB_* variables."
fi

exec apache2-foreground
