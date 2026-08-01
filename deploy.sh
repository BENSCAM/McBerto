#!/usr/bin/env bash
set -euo pipefail

echo "==> Pulling latest code"
git pull

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing JS dependencies and building assets"
npm ci
npm run build

echo "==> Publishing Livewire assets as static files (avoids Nginx .js caching rules 404ing the dynamic livewire.js route)"
php artisan livewire:publish --assets

echo "==> Running migrations"
php artisan migrate --force

echo "==> Caching config, routes, views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Done."
