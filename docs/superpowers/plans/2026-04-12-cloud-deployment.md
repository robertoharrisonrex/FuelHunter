# Cloud Deployment Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deploy FuelHunter to a single DigitalOcean Droplet (or equivalent VPS) with nginx + PHP-FPM serving Laravel, Docker Compose running Airflow, and a managed PostgreSQL database replacing SQLite.

**Architecture:** Laravel and the Airflow ETL both connect to a managed PostgreSQL instance via `FUELDB_URL`. Airflow's internal metadata stays in its own Dockerised PostgreSQL (already in docker-compose). nginx terminates HTTPS and proxies PHP-FPM. Supervisor keeps the queue worker alive.

**Tech Stack:** Ubuntu 24.04, nginx, PHP 8.3-FPM, Composer, Node 20, Docker, Supervisor, PostgreSQL 16 (managed), Let's Encrypt / Certbot

---

## File Map

| File | Action | Purpose |
|---|---|---|
| `database/migrations/2026_03_20_000000_add_performance_indexes.php` | Modify | Make `transaction_date` generated column work on both SQLite and PostgreSQL |
| `app/Livewire/Dashboard.php` | Modify | Replace SQLite `STRFTIME` with driver-aware date extraction |
| `airflow/dags/qldfuelapi_to_sqlite_etl.py` | Modify | Read DB connection from `FUELDB_URL` env var; wrap all SQL in `text()` |
| `.env.example` | Modify | Add all production vars with placeholders and comments |
| `deploy/nginx.conf` | Create | Production nginx server block (HTTP→HTTPS redirect + PHP-FPM) |
| `deploy/supervisor.conf` | Create | Supervisor program block for `queue:work` |
| `deploy/deploy.sh` | Create | Repeatable deployment script (pull → build → cache → migrate → restart) |
| `deploy/setup.sh` | Create | One-time server bootstrap (install PHP, nginx, Node, Docker, Supervisor) |
| `airflow/docker-compose.prod.yml` | Create | Production Airflow overrides (remove SQLite volume, add FUELDB_URL) |

---

## Task 1: Make `transaction_date` generated column cross-database

PostgreSQL supports stored generated columns but not virtual ones. The existing migration uses `->virtualAs()` which is SQLite-only. Update it to branch on the driver.

**Files:**
- Modify: `database/migrations/2026_03_20_000000_add_performance_indexes.php`

- [ ] **Step 1: Update the migration**

Replace the file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('prices', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->date('transaction_date')->storedAs("transaction_date_utc::date")->nullable();
            } else {
                $table->string('transaction_date', 10)->virtualAs("date(transaction_date_utc)")->nullable();
            }
            $table->index(['fuel_id', 'transaction_date'], 'prices_fuel_id_date_index');
            $table->index(['site_id', 'fuel_id'], 'prices_site_id_fuel_id_index');
        });

        Schema::table('historical_site_prices', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->date('transaction_date')->storedAs("transaction_date_utc::date")->nullable();
            } else {
                $table->string('transaction_date', 10)->virtualAs("date(transaction_date_utc)")->nullable();
            }
            $table->index(['fuel_id', 'transaction_date'], 'hsp_fuel_id_date_index');
            $table->index('site_id', 'hsp_site_id_index');
        });

        Schema::table('fuel_sites', function (Blueprint $table) {
            $table->index('brand_id', 'fuel_sites_brand_id_index');
            $table->index('geo_region_2', 'fuel_sites_geo_region_2_index');
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropIndex('prices_fuel_id_date_index');
            $table->dropIndex('prices_site_id_fuel_id_index');
            $table->dropColumn('transaction_date');
        });

        Schema::table('historical_site_prices', function (Blueprint $table) {
            $table->dropIndex('hsp_fuel_id_date_index');
            $table->dropIndex('hsp_site_id_index');
            $table->dropColumn('transaction_date');
        });

        Schema::table('fuel_sites', function (Blueprint $table) {
            $table->dropIndex('fuel_sites_brand_id_index');
            $table->dropIndex('fuel_sites_geo_region_2_index');
        });
    }
};
```

- [ ] **Step 2: Verify existing SQLite dev environment still migrates cleanly**

```bash
php artisan migrate:fresh --seed
```

Expected: all migrations run without errors.

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_03_20_000000_add_performance_indexes.php
git commit -m "fix: make transaction_date generated column compatible with PostgreSQL"
```

---

## Task 2: Fix STRFTIME in Dashboard.php for PostgreSQL

SQLite uses `STRFTIME('%w', col)` for day-of-week. PostgreSQL uses `EXTRACT(DOW FROM col)::integer`. Both need to produce 0=Sunday … 6=Saturday.

**Files:**
- Modify: `app/Livewire/Dashboard.php:164-215`

- [ ] **Step 1: Add a private helper method for the driver-aware expression**

In `app/Livewire/Dashboard.php`, add this private method after `cacheKey()`:

```php
private function dowExpression(string $column): string
{
    return DB::connection()->getDriverName() === 'pgsql'
        ? "EXTRACT(DOW FROM {$column})::integer"
        : "STRFTIME('%w', {$column})";
}
```

- [ ] **Step 2: Replace the two STRFTIME calls in `weeklyChartData()`**

Find (line ~179):
```php
->selectRaw("fuel_id, STRFTIME('%w', transaction_date_utc) as day_of_week, price");
```
Replace with:
```php
->selectRaw("fuel_id, {$this->dowExpression('transaction_date_utc')} as day_of_week, price");
```

Find (line ~187, inside the unionAll sub-query):
```php
->selectRaw("fuel_id, STRFTIME('%w', transaction_date_utc) as day_of_week, price")
```
Replace with:
```php
->selectRaw("fuel_id, {$this->dowExpression('transaction_date_utc')} as day_of_week, price")
```

- [ ] **Step 3: Verify the dashboard still loads on SQLite dev**

```bash
php artisan serve --port=7025 &
curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:7025/dashboard
# Expected: 200
kill %1
```

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Dashboard.php
git commit -m "fix: use driver-aware day-of-week expression for PostgreSQL compatibility"
```

---

## Task 3: Update Airflow ETL to use FUELDB_URL env var

The ETL hardcodes a SQLite path. Replace every `create_engine(...)` call with one that reads `FUELDB_URL` from the environment, falling back to SQLite for local dev. Also wrap all raw SQL strings in `text()` for SQLAlchemy 2.x compatibility.

**Files:**
- Modify: `airflow/dags/qldfuelapi_to_sqlite_etl.py`

- [ ] **Step 1: Replace the SQLite engine helper throughout the file**

The file currently has this pattern repeated in every function:
```python
engine = create_engine('sqlite:////opt/airflow/database/database.sqlite')
conn = engine.connect()
```

Add a helper near the top of the file (after the imports):

```python
def _engine():
    url = os.environ.get(
        'FUELDB_URL',
        'sqlite:////opt/airflow/database/database.sqlite'
    )
    return create_engine(url)
```

Then replace every occurrence of:
```python
engine = create_engine('sqlite:////opt/airflow/database/database.sqlite')
conn = engine.connect()
```
with:
```python
engine = _engine()
conn = engine.connect()
```

There are occurrences in: `transform_brands`, `transform_regions`, `transform_fuel_sites`, `transform_fuel_types`, `load_brands`, `load_fuel_sites`, `load_fuel_types`, `load_fuel_prices`, and `transform_fuel_prices`.

- [ ] **Step 2: Wrap all raw SQL strings in text()**

Every `conn.execute("""...""")` call must become `conn.execute(text("""..."""))`.
`text` is already imported at line 10: `from sqlalchemy import ..., text`.

Find all occurrences (there are ~10) and wrap them. Example:

```python
# Before
conn.execute("""
    UPDATE brands SET ...
""")

# After
conn.execute(text("""
    UPDATE brands SET ...
"""))
```

Apply to every `conn.execute(...)` call in `load_brands`, `load_regions`, `load_fuel_sites`, `load_fuel_types`, and `load_fuel_prices`.

- [ ] **Step 3: Fix deprecated MetaData(bind=conn)**

`MetaData(bind=conn)` is removed in SQLAlchemy 2.x. Two functions use it:

In `transform_brands` (line ~42):
```python
# Before
metadata = MetaData(bind=conn)
brand_temp_table = Table("temp_brands", metadata, ...)
metadata.create_all(conn)

# After
metadata = MetaData()
brand_temp_table = Table("temp_brands", metadata, ...)
metadata.create_all(engine)
```

Apply the same fix in `transform_fuel_sites` (which uses `MetaData()` already — verify and leave it).

- [ ] **Step 4: Commit**

```bash
git add airflow/dags/qldfuelapi_to_sqlite_etl.py
git commit -m "fix: read DB connection from FUELDB_URL env var; wrap SQL in text() for SQLAlchemy 2.x"
```

---

## Task 4: Update .env.example

**Files:**
- Modify: `.env.example`

- [ ] **Step 1: Replace the file contents**

```ini
APP_NAME=FuelHunter
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_TIMEZONE=Australia/Brisbane
APP_URL=https://your-domain.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=warning

# PostgreSQL (production)
DB_CONNECTION=pgsql
DB_HOST=your-managed-db-host.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=fuelHunter
DB_USERNAME=fuelHunter
DB_PASSWORD=your-db-password
DB_SSLMODE=require

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

VITE_APP_NAME="${APP_NAME}"

# Queensland Fuel Price Reporting Scheme API
# Get from: https://www.business.qld.gov.au/industries/mining-energy-water/fuel/price-reporting
FUEL_API_TOKEN="FPDAPI SubscriberToken=your-token-here"

# Google Maps JavaScript API key
# Restrict this key to HTTP referrers (your domain only) in Google Cloud Console
GOOGLE_API_TOKEN=your-google-maps-key-here
```

- [ ] **Step 2: Commit**

```bash
git add .env.example
git commit -m "chore: update .env.example with all production vars and correct defaults"
```

---

## Task 5: Create nginx config

**Files:**
- Create: `deploy/nginx.conf`

- [ ] **Step 1: Create the file**

```nginx
# /etc/nginx/sites-available/fuelHunter
# Symlink to /etc/nginx/sites-enabled/fuelHunter after setup

server {
    listen 80;
    listen [::]:80;
    server_name YOUR_DOMAIN;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    http2 on;

    server_name YOUR_DOMAIN;
    root /var/www/fuelHunter/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/YOUR_DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/YOUR_DOMAIN/privkey.pem;
    ssl_protocols       TLSv1.2 TLSv1.3;
    ssl_ciphers         HIGH:!aNULL:!MD5;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Rate-limit the map tile endpoint (expensive DB query)
    location ~ ^/map-tiles/ {
        limit_req zone=maptiles burst=20 nodelay;
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Rate limit zone (put this in /etc/nginx/nginx.conf http block, or a conf.d file)
# limit_req_zone $binary_remote_addr zone=maptiles:10m rate=30r/m;
```

- [ ] **Step 2: Commit**

```bash
git add deploy/nginx.conf
git commit -m "chore: add production nginx config"
```

---

## Task 6: Create Supervisor config

**Files:**
- Create: `deploy/supervisor.conf`

- [ ] **Step 1: Create the file**

```ini
; Copy to /etc/supervisor/conf.d/fuelHunter.conf on the server

[program:fuelHunter-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/fuelHunter/artisan queue:work --sleep=3 --tries=3 --max-time=3600
directory=/var/www/fuelHunter
autostart=true
autorestart=true
startretries=3
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/fuelHunter/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=3
user=www-data
```

- [ ] **Step 2: Commit**

```bash
git add deploy/supervisor.conf
git commit -m "chore: add Supervisor config for queue worker"
```

---

## Task 7: Create deploy script

**Files:**
- Create: `deploy/deploy.sh`

- [ ] **Step 1: Create the file**

```bash
#!/usr/bin/env bash
set -euo pipefail

DEPLOY_PATH=/var/www/fuelHunter

echo "==> Pulling latest code"
cd "$DEPLOY_PATH"
git pull origin main

echo "==> Installing PHP dependencies"
composer install --no-dev --optimize-autoloader

echo "==> Installing JS dependencies and building assets"
npm ci
npm run build

echo "==> Caching Laravel config / routes / views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running migrations"
php artisan migrate --force

echo "==> Restarting queue worker"
sudo supervisorctl restart fuelHunter-queue:*

echo "==> Done."
```

- [ ] **Step 2: Commit**

```bash
git add deploy/deploy.sh
chmod +x deploy/deploy.sh   # ensure executable bit in repo
git commit -m "chore: add deploy.sh"
```

---

## Task 8: Create server setup script

This is a one-time script run as root on a fresh Ubuntu 24.04 server. It installs all dependencies, clones the repo, and wires everything together.

**Files:**
- Create: `deploy/setup.sh`

- [ ] **Step 1: Create the file**

```bash
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
git clone https://github.com/YOUR_ORG/fuelHunter.git "$DEPLOY_PATH"
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
```

- [ ] **Step 2: Commit**

```bash
git add deploy/setup.sh
chmod +x deploy/setup.sh
git commit -m "chore: add one-time server setup script"
```

---

## Task 9: Create Airflow production docker-compose override

This file removes the SQLite volume mount and injects `FUELDB_URL` so Airflow's ETL writes to the managed PostgreSQL instead.

**Files:**
- Create: `airflow/docker-compose.prod.yml`

- [ ] **Step 1: Create the file**

```yaml
# Production overrides for Airflow.
# Usage: docker compose -f docker-compose.yaml -f docker-compose.prod.yml up -d
#
# Set these in airflow/.env before starting:
#   AIRFLOW_FERNET_KEY=<generate with: python -c "from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())">
#   FUELDB_URL=postgresql+psycopg2://USER:PASS@HOST:25060/DBNAME?sslmode=require
#   _AIRFLOW_WWW_USER_USERNAME=admin
#   _AIRFLOW_WWW_USER_PASSWORD=<strong password>

x-airflow-prod-env:
  &airflow-prod-env
  FUELDB_URL: '${FUELDB_URL}'
  AIRFLOW__WEBSERVER__SECRET_KEY: '${AIRFLOW_SECRET_KEY}'

services:
  airflow-webserver:
    environment:
      <<: *airflow-prod-env
    ports:
      - "127.0.0.1:8080:8080"   # bind to localhost only; expose via nginx if needed

  airflow-scheduler:
    environment:
      <<: *airflow-prod-env
    volumes:
      - ./dags:/opt/airflow/dags
      - ./logs:/opt/airflow/logs
      - ./config:/opt/airflow/config
      - ./plugins:/opt/airflow/plugins
      # SQLite volume removed — ETL now uses FUELDB_URL

  airflow-worker:
    environment:
      <<: *airflow-prod-env
    volumes:
      - ./dags:/opt/airflow/dags
      - ./logs:/opt/airflow/logs
      - ./config:/opt/airflow/config
      - ./plugins:/opt/airflow/plugins
      # SQLite volume removed

  postgres:
    environment:
      POSTGRES_USER: '${AIRFLOW_POSTGRES_USER:-airflow}'
      POSTGRES_PASSWORD: '${AIRFLOW_POSTGRES_PASSWORD:?Set AIRFLOW_POSTGRES_PASSWORD}'
      POSTGRES_DB: airflow
```

- [ ] **Step 2: Add required vars to `airflow/.env.example`**

Create `airflow/.env.example`:

```ini
AIRFLOW_UID=1000
AIRFLOW_FERNET_KEY=
AIRFLOW_SECRET_KEY=
AIRFLOW_POSTGRES_USER=airflow
AIRFLOW_POSTGRES_PASSWORD=
_AIRFLOW_WWW_USER_USERNAME=admin
_AIRFLOW_WWW_USER_PASSWORD=

# FuelHunter managed database — both ETL and Laravel use this
FUELDB_URL=postgresql+psycopg2://USER:PASS@HOST:25060/DBNAME?sslmode=require
```

- [ ] **Step 3: Commit**

```bash
git add airflow/docker-compose.prod.yml airflow/.env.example
git commit -m "chore: add Airflow production docker-compose override and env example"
```

---

## Task 10: Server provisioning (manual steps)

These steps require a DigitalOcean account (or equivalent). They cannot be automated by the agent.

- [ ] **Step 1: Create a Droplet**
  - Size: `s-2vcpu-4gb` ($24/month) or `s-1vcpu-2gb` ($12/month) for low traffic
  - Image: Ubuntu 24.04 LTS
  - Region: Sydney (`syd1`) for Queensland users
  - Add your SSH key during creation

- [ ] **Step 2: Create a Managed PostgreSQL database**
  - DigitalOcean → Databases → Create → PostgreSQL 16
  - Same region as the Droplet
  - Plan: Basic ($15/month for 1GB RAM)
  - Create a database named `fuelHunter` and a user named `fuelHunter`
  - Note the connection string from the "Connection Details" tab (use the `psycopg2` format)
  - Add the Droplet to the database's "Trusted Sources"

- [ ] **Step 3: Point DNS to the Droplet IP**
  - Add an A record: `yourdomain.com → <Droplet IP>`
  - Wait for propagation before running setup.sh (certbot needs DNS live)

- [ ] **Step 4: Generate an APP_KEY**

  On your local machine:
  ```bash
  cd /Users/roberto/Projects/FuelHunter/FuelHunter
  php artisan key:generate --show
  # Copy the output: base64:xxxx...
  ```

- [ ] **Step 5: SSH in and run setup.sh**

  ```bash
  ssh root@<Droplet IP>
  # On the server:
  curl -fsSL https://raw.githubusercontent.com/YOUR_ORG/fuelHunter/main/deploy/setup.sh | \
    bash -s -- \
      yourdomain.com \
      your-db-host.db.ondigitalocean.com \
      fuelHunter \
      fuelHunter \
      'your-db-password' \
      'FPDAPI SubscriberToken=your-token' \
      'your-google-maps-key' \
      'base64:your-app-key'
  ```

---

## Task 11: Start Airflow on the server

- [ ] **Step 1: Copy the Airflow directory to the server**

  ```bash
  # From your local machine:
  scp -r /Users/roberto/Projects/FuelHunter/FuelHunter/airflow root@<Droplet IP>:/opt/airflow-fuelHunter
  ```

- [ ] **Step 2: Create `airflow/.env` on the server**

  ```bash
  ssh root@<Droplet IP>
  cd /opt/airflow-fuelHunter

  # Generate a Fernet key:
  docker run --rm apache/airflow:2.9.0 python -c \
    "from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())"

  cp .env.example .env
  # Edit .env and fill in:
  #   AIRFLOW_FERNET_KEY=<from above>
  #   AIRFLOW_SECRET_KEY=<random string>
  #   AIRFLOW_POSTGRES_PASSWORD=<strong password>
  #   FUELDB_URL=postgresql+psycopg2://fuelHunter:pass@host:25060/fuelHunter?sslmode=require
  #   _AIRFLOW_WWW_USER_PASSWORD=<strong password>
  nano .env
  ```

- [ ] **Step 3: Start Airflow**

  ```bash
  cd /opt/airflow-fuelHunter
  docker compose -f docker-compose.yaml -f docker-compose.prod.yml up airflow-init
  docker compose -f docker-compose.yaml -f docker-compose.prod.yml up -d
  ```

- [ ] **Step 4: Verify Airflow is running and set API credentials**

  ```bash
  docker compose ps
  # All services should show "running (healthy)"

  # Set the fuel API credentials as Airflow Variables (replaces hardcoded values):
  docker compose exec airflow-webserver airflow variables set FUEL_REPORTING_SCHEME_URL "https://fppdirectapi-prod.fuelpricesqld.com.au/"
  docker compose exec airflow-webserver airflow variables set FUEL_REPORTING_API_KEY "FPDAPI SubscriberToken=your-token"

  # Set the OilPrice API token (used by oilprice_etl DAG):
  docker compose exec airflow-webserver airflow variables set OILPRICE_API_TOKEN "your-oilprice-api-token"
  ```

- [ ] **Step 5: Trigger a manual ETL run to populate PostgreSQL**

  ```bash
  docker compose exec airflow-webserver airflow dags trigger qldfuelapi_to_sqlite_etl
  ```

  Wait 5–10 minutes, then verify data in PostgreSQL:

  ```bash
  # From local machine, connecting to managed DB:
  psql "postgresql://fuelHunter:pass@host:25060/fuelHunter?sslmode=require" \
    -c "SELECT COUNT(*) FROM prices;"
  # Expected: ~7,000–9,000 rows
  ```

---

## Task 12: Verify production deployment

- [ ] **Step 1: Check the dashboard loads**

  ```bash
  curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com/dashboard
  # Expected: 200
  ```

- [ ] **Step 2: Check the map tiles endpoint**

  ```bash
  curl -s -o /dev/null -w "%{http_code}" "https://yourdomain.com/map-tiles/2/-27/153"
  # Expected: 200
  ```

- [ ] **Step 3: Check the queue worker is running**

  ```bash
  ssh root@<Droplet IP> sudo supervisorctl status fuelHunter-queue:*
  # Expected: fuelHunter-queue:fuelHunter-queue_00   RUNNING
  ```

- [ ] **Step 4: Check the Laravel log for errors**

  ```bash
  ssh root@<Droplet IP> tail -50 /var/www/fuelHunter/storage/logs/laravel.log
  # Expected: no ERROR lines
  ```

- [ ] **Step 5: Restrict Google Maps API key**

  In Google Cloud Console → APIs & Services → Credentials:
  - Find the key matching `GOOGLE_API_TOKEN`
  - Under "Application restrictions" → select "Websites"
  - Add: `https://yourdomain.com/*`
  - Under "API restrictions" → restrict to "Maps JavaScript API"
  - Save

- [ ] **Step 6: Rotate credentials (if used in production for the first time)**

  - Regenerate the Queensland Fuel API token if the current one was ever committed to git
  - Update `FUEL_API_TOKEN` in `/var/www/fuelHunter/.env` and Airflow Variables

---

## Self-Review

**Spec coverage:**
- ✅ nginx + PHP-FPM config (Task 5, 8)
- ✅ PostgreSQL generated column compatibility (Task 1)
- ✅ SQLite STRFTIME → PostgreSQL EXTRACT (Task 2)
- ✅ ETL connection string (Task 3)
- ✅ SQLAlchemy 2.x `text()` wrappers (Task 3)
- ✅ .env.example hardened (Task 4)
- ✅ Supervisor / queue worker (Task 6)
- ✅ Deploy script (Task 7)
- ✅ Server setup (Task 8)
- ✅ Airflow prod config (Task 9)
- ✅ Server provisioning (Task 10)
- ✅ Data population (Task 11)
- ✅ Verification + Google Maps key restriction (Task 12)

**Tasks 10–12 are manual** (DigitalOcean account, DNS, SSH access) — they can't be executed by the agent but are fully documented as runbook steps.
