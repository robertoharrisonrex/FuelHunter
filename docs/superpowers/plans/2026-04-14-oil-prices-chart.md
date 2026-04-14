# Oil Prices Chart Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Global Oil Prices line chart card to the dashboard that displays daily-average WTI, Brent Crude, Natural Gas, and Gasoline prices fetched from the OilPrice API every 20 minutes via Airflow.

**Architecture:** A new `oil_prices` table stores one row per commodity per API poll; an Airflow DAG fetches the four commodity codes from `GET https://api.oilpriceapi.com/v1/prices/latest` every 20 minutes and inserts with deduplication. A Laravel controller aggregates to daily averages and returns JSON; the dashboard blade fetches it client-side and renders a Chart.js line chart with per-series toggle buttons.

**Tech Stack:** SQLite / PostgreSQL, Laravel 11, Livewire 3, Chart.js 4.4.4, Python 3 / Airflow, OilPrice API v1 (`Authorization: Token <key>`).

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `database/migrations/2026_04_14_200000_create_oil_prices_table.php` | Create | `oil_prices` schema with unique index |
| `airflow/dags/oilprice_etl.py` | Create | Airflow DAG — fetch 4 codes, insert into DB |
| `app/Http/Controllers/OilPriceController.php` | Create | `GET /oil-prices` → daily-avg JSON |
| `routes/web.php` | Modify | Register `/oil-prices` route |
| `tests/Feature/OilPriceControllerTest.php` | Create | Feature tests for the controller |
| `resources/views/livewire/dashboard.blade.php` | Modify | Add chart card (HTML + JS) |

No changes to `app/Livewire/Dashboard.php` — chart data is fetched client-side.

---

## Setup Note — Airflow Variable

Before the ETL will run you must add the API token as an Airflow Variable (separate from `.env`):

1. Open Airflow UI → Admin → Variables
2. Add key `OILPRICE_API_TOKEN`, value = your token from `.env`

---

## Task 1: Migration — create oil_prices table

**Files:**
- Create: `database/migrations/2026_04_14_200000_create_oil_prices_table.php`

- [ ] **Step 1: Create the migration file**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_prices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);           // WTI_USD, BRENT_CRUDE_USD, etc.
            $table->decimal('price', 10, 4);       // e.g. 74.5200
            $table->string('currency', 10)->default('USD');
            $table->timestamp('recorded_at');      // API's created_at value
            $table->timestamps();

            $table->unique(['code', 'recorded_at']);        // dedup guard
            $table->index(['code', 'recorded_at']);         // time-series query
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_prices');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
php artisan migrate
```

Expected output: `Running migrations … 2026_04_14_200000_create_oil_prices_table ……… DONE`

- [ ] **Step 3: Commit**

```bash
git add database/migrations/2026_04_14_200000_create_oil_prices_table.php
git commit -m "feat: add oil_prices migration"
```

---

## Task 2: Airflow ETL — oilprice_etl.py

**Files:**
- Create: `airflow/dags/oilprice_etl.py`

The DAG calls `GET https://api.oilpriceapi.com/v1/prices/latest?by_code=<CODE>` once per commodity code (4 requests total), then inserts the results. It uses `INSERT OR IGNORE` on SQLite and `ON CONFLICT DO NOTHING` on PostgreSQL — deduplication is keyed on `(code, recorded_at)`.

- [ ] **Step 1: Create the DAG file**

```python
# airflow/dags/oilprice_etl.py
from datetime import datetime, timedelta
import os

import requests
from airflow import DAG
from airflow.models import Variable
from airflow.operators.python import PythonOperator
from sqlalchemy import create_engine, text

COMMODITY_CODES = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD']


def _engine():
    url = os.environ.get('FUELDB_URL', 'sqlite:////opt/airflow/database/database.sqlite')
    return create_engine(url)


def fetch_and_store_oil_prices(**context):
    api_token = Variable.get('OILPRICE_API_TOKEN')
    headers = {
        'Authorization': f'Token {api_token}',
        'Accept': 'application/json',
    }

    engine = _engine()
    driver = engine.dialect.name  # 'sqlite' or 'postgresql'

    rows = []
    for code in COMMODITY_CODES:
        resp = requests.get(
            'https://api.oilpriceapi.com/v1/prices/latest',
            headers=headers,
            params={'by_code': code},
            timeout=10,
        )
        resp.raise_for_status()
        data = resp.json()['data']
        rows.append({
            'code':        data['code'],
            'price':       float(data['price']),
            'currency':    data.get('currency', 'USD'),
            'recorded_at': data['created_at'],
        })

    if driver == 'sqlite':
        insert_sql = """
            INSERT OR IGNORE INTO oil_prices (code, price, currency, recorded_at, created_at, updated_at)
            VALUES (:code, :price, :currency, :recorded_at, datetime('now'), datetime('now'))
        """
    else:
        insert_sql = """
            INSERT INTO oil_prices (code, price, currency, recorded_at, created_at, updated_at)
            VALUES (:code, :price, :currency, :recorded_at, now(), now())
            ON CONFLICT (code, recorded_at) DO NOTHING
        """

    with engine.connect() as conn:
        for row in rows:
            conn.execute(text(insert_sql), row)
        conn.commit()


dag = DAG(
    dag_id='oilprice_etl',
    start_date=datetime(2026, 4, 14),
    schedule_interval=timedelta(minutes=20),
    catchup=False,
    default_args={'owner': 'Roberto', 'email': ['roberto@boffincentral.com']},
)

fetch_task = PythonOperator(
    task_id='fetch_and_store_oil_prices',
    python_callable=fetch_and_store_oil_prices,
    dag=dag,
)
```

- [ ] **Step 2: Verify the DAG loads (no import errors)**

In the Airflow container:

```bash
docker compose exec airflow-scheduler airflow dags list | grep oilprice
```

Expected: `oilprice_etl` appears in the list.

- [ ] **Step 3: Trigger a manual run to confirm inserts work**

```bash
docker compose exec airflow-scheduler airflow dags trigger oilprice_etl
```

Then check the DB:

```bash
php artisan tinker --execute="dump(DB::table('oil_prices')->count(), DB::table('oil_prices')->get(['code','price','recorded_at']));"
```

Expected: 4 rows (one per commodity code).

- [ ] **Step 4: Commit**

```bash
git add airflow/dags/oilprice_etl.py
git commit -m "feat: add oilprice_etl Airflow DAG (20-min schedule)"
```

---

## Task 3: OilPriceController + Route + Tests

**Files:**
- Create: `app/Http/Controllers/OilPriceController.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/OilPriceControllerTest.php`

The endpoint returns daily averages for the last 30 days, grouped by commodity code. Response shape:

```json
{
  "dates":  ["2026-03-15", "2026-03-16"],
  "series": {
    "WTI_USD":         [74.52, 75.10],
    "BRENT_CRUDE_USD": [78.30, 79.05],
    "NATURAL_GAS_USD": [2.45,  2.50],
    "GASOLINE_USD":    [2.35,  2.40]
  }
}
```

`null` fills gaps where a code has no reading for a given date.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Feature/OilPriceControllerTest.php

use Illuminate\Support\Facades\Cache;

test('oil prices endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => ['2026-04-01', '2026-04-02'],
            'series' => [
                'WTI_USD'          => [74.52, 75.10],
                'BRENT_CRUDE_USD'  => [78.30, 79.05],
                'NATURAL_GAS_USD'  => [2.45,  2.50],
                'GASOLINE_USD'     => [2.35,  2.40],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonStructure(['dates', 'series'])
         ->assertJsonStructure(['series' => [
             'WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD',
         ]])
         ->assertJsonPath('series.WTI_USD.0', 74.52);
});

test('oil prices endpoint returns empty data when no prices exist', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => [],
            'series' => [
                'WTI_USD'         => [],
                'BRENT_CRUDE_USD' => [],
                'NATURAL_GAS_USD' => [],
                'GASOLINE_USD'    => [],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('dates', []);
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
./vendor/bin/pest tests/Feature/OilPriceControllerTest.php
```

Expected: both tests fail with `404` (route not registered yet).

- [ ] **Step 3: Create the controller**

```php
<?php
// app/Http/Controllers/OilPriceController.php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OilPriceController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::store('file')->remember('oil_prices_chart', 300, function () {
            $codes  = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD'];
            $cutoff = now()->subDays(30)->toDateString();

            $rows = DB::table('oil_prices')
                ->whereIn('code', $codes)
                ->whereRaw('DATE(recorded_at) >= ?', [$cutoff])
                ->selectRaw('code, DATE(recorded_at) as date, ROUND(AVG(price), 2) as avg_price')
                ->groupBy('code', DB::raw('DATE(recorded_at)'))
                ->orderBy('date')
                ->get();

            $dates = $rows->pluck('date')->unique()->sort()->values()->toArray();

            $series = [];
            foreach ($codes as $code) {
                $byDate       = $rows->where('code', $code)->keyBy('date');
                $series[$code] = array_map(
                    fn($date) => ($r = $byDate->get($date)) ? (float) $r->avg_price : null,
                    $dates
                );
            }

            return compact('dates', 'series');
        });

        return response()->json($data);
    }
}
```

- [ ] **Step 4: Register the route**

In `routes/web.php`, add the import at the top with the other controller imports:

```php
use App\Http\Controllers\OilPriceController;
```

Then add the route after the existing map routes:

```php
Route::get('/oil-prices', [OilPriceController::class, 'index']);
```

- [ ] **Step 5: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/OilPriceControllerTest.php
```

Expected: `2 passed`

- [ ] **Step 6: Run the full suite to confirm no regressions**

```bash
./vendor/bin/pest
```

Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/OilPriceController.php routes/web.php tests/Feature/OilPriceControllerTest.php
git commit -m "feat: add OilPriceController and /oil-prices endpoint"
```

---

## Task 4: Dashboard — Oil Price Chart Card

**Files:**
- Modify: `resources/views/livewire/dashboard.blade.php`

Add a new card after the Brand Market Share card (currently the last card in the `<div class="space-y-5">` wrapper, ending at line 236). Also add the chart JS before the closing `</script>` tag of the `@script` block (currently at line 461).

### 4a — HTML card

- [ ] **Step 1: Insert the card HTML**

Directly after the closing `</div>` of the Brand Market Share card (line 236, just before `</div>` that closes `space-y-5`), insert:

```html
    {{-- ── Global Oil Prices ──────────────────────────────────────── --}}
    <div class="dash-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h2 class="text-slate-900 text-xl font-bold tracking-tight">Global Oil Prices</h2>
                    <p class="text-slate-500 text-sm mt-0.5">Daily average USD — last 30 days</p>
                </div>
            </div>

            {{-- Series toggles --}}
            <div class="flex flex-wrap gap-2 mb-4" id="oilToggles">
                <button data-code="WTI_USD"          class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">WTI Crude</button>
                <button data-code="BRENT_CRUDE_USD"  class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Brent Crude</button>
                <button data-code="NATURAL_GAS_USD"  class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Natural Gas</button>
                <button data-code="GASOLINE_USD"     class="oil-toggle px-3 py-1 rounded-full text-xs font-semibold border transition-colors">Gasoline</button>
            </div>

            <div wire:ignore class="relative h-[200px] sm:h-[300px]">
                <canvas id="chartOilPrices"></canvas>
            </div>
            <p id="oilPricesEmpty" class="hidden text-center text-sm text-slate-400 mt-4">
                No oil price data available yet — check back after the ETL has run.
            </p>
        </div>
    </div>
```

### 4b — JavaScript

- [ ] **Step 2: Insert the chart JS**

Directly before the closing `</script>` tag of the `@script` block (after the `$wire.on('brandShareUpdated', ...)` listener), insert:

```javascript
    // ── Global Oil Prices chart ───────────────────────────────
    const OIL_COLOURS = {
        WTI_USD:         '#f59e0b',
        BRENT_CRUDE_USD: '#3b82f6',
        NATURAL_GAS_USD: '#10b981',
        GASOLINE_USD:    '#8b5cf6',
    };
    const OIL_LABELS = {
        WTI_USD:         'WTI Crude',
        BRENT_CRUDE_USD: 'Brent Crude',
        NATURAL_GAS_USD: 'Natural Gas',
        GASOLINE_USD:    'Gasoline',
    };
    // Which codes are visible on first load
    const OIL_DEFAULT_VISIBLE = new Set(['WTI_USD']);

    function buildOilDatasets(series) {
        return Object.entries(series).map(([code, values]) => ({
            label:           OIL_LABELS[code] ?? code,
            data:            values,
            borderColor:     OIL_COLOURS[code],
            backgroundColor: OIL_COLOURS[code] + '1a',
            borderWidth:     2,
            pointRadius:     0,
            tension:         0.3,
            fill:            false,
            hidden:          !OIL_DEFAULT_VISIBLE.has(code),
        }));
    }

    async function initOilChart() {
        let data;
        try {
            const resp = await fetch('/oil-prices');
            data = await resp.json();
        } catch (e) {
            document.getElementById('oilPricesEmpty').classList.remove('hidden');
            return;
        }

        if (!data.dates || data.dates.length === 0) {
            document.getElementById('oilPricesEmpty').classList.remove('hidden');
            return;
        }

        const canvas = document.getElementById('chartOilPrices');
        const ctx    = canvas.getContext('2d');

        const oilChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels:   data.dates,
                datasets: buildOilDatasets(data.series),
            },
            options: {
                responsive:          true,
                maintainAspectRatio: false,
                animation:           { duration: 600 },
                interaction:         { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(2,6,23,0.94)',
                        titleColor:      '#e2e8f0',
                        bodyColor:       '#94a3b8',
                        borderColor:     'rgba(99,102,241,0.35)',
                        borderWidth:     1,
                        padding:         14,
                        cornerRadius:    12,
                        callbacks: {
                            label: c => ` ${c.dataset.label}: $${c.parsed.y != null ? c.parsed.y.toFixed(2) : '—'}`,
                        },
                    },
                },
                scales: {
                    x: {
                        ticks: { maxTicksLimit: 8, color: '#94a3b8', font: { size: 11 } },
                        grid:  { display: false },
                    },
                    y: {
                        ticks: {
                            color: '#94a3b8',
                            font:  { size: 11 },
                            callback: v => `$${v}`,
                        },
                        grid: { color: '#f1f5f9' },
                    },
                },
            },
        });

        // Wire up toggle buttons
        document.querySelectorAll('#oilToggles .oil-toggle').forEach(btn => {
            const code   = btn.dataset.code;
            const colour = OIL_COLOURS[code];
            const label  = OIL_LABELS[code] ?? code;

            function applyStyle(active) {
                btn.style.cssText = active
                    ? `background:${colour}1a;border-color:${colour};color:${colour}`
                    : 'background:#f8fafc;border-color:#e2e8f0;color:#94a3b8';
            }

            applyStyle(OIL_DEFAULT_VISIBLE.has(code));

            btn.addEventListener('click', () => {
                const dataset = oilChart.data.datasets.find(d => d.label === label);
                if (!dataset) return;
                dataset.hidden = !dataset.hidden;
                oilChart.update();
                applyStyle(!dataset.hidden);
            });
        });
    }

    initOilChart();
```

- [ ] **Step 3: Start the dev server and verify the card renders**

```bash
php artisan serve --port=7025
```

Open `http://127.0.0.1:7025/dashboard`. You should see the "Global Oil Prices" card at the bottom. Because the ETL hasn't run yet the empty-state message will show: `"No oil price data available yet — check back after the ETL has run."`

- [ ] **Step 4: Seed a few rows manually to verify the chart renders**

```bash
php artisan tinker --execute="
\$now = now();
foreach (['WTI_USD','BRENT_CRUDE_USD','NATURAL_GAS_USD','GASOLINE_USD'] as \$i => \$code) {
    foreach (range(0, 29) as \$d) {
        DB::table('oil_prices')->insertOrIgnore([
            'code'        => \$code,
            'price'       => round(50 + \$i * 20 + rand(-200, 200) / 100, 4),
            'currency'    => 'USD',
            'recorded_at' => now()->subDays(\$d)->toDateTimeString(),
            'created_at'  => \$now,
            'updated_at'  => \$now,
        ]);
    }
}
echo 'seeded';
"
```

Refresh the dashboard — the chart should render with an amber WTI Crude line. Clicking the other toggle buttons should show/hide Brent Crude, Natural Gas, and Gasoline lines.

- [ ] **Step 5: Run full test suite**

```bash
./vendor/bin/pest
```

Expected: all tests pass (the blade change has no PHP test surface — chart rendering is verified manually above).

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/dashboard.blade.php
git commit -m "feat: add Global Oil Prices chart card to dashboard"
```

---

## Self-Review

**Spec coverage:**
- ✅ Migration for `oil_prices` table — Task 1
- ✅ Airflow ETL every 20 minutes — Task 2
- ✅ Fetches WTI_USD, BRENT_CRUDE_USD, NATURAL_GAS_USD, GASOLINE_USD — Task 2
- ✅ Uses `OILPRICE_API_TOKEN` Airflow Variable — Task 2
- ✅ API endpoint for chart data — Task 3
- ✅ Tests for the endpoint — Task 3
- ✅ Chart card on dashboard — Task 4
- ✅ WTI_USD visible by default — Task 4 (`OIL_DEFAULT_VISIBLE`)
- ✅ Toggles for other three commodities — Task 4
- ✅ Empty state when no data yet — Task 4

**Type / naming consistency:**
- `buildOilDatasets(series)` defined in Step 2, called in Step 2 ✅
- `initOilChart()` defined and called in Step 2 ✅
- `applyStyle(active)` defined and called in same closure ✅
- Controller returns `compact('dates', 'series')` — JS reads `data.dates` / `data.series` ✅
- `OIL_DEFAULT_VISIBLE`, `OIL_COLOURS`, `OIL_LABELS` all defined before use ✅
