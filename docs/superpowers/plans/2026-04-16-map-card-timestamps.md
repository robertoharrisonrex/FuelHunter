# Map Card Timestamps Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the ambiguous "Updated X ago" info-window label with two distinct lines — "{FuelType} price last changed X" (per-site) and "Last checked X" (global ETL run time from a new `settings` table).

**Architecture:** A new `settings` key/value table stores the last ETL run timestamp. The ETL writes to it on every run. `MapStatsController` reads it outside the cache block and appends it to the stats response. The JS frontend stores it as `globalLastChecked` and renders two timestamp lines in the info window.

**Tech Stack:** Laravel 11, Pest, SQLite, Python/Airflow, vanilla JS (Google Maps info window)

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `database/migrations/2026_04_16_100000_create_settings_table.php` | Create | Settings key/value table |
| `app/Http/Controllers/MapStatsController.php` | Modify | Append `last_checked_at` from `settings` table outside cache |
| `tests/Feature/MapStatsControllerTest.php` | Modify | Update assertions + add `last_checked_at` tests |
| `airflow/dags/qldfuelapi_to_sqlite_etl.py` | Modify | Write `last_prices_checked_at` at end of `load_fuel_prices` |
| `resources/views/livewire/fuel-map.blade.php` | Modify | Declare `globalLastChecked`, populate from stats, render two timestamp lines |

---

## Task 1: Settings table migration

**Files:**
- Create: `database/migrations/2026_04_16_100000_create_settings_table.php`

- [ ] **Step 1: Write the migration**

Create `database/migrations/2026_04_16_100000_create_settings_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

- [ ] **Step 2: Run migration**

```bash
php artisan migrate
```

Expected output includes: `2026_04_16_100000_create_settings_table ............. DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_16_100000_create_settings_table.php
git commit -m "feat: add settings table for ETL metadata"
```

---

## Task 2: MapStatsController — expose `last_checked_at` (TDD)

**Files:**
- Modify: `app/Http/Controllers/MapStatsController.php`
- Modify: `tests/Feature/MapStatsControllerTest.php`

- [ ] **Step 1: Write failing tests**

Replace the full content of `tests/Feature/MapStatsControllerTest.php` with:

```php
<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('map stats endpoint returns correct json structure including last_checked_at', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'min'            => 1.699,
            'max'            => 2.059,
            'count'          => 350,
            'fuel_type_name' => 'Unleaded',
        ]);

    $response = $this->getJson('/map-stats/2');

    $response->assertOk()
             ->assertJsonStructure(['min', 'max', 'count', 'fuel_type_name', 'last_checked_at'])
             ->assertJsonFragment(['fuel_type_name' => 'Unleaded'])
             ->assertJsonPath('min', 1.699)
             ->assertJsonPath('max', 2.059)
             ->assertJsonPath('count', 350)
             ->assertJsonPath('last_checked_at', null);
});

test('map stats returns zeros for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => '']);

    $response = $this->getJson('/map-stats/999');

    $response->assertOk()
             ->assertJsonPath('count', 0)
             ->assertJsonPath('fuel_type_name', '')
             ->assertJsonPath('last_checked_at', null);
});

test('map stats returns last_checked_at from settings table when present', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 1.699, 'max' => 2.059, 'count' => 350, 'fuel_type_name' => 'Unleaded']);

    DB::table('settings')->insert([
        'key'   => 'last_prices_checked_at',
        'value' => '2026-04-16 08:30:00',
    ]);

    $response = $this->getJson('/map-stats/2');

    $response->assertOk()
             ->assertJsonPath('last_checked_at', '2026-04-16 08:30:00');
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Feature/MapStatsControllerTest.php
```

Expected: the third test fails (`last_checked_at` key missing from response). The first two may also fail on `last_checked_at` assertion.

- [ ] **Step 3: Update MapStatsController**

Replace `app/Http/Controllers/MapStatsController.php` with:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapStatsController extends Controller
{
    public function show(int $fuelTypeId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_stats_{$fuelTypeId}", 600, function () use ($fuelTypeId) {
            $row = DB::table('prices')
                ->join('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->selectRaw('fuel_types.name as fuel_type_name, MIN(prices.price) as min_price, MAX(prices.price) as max_price, COUNT(DISTINCT prices.site_id) as site_count')
                ->groupBy('fuel_types.name')
                ->first();

            if (! $row) {
                return ['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => ''];
            }

            return [
                'min'            => round((float) $row->min_price / 100, 3),
                'max'            => round((float) $row->max_price / 100, 3),
                'count'          => (int) $row->site_count,
                'fuel_type_name' => $row->fuel_type_name,
            ];
        });

        $data['last_checked_at'] = DB::table('settings')
            ->where('key', 'last_prices_checked_at')
            ->value('value');

        return response()->json($data);
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/MapStatsControllerTest.php
```

Expected: all 3 tests PASS.

- [ ] **Step 5: Run full test suite to check for regressions**

```bash
./vendor/bin/pest
```

Expected: all tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/MapStatsController.php tests/Feature/MapStatsControllerTest.php
git commit -m "feat: expose last_checked_at from settings table in map stats response"
```

---

## Task 3: ETL writes `last_prices_checked_at`

**Files:**
- Modify: `airflow/dags/qldfuelapi_to_sqlite_etl.py` — `load_fuel_prices` function

- [ ] **Step 1: Update `load_fuel_prices` to write the timestamp**

In `airflow/dags/qldfuelapi_to_sqlite_etl.py`, find the `load_fuel_prices` function and add one SQL statement at the end of the `with engine.begin() as conn:` block. The updated function:

```python
def load_fuel_prices():
    engine = _engine()
    with engine.begin() as conn:
        # Archive current prices that are being superseded by a newer incoming price.
        conn.execute(text("""
            INSERT INTO historical_site_prices
                (site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at)
            SELECT p.site_id, p.fuel_id, p.collection_method, p.transaction_date_utc, p.price, p.created_at, p.updated_at
            FROM prices p
            JOIN temp_prices tp ON tp.site_id = p.site_id AND tp.fuel_id = p.fuel_id
            WHERE tp.transaction_date_utc > p.transaction_date_utc
        """))

        # Delete the now-archived (superseded) prices from the current prices table.
        conn.execute(text("""
            DELETE FROM prices
            WHERE id IN (
                SELECT p.id
                FROM prices p
                JOIN temp_prices tp ON tp.site_id = p.site_id AND tp.fuel_id = p.fuel_id
                WHERE tp.transaction_date_utc > p.transaction_date_utc
            )
        """))

        # Insert new prices for: (a) updated pairs just deleted above, and (b) brand-new pairs.
        # Pairs with the same date as the existing price are ignored (they remain unchanged).
        conn.execute(text("""
            INSERT INTO prices
                (site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at)
            SELECT site_id, fuel_id, collection_method, transaction_date_utc, price, created_at, updated_at
            FROM temp_prices
            WHERE (site_id, fuel_id) NOT IN (SELECT site_id, fuel_id FROM prices)
        """))

        # Record the timestamp of this ETL run for display in the UI.
        conn.execute(text("""
            INSERT OR REPLACE INTO settings (key, value)
            VALUES ('last_prices_checked_at', datetime('now'))
        """))
```

- [ ] **Step 2: Verify the ETL change manually**

If Airflow is running locally (`docker compose up` from `/airflow`), trigger the DAG manually via the Airflow UI and then confirm the settings row was written:

```bash
sqlite3 database/database.sqlite "SELECT key, value FROM settings;"
```

Expected output:
```
last_prices_checked_at|2026-04-16 XX:XX:XX
```

If Airflow isn't running, you can test the function in isolation:

```bash
cd airflow
python3 -c "
import sys; sys.path.insert(0, 'dags')
from qldfuelapi_to_sqlite_etl import _engine
from sqlalchemy import text
engine = _engine()
with engine.begin() as conn:
    conn.execute(text(\"INSERT OR REPLACE INTO settings (key, value) VALUES ('last_prices_checked_at', datetime('now'))\"))
    row = conn.execute(text(\"SELECT value FROM settings WHERE key = 'last_prices_checked_at'\")).fetchone()
    print('Written:', row[0])
"
```

Expected: prints `Written: 2026-04-16 XX:XX:XX`

- [ ] **Step 3: Commit**

```bash
git add airflow/dags/qldfuelapi_to_sqlite_etl.py
git commit -m "feat: write last_prices_checked_at to settings table after each ETL price load"
```

---

## Task 4: Frontend — two timestamp lines in info window

**Files:**
- Modify: `resources/views/livewire/fuel-map.blade.php`

- [ ] **Step 1: Declare `globalLastChecked` with the other global vars**

In the `@script` block, find the section that begins:

```javascript
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
    let currentFuelTypeName = '';
    let globalMin = 0, globalMax = 0;
    let currentFuelTypeId = parseInt($wire.selectedFuelTypeId);
```

Add `globalLastChecked` on the line after `globalMin`/`globalMax`:

```javascript
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
    let currentFuelTypeName = '';
    let globalMin = 0, globalMax = 0, globalLastChecked = null;
    let currentFuelTypeId = parseInt($wire.selectedFuelTypeId);
```

- [ ] **Step 2: Populate `globalLastChecked` from stats — idle handler**

Find the block inside `map.addListener('idle', async () => {` that reads:

```javascript
            if (!statsCache[currentFuelTypeId]) {
                const stats = await fetchStats(currentFuelTypeId);
                globalMin           = stats.min;
                globalMax           = stats.max;
                currentFuelTypeName = stats.fuel_type_name;
                updateStatsPills(stats);
            }
```

Replace it with:

```javascript
            if (!statsCache[currentFuelTypeId]) {
                const stats = await fetchStats(currentFuelTypeId);
                globalMin           = stats.min;
                globalMax           = stats.max;
                currentFuelTypeName = stats.fuel_type_name;
                globalLastChecked   = stats.last_checked_at ?? null;
                updateStatsPills(stats);
            }
```

- [ ] **Step 3: Populate `globalLastChecked` from stats — fuel type change handler**

Find the block inside `$wire.on('fuelTypeChanged', async ({ fuelTypeId }) => {` that reads:

```javascript
        const stats = await fetchStats(currentFuelTypeId);
        globalMin           = stats.min;
        globalMax           = stats.max;
        currentFuelTypeName = stats.fuel_type_name;
        updateStatsPills(stats);
```

Replace it with:

```javascript
        const stats = await fetchStats(currentFuelTypeId);
        globalMin           = stats.min;
        globalMax           = stats.max;
        currentFuelTypeName = stats.fuel_type_name;
        globalLastChecked   = stats.last_checked_at ?? null;
        updateStatsPills(stats);
```

- [ ] **Step 4: Update `openInfoWindow` to render two timestamp lines**

Find the `priceSection` declaration inside `openInfoWindow`:

```javascript
        const priceSection = site.price
            ? `<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                   <span style="font-size:9px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.08em;background:#eef2ff;padding:2px 8px;border-radius:6px">${currentFuelTypeName}</span>
                   <span style="font-size:10px;color:#94a3b8">Updated ${formatUpdated(site.updated)}</span>
               </div>
               <div style="display:flex;align-items:baseline;gap:3px">
                   <span style="font-size:36px;font-weight:900;color:${color};line-height:1;letter-spacing:-1px">${(site.price * 100).toFixed(1)}</span>
                   <span style="font-size:13px;color:#94a3b8;font-weight:600">/L</span>
               </div>`
            : `<div style="font-size:13px;color:#94a3b8;font-style:italic;padding:4px 0">No ${currentFuelTypeName} price available</div>`;
```

Replace it with:

```javascript
        const checkedLine = globalLastChecked
            ? `<span style="font-size:10px;color:#94a3b8">Last checked ${formatUpdated(globalLastChecked)}</span>`
            : '';

        const priceSection = site.price
            ? `<div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:5px">
                   <span style="font-size:9px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.08em;background:#eef2ff;padding:2px 8px;border-radius:6px">${currentFuelTypeName}</span>
                   <div style="display:flex;flex-direction:column;align-items:flex-end;gap:2px">
                       <span style="font-size:10px;color:#94a3b8">${currentFuelTypeName} price last changed ${formatUpdated(site.updated)}</span>
                       ${checkedLine}
                   </div>
               </div>
               <div style="display:flex;align-items:baseline;gap:3px">
                   <span style="font-size:36px;font-weight:900;color:${color};line-height:1;letter-spacing:-1px">${(site.price * 100).toFixed(1)}</span>
                   <span style="font-size:13px;color:#94a3b8;font-weight:600">/L</span>
               </div>`
            : `<div style="font-size:13px;color:#94a3b8;font-style:italic;padding:4px 0">No ${currentFuelTypeName} price available</div>`;
```

- [ ] **Step 5: Build assets and verify in browser**

```bash
npm run build
php artisan serve --port=7025
```

Open the map, zoom in, click a fuel site marker. Verify the info window shows:
- The fuel type badge (indigo, unchanged)
- "Unleaded price last changed X ago" (or whichever fuel type is selected)
- "Last checked X ago" (populated once the ETL has written to `settings` — if running locally without Airflow, you can manually insert a row: `sqlite3 database/database.sqlite "INSERT OR REPLACE INTO settings (key, value) VALUES ('last_prices_checked_at', datetime('now'));"`)

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/fuel-map.blade.php
git commit -m "feat: show fuel type price last changed and last checked timestamps in map info window"
```
