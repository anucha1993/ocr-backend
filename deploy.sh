#!/bin/bash
# =============================================================
# deploy-backend.sh — Laravel Backend on Plesk (SSH)
# รันหลังจาก upload ไฟล์ขึ้น server แล้ว
# =============================================================
set -e

echo "▶ [1/6] Installing Composer dependencies (production)..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "▶ [2/6] Copying .env.production → .env..."
cp .env.production .env

echo "▶ [3/6] Running migrations..."
php artisan migrate --force

echo "▶ [4/6] Caching config / routes / views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "▶ [5/6] Linking public storage..."
php artisan storage:link || true   # ถ้า link มีอยู่แล้วไม่ error

echo "▶ [6/6] Setting permissions..."
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo ""
echo "✅ Backend deployed successfully!"
echo "   Document root ใน Plesk ควรชี้ไปที่: public/"
