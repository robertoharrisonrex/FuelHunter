#!/usr/bin/env bash
# Run as root on a fresh Ubuntu 24.04 server.
# Usage: bash setup.sh YOUR_DOMAIN DB_HOST DB_NAME DB_USER DB_PASS FUEL_API_TOKEN GOOGLE_API_TOKEN APP_KEY
set -euo pipefail

DOMAIN="${1:?Usage: setup.sh DOMAIN DB_HOST DB_NAME DB_USER DB_PASS FUEL_TOKEN GOOGLE_TOKEN APP_KEY}"
DB_HOST="$2"
DB_NAME="$3"
DB_USER="$4"
DB_PASS="$5"
FUEL_TOKEN="$6"
GOOGLE_TOKEN="$7"
APP_KEY="$8"
DEPLOY_PATH=/var/www/fuelHunter

echo "==> System packages"
apt-get update -qq
apt-get install -y -qq \
    nginx supervisor git curl unzip \
    php8.3 php8.3-fpm php8.3-pgsql php8.3-mbstring php8.3-xml \
    php8.3-curl php8.3-zip php8.3-intl php8.3-bcmath \
    certbot python3-certbot-nginx

echo "==> Node 20"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y -qq nodejs

echo "==> Composer"
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

echo "==> Docker"
curl -fsSL https://get.docker.com | sh
apt-get install -y -qq docker-compose-plugin

echo "==> Clone repo"
mkdir -p "$DEPLOY_PATH"
git clone https://github.com/robertoharrisonrex/FuelHunter.git "$DEPLOY_PATH"
chown -R www-data:www-data "$DEPLOY_PATH"

echo "==> .env"
cat > "$DEPLOY_PATH/.env" <<EOF
APP_NAME=FuelHunter
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_TIMEZONE=Australia/Brisbane
APP_URL=https://${DOMAIN}
LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=25060
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}
DB_SSLMODE=require
SESSION_DRIVER=database
SESSION_ENCRYPT=true
QUEUE_CONNECTION=database
CACHE_STORE=database
FUEL_API_TOKEN="${FUEL_TOKEN}"
GOOGLE_API_TOKEN=${GOOGLE_TOKEN}
EOF

echo "==> Build and migrate"
cd "$DEPLOY_PATH"
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm ci
sudo -u www-data npm run build
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan storage:link

echo "==> nginx"
cp deploy/nginx.conf /etc/nginx/sites-available/fuelHunter
sed -i "s/YOUR_DOMAIN/${DOMAIN}/g" /etc/nginx/sites-available/fuelHunter
ln -sf /etc/nginx/sites-available/fuelHunter /etc/nginx/sites-enabled/fuelHunter
rm -f /etc/nginx/sites-enabled/default

# Add rate-limit zone to nginx http block
if ! grep -q "maptiles" /etc/nginx/nginx.conf; then
    sed -i '/http {/a\\tlimit_req_zone $binary_remote_addr zone=maptiles:10m rate=30r/m;' /etc/nginx/nginx.conf
fi

nginx -t
systemctl reload nginx

echo "==> SSL (requires DNS already pointing to this server)"
certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "admin@${DOMAIN}"

echo "==> Supervisor"
cp deploy/supervisor.conf /etc/supervisor/conf.d/fuelHunter.conf
supervisorctl reread
supervisorctl update
supervisorctl start fuelHunter-queue:*

echo "==> Done. Visit https://${DOMAIN}"
