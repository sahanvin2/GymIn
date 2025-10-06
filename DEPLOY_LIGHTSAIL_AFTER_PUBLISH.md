# Lightsail After Publish Checklist (Laravel + Nginx + PHP 8.3)

Use this quick checklist right after you deploy/pull latest on the server.

## 1) Pull latest code
- git fetch --all
- git reset --hard origin/main

## 2) Environment
- cp .env.example .env (first time only)
- php artisan key:generate
- Update DB_*, APP_URL, SESSION_DRIVER=cookie, CACHE_DRIVER=file/redis, QUEUE_CONNECTION=sync/redis as needed

## 3) Dependencies
- composer install --no-dev --optimize-autoloader
- npm ci
- npm run build

Tip: If Node errors, install Node 20 LTS (nvm or distro). For Bitnami, prefer system node in /opt/bitnami.

## 4) Storage and caches
- php artisan storage:link
- php artisan migrate --force
- php artisan optimize:clear
- php artisan config:cache
- php artisan route:cache
- php artisan view:cache

## 5) Create admin user
- php artisan user:create-admin admin@gymin.com "Admin User" admin123
  or
- php artisan user:set-admin           # creates default admin if none

## 6) Permissions (Linux)
- sudo chown -R bitnami:daemon storage bootstrap/cache
- sudo chmod -R 775 storage bootstrap/cache

## 7) Nginx reload (Bitnami)
- sudo /opt/bitnami/ctlscript.sh restart nginx
- sudo /opt/bitnami/ctlscript.sh restart php-fpm

## 8) Smoke test
- curl -I https://your-domain/ | head -n 5
- curl -sS https://your-domain/up     # Laravel health endpoint

If something fails, check logs:
- storage/logs/laravel.log
- sudo journalctl -u php-fpm --no-pager | tail -n 100
- sudo tail -n 200 -f /opt/bitnami/nginx/logs/error.log
