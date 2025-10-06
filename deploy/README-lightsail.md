# Deploy GymIn to AWS Lightsail (Bitnami LAMP)

This guide deploys the Laravel app on a Lightsail Bitnami LAMP instance using local MariaDB, Apache, and PHP-FPM.

## 1) Create the Lightsail instance
- Create Lightsail -> Instances -> Linux/Unix -> Blueprint: LAMP (PHP)
- Size: choose per budget; open ports 22, 80 (and 443 if using SSL)
- Attach a static IP

## 2) Connect and prepare the server
SSH into the instance, then:

```
sudo /opt/bitnami/ctlscript.sh stop apache
sudo mkdir -p /opt/bitnami/projects/gymin
sudo chown -R bitnami:daemon /opt/bitnami/projects
sudo chmod -R g+w /opt/bitnami/projects

# Optional: install git and composer if missing
sudo apt-get update -y
sudo apt-get install -y git unzip
php -v || /opt/bitnami/php/bin/php -v

# Install composer locally
cd /home/bitnami
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/home/bitnami --filename=composer
echo 'export PATH="/home/bitnami:$PATH"' >> ~/.bashrc && source ~/.bashrc

# Clone the app
cd /opt/bitnami/projects
sudo -u bitnami git clone https://github.com/sahanvin2/GymIn.git gymin
cd gymin

# Install dependencies (Bitnami PHP path)
composer install --no-dev --optimize-autoloader

# Build assets (optional if repo already contains public/build)
# If Node is not installed on server, build locally and commit public/build
# npm ci && npm run build

# Environment
cp .env.production.example .env
/opt/bitnami/php/bin/php artisan key:generate

# Storage symlink and permissions
/opt/bitnami/php/bin/php artisan storage:link || true
sudo chown -R bitnami:daemon storage bootstrap/cache
sudo find storage -type d -exec chmod 775 {} \;
sudo find storage -type f -exec chmod 664 {} \;
sudo chmod -R 775 bootstrap/cache

```

## 3) Configure database (MariaDB on instance)
```
# Login as root via socket (Bitnami)
sudo /opt/bitnami/mariadb/bin/mariadb -u root -S /opt/bitnami/mariadb/tmp/mysql.sock

-- Inside MariaDB prompt:
CREATE DATABASE gymin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gymin_user'@'localhost' IDENTIFIED BY 'ChangeThisStrong!Pass1';
GRANT ALL PRIVILEGES ON gymin.* TO 'gymin_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Set .env
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=mysql/" .env
sed -i "s/^DB_HOST=.*/DB_HOST=localhost/" .env
sed -i "s/^DB_PORT=.*/DB_PORT=3306/" .env
sed -i "s/^DB_DATABASE=.*/DB_DATABASE=gymin/" .env
sed -i "s/^DB_USERNAME=.*/DB_USERNAME=gymin_user/" .env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=ChangeThisStrong!Pass1/" .env

# Use file drivers initially
sed -i "s/^CACHE_STORE=.*/CACHE_STORE=file/" .env
sed -i "s/^SESSION_DRIVER=.*/SESSION_DRIVER=file/" .env
sed -i "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/" .env

# Migrate and seed
/opt/bitnami/php/bin/php artisan migrate --force
/opt/bitnami/php/bin/php artisan db:seed --force

```

If root login fails, reset root password using an init file and restart MariaDB, then create the DB/user again.

## 4) Apache vhost
Create a vhost using the provided template `deploy/gymin-vhost.conf`:

```
sudo cp deploy/gymin-vhost.conf /opt/bitnami/apache/conf/vhosts/gymin-vhost.conf
sudo sed -i "s|APP_ROOT|/opt/bitnami/projects/gymin|g" /opt/bitnami/apache/conf/vhosts/gymin-vhost.conf
sudo /opt/bitnami/apache/bin/httpd -t
sudo /opt/bitnami/ctlscript.sh restart apache
```

Then browse: http://<your-static-ip>

## 5) Production cache and health
```
/opt/bitnami/php/bin/php artisan optimize:clear
/opt/bitnami/php/bin/php artisan config:cache
/opt/bitnami/php/bin/php artisan route:cache
/opt/bitnami/php/bin/php artisan view:cache
/opt/bitnami/php/bin/php artisan about
```

## 6) Optional: HTTPS
Attach a domain to the instance, then run:
```
sudo /opt/bitnami/bncert-tool
```

## Notes
- Ensure directory case matches namespaces (Api vs API) for Linux.
- APP_URL should be set to your domain or static IP in `.env`.
