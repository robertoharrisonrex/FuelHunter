# FuelHunter

Track Queensland fuel prices in real time. FuelHunter pulls live data from the Queensland Government Fuel Price Reporting Scheme, archives price history, and presents it through an interactive dashboard and map.

Live at **[fuelhunter.cloud](https://fuelhunter.cloud)**

---

## Features

- **Interactive map** — browse current prices at every Queensland fuel station, filtered by fuel type, with viewport-scoped tile loading
- **Price trends dashboard** — Chart.js line chart averaging daily prices across a configurable date range (7 days → 1 year) for up to 7 fuel types
- **Global oil prices** — WTI Crude, Brent Crude, Natural Gas, and Gasoline commodity prices updated every 20 minutes via OilPrice API
- **Brand market share** — doughnut chart of site counts by brand
- **Station explorer** — searchable/filterable list of fuel sites with current prices
- **Historical archive** — every price change is preserved; the dashboard queries both current and historical tables

---

## Tech Stack

| Layer | Technology |
|---|---|
| Web framework | Laravel 11 + Livewire 3 |
| Frontend | Tailwind CSS, Vite, Alpine.js |
| Charts | Chart.js 4 |
| Map | Google Maps JS API |
| Database (local) | SQLite |
| Database (production) | PostgreSQL 16 (DigitalOcean managed) |
| ETL pipeline | Apache Airflow 2 (Docker Compose, CeleryExecutor) |
| Web server | nginx + PHP 8.3-FPM |
| CI/CD | GitHub Actions |

---

## Local Development

### Prerequisites

- PHP 8.3 with extensions: `pdo_sqlite`, `mbstring`, `xml`, `curl`, `zip`
- Composer
- Node 18+
- Docker + Docker Compose (for Airflow only)

### Setup

```bash
git clone https://github.com/robertoharrisonrex/FuelHunter.git
cd FuelHunter

composer install
npm install

cp .env.example .env
php artisan key:generate

# Populate the database
php artisan migrate:fresh --seed

# Start the dev server (port 7025)
php artisan serve --port=7025

# In a separate terminal — watch/build frontend assets
npm run dev
```

Visit [http://127.0.0.1:7025](http://127.0.0.1:7025)

### Environment variables

Copy `.env.example` to `.env` and fill in:

| Variable | Description |
|---|---|
| `GOOGLE_API_TOKEN` | Google Maps JavaScript API key |
| `FUEL_API_TOKEN` | Queensland Fuel Price Reporting Scheme token |
| `OILPRICE_API_TOKEN` | OilPrice API key — set as an Airflow Variable (Admin → Variables), not used directly by Laravel |

---

## Running Tests

```bash
./vendor/bin/pest
```

Tests use an in-memory SQLite database. No external services or seed data required.

---

## Architecture

### ETL Pipeline (Airflow)

Two DAGs run inside Docker Compose:

| DAG | Schedule | What it does |
|---|---|---|
| `qldfuelapi_to_sqlite_etl` | Every 24 hours | Pulls brands, regions, sites, fuel types, and prices from the QLD Government API |
| `oilprice_etl` | Every 20 minutes | Fetches WTI, Brent, Natural Gas, and Gasoline spot prices from OilPrice API |

Price loading is incremental: when a new price arrives for a `(site_id, fuel_id)` pair on a different date, the old record is moved to `historical_site_prices` before the new one is inserted.

```
brands → regions → fuel_sites → fuel_types → prices
```

Start Airflow locally:

```bash
cd airflow
docker compose up
```

API credentials are stored as **Airflow Variables** (Admin → Variables), not in code.

### Database Schema (key tables)

| Table | Purpose |
|---|---|
| `fuel_sites` | Station metadata — name, address, brand, coordinates |
| `fuel_types` | Fuel type reference (Unleaded, Diesel, etc.) |
| `prices` | Current price per `(site_id, fuel_id)` |
| `historical_site_prices` | All superseded prices |
| `oil_prices` | Commodity spot prices with `(code, recorded_at)` uniqueness |

### Web App

- `app/Livewire/Dashboard.php` — date range + fuel type filters, chart data, brand share
- `app/Livewire/FuelMap.php` — fuel type selector, dispatches map tile events
- `app/Http/Controllers/MapTileController.php` — viewport-scoped geospatial tile endpoint
- `app/Http/Controllers/OilPriceController.php` — 30-day oil price series, cached 5 minutes

---

## Deployment

### CI/CD

Every push triggers the GitHub Actions workflow (`.github/workflows/ci-cd.yml`):

1. **CI** — runs the full Pest test suite on PHP 8.3
2. **CD** — SSHes into the production server and runs `deploy/deploy.sh` (main branch only, after CI passes)

Required GitHub secrets: `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY`

### Manual deploy

```bash
ssh root@<server>
cd /var/www/fuelHunter
bash deploy/deploy.sh
```

Pulls latest code, rebuilds assets, refreshes Laravel caches, runs migrations, restarts the queue worker.

### First-time server setup

See `deploy/setup.sh` for the full provisioning script (Ubuntu 24.04, nginx, PHP-FPM, Node, Docker, Supervisor, Let's Encrypt).

---

## Data Sources

- **Queensland fuel prices** — [Queensland Government Fuel Price Reporting Scheme](https://www.business.qld.gov.au/industries/mining-energy-water/fuel/price-reporting) (open data licence)
- **Global oil prices** — [OilPrice API](https://oilpriceapi.com)

FuelHunter is an independent project and is not affiliated with or endorsed by the Queensland Government or OilPrice API.

---

## License

MIT
