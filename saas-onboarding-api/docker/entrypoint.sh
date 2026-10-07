#!/bin/sh
set -e

# Generate a throwaway app key for local runs if none was supplied.
if [ -z "$APP_KEY" ]; then
  APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
  export APP_KEY
fi

# Run migrations, retrying while the database finishes starting (up to ~60 seconds).
attempt=1
until php artisan migrate --force; do
  if [ "$attempt" -ge 30 ]; then
    echo "Database not reachable; giving up." >&2
    exit 1
  fi
  attempt=$((attempt + 1))
  echo "Waiting for database..."
  sleep 2
done

if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
  php artisan db:seed --force
fi

exec "$@"
