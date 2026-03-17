# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

FuelHunter tracks Queensland fuel prices. It has two main components:
1. **Airflow ETL pipeline** — pulls data from the Queensland Fuel Price Reporting Scheme API and stores it in SQLite
2. **Laravel web app** — lets users view fuel stations, prices, and price trends via an interactive dashboard

## Common Commands

```bash
# Laravel setup
php artisan migrate:fresh --seed

# Run the web app (port 7025)
php artisan serve --port=7025

# Build frontend assets
npm run dev        # dev server with HMR
npm run build      # production build

# Run tests
./vendor/bin/pest                  # all tests
./vendor/bin/pest tests/Feature/ExampleTest.php  # single test file

# Code style
./vendor/bin/pint                  # fix PHP code style

# Airflow (from /airflow directory)
docker compose up
```

## Architecture

### Data Pipeline (Airflow)

`airflow/dags/qldfuelapi_to_sqlite_etl.py` runs every 24 hours and processes entities in sequence:

```
brands → regions → fuel_sites → fuel_types → prices
```

Each entity follows an extract → transform → load pattern. The price loading logic is smart: when a new price arrives for a (site_id, fuel_id) pair with a different date, the old record is moved to `historical_site_prices` before the new one is inserted. Prices from the API are divided by 10 (stored in 0.1¢ increments by the API).

Airflow runs via Docker Compose (CeleryExecutor + Redis + PostgreSQL). API credentials are stored as Airflow Variables, not in code.

### Web App (Laravel 11 + Livewire 3)

- **Database:** SQLite at `database/database.sqlite` (shared with Airflow via volume mount)
- **Key models:** `FuelSite`, `Price`, `HistoricalSitePrice`, `FuelType`, `Brand`, geographic hierarchy (`Suburb`, `City`, `State`)
- **Dashboard:** `app/Livewire/Dashboard.php` + `resources/views/livewire/dashboard.blade.php` — renders fuel price trends using Chart.js; combines `prices` and `historical_site_prices` tables, averages by date over a configurable date range
- **Frontend stack:** Tailwind CSS, Vite, Chart.js, Google Maps JS API (`@googlemaps/js-api-loader`)

### Price Storage

Two tables handle prices:
- `prices` — current/latest price per (site_id, fuel_id)
- `historical_site_prices` — all prior prices moved there by the ETL when superseded

When querying price history, join both tables.
