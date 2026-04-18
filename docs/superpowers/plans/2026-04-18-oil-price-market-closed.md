# Oil Price Market Closed State — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Live/Market Closed badge to the Global Oil Prices chart header and stop the chart flatlineing over weekends.

**Architecture:** The `/oil-prices` JSON endpoint gains a `market_open` boolean (computed outside the cache, based on current Brisbane day-of-week). The dashboard blade reads that flag after fetch and injects the appropriate badge; `spanGaps` is changed to `false` so the line stops cleanly at the last data point instead of bridging the weekend gap.

**Tech Stack:** Laravel 11, Pest, Carbon, Chart.js, Tailwind CSS

---

## File Map

| File | Change |
|------|--------|
| `app/Http/Controllers/OilPriceController.php` | Append `market_open` boolean after cache block |
| `tests/Feature/OilPriceControllerTest.php` | Update existing assertions; add weekend day-of-week tests |
| `resources/views/livewire/dashboard.blade.php` | Add `#oilStatusBadge` div; inject badge in `initOilChart()`; `spanGaps: false` |

---

### Task 1: Add `market_open` to the oil-prices API response

**Files:**
- Modify: `app/Http/Controllers/OilPriceController.php`
- Modify: `tests/Feature/OilPriceControllerTest.php`

- [ ] **Step 1: Update existing tests to assert `market_open` is present**

Open `tests/Feature/OilPriceControllerTest.php`. Replace the two `assertJsonStructure` calls so both tests also assert the new key exists and is a boolean:

```php
<?php

use Illuminate\Support\Facades\Cache;

test('oil prices endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => ['2026-04-01 00:00:00', '2026-04-01 00:20:00'],
            'series' => [
                'WTI_USD'          => [74.52, 75.10],
                'BRENT_CRUDE_USD'  => [78.30, 79.05],
                'NATURAL_GAS_USD'  => [2.45,  2.50],
                'GASOLINE_USD'     => [2.35,  2.40],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonStructure(['dates', 'series', 'market_open'])
         ->assertJsonStructure(['series' => [
             'WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD',
         ]])
         ->assertJsonPath('series.WTI_USD.0', 74.52)
         ->assertJsonIsBoolean('market_open');
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
         ->assertJsonPath('dates', [])
         ->assertJsonIsBoolean('market_open');
});

test('market_open is false on Saturday in Brisbane time', function () {
    // Saturday 2026-04-18 12:00 AEST = 2026-04-18 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-18 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', false);

    \Carbon\Carbon::setTestNow();
});

test('market_open is false on Sunday in Brisbane time', function () {
    // Sunday 2026-04-19 12:00 AEST = 2026-04-19 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-19 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', false);

    \Carbon\Carbon::setTestNow();
});

test('market_open is true on a weekday in Brisbane time', function () {
    // Monday 2026-04-14 12:00 AEST = 2026-04-14 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-14 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', true);

    \Carbon\Carbon::setTestNow();
});
```

- [ ] **Step 2: Run tests — expect failures**

```bash
./vendor/bin/pest tests/Feature/OilPriceControllerTest.php
```

Expected: failures on the `market_open` assertions (key missing from response).

- [ ] **Step 3: Add `market_open` to the controller**

Open `app/Http/Controllers/OilPriceController.php`. Replace the `return response()->json($data);` line so the method appends the flag after the cache block:

```php
public function index(): JsonResponse
{
    $data = Cache::store('file')->remember('oil_prices_chart', 300, function () {
        $codes = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD'];

        $isPgsql  = DB::connection()->getDriverName() === 'pgsql';
        $bucket   = $isPgsql
            ? "to_char(date_trunc('hour', (recorded_at AT TIME ZONE 'UTC') AT TIME ZONE 'Australia/Brisbane') + floor(date_part('minute', (recorded_at AT TIME ZONE 'UTC') AT TIME ZONE 'Australia/Brisbane') / 30) * interval '30 min', 'YYYY-MM-DD HH24:MI')"
            : "strftime('%Y-%m-%d %H:', datetime(recorded_at, '+10 hours')) || printf('%02d', (cast(strftime('%M', datetime(recorded_at, '+10 hours')) as integer) / 30) * 30)";

        $rows = DB::table('oil_prices')
            ->whereIn('code', $codes)
            ->whereRaw('recorded_at >= ?', [now()->utc()->subHours(72)])
            ->selectRaw("code, {$bucket} as bucket, ROUND(AVG(price), 2) as avg_price")
            ->groupBy('code', DB::raw($bucket))
            ->orderBy('bucket')
            ->get();

        $dates = $rows->pluck('bucket')->unique()->sort()->values()->toArray();

        $series = [];
        foreach ($codes as $code) {
            $byBucket      = $rows->where('code', $code)->keyBy('bucket');
            $series[$code] = array_map(
                fn($b) => ($r = $byBucket->get($b)) ? (float) $r->avg_price : null,
                $dates
            );
        }

        return compact('dates', 'series');
    });

    $day = (int) now()->setTimezone('Australia/Brisbane')->dayOfWeek;
    $data['market_open'] = !in_array($day, [0, 6]); // 0 = Sunday, 6 = Saturday

    return response()->json($data);
}
```

- [ ] **Step 4: Run tests — expect all pass**

```bash
./vendor/bin/pest tests/Feature/OilPriceControllerTest.php
```

Expected: all 5 tests pass.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/OilPriceController.php tests/Feature/OilPriceControllerTest.php
git commit -m "feat: add market_open flag to oil-prices endpoint"
```

---

### Task 2: Add Live/Market Closed badge and fix spanGaps in the dashboard blade

**Files:**
- Modify: `resources/views/livewire/dashboard.blade.php`

- [ ] **Step 1: Add the badge placeholder to the oil chart header**

In `resources/views/livewire/dashboard.blade.php`, find the Global Oil Prices card header block (around line 243–249):

```html
<div class="flex items-start justify-between mb-4">
    <div>
        <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold tracking-tight">Global Oil Prices</h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">USD — last 72 hours</p>
    </div>
</div>
```

Replace it with:

```html
<div class="flex items-start justify-between mb-4">
    <div>
        <h2 class="text-slate-900 dark:text-slate-100 text-xl font-bold tracking-tight">Global Oil Prices</h2>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">USD — last 72 hours</p>
    </div>
    <div id="oilStatusBadge"></div>
</div>
```

- [ ] **Step 2: Inject the badge in `initOilChart()` after the fetch**

In the same file, find the `initOilChart()` function. After the early-return guard for empty data (around line 566–570), add badge injection. The full `initOilChart()` function should look like this:

```javascript
async function initOilChart() {
    let data;
    try {
        const resp = await fetch('/oil-prices');
        data = await resp.json();
    } catch (e) {
        document.getElementById('chartOilPrices').closest('.relative').classList.add('hidden');
        document.getElementById('oilPricesEmpty').classList.remove('hidden');
        return;
    }

    // Render Live / Market Closed badge
    const badgeEl = document.getElementById('oilStatusBadge');
    if (badgeEl) {
        if (data.market_open) {
            badgeEl.innerHTML = `
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
                    </span>
                    <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Live</span>
                </div>`;
        } else {
            badgeEl.innerHTML = `
                <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
                    <span class="relative flex h-2 w-2">
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
                    </span>
                    <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Market Closed</span>
                </div>`;
        }
    }

    if (!data.dates || data.dates.length === 0) {
        document.getElementById('chartOilPrices').closest('.relative').classList.add('hidden');
        document.getElementById('oilPricesEmpty').classList.remove('hidden');
        return;
    }

    const canvas = document.getElementById('chartOilPrices');
    const ctx    = canvas.getContext('2d');

    const t = getChartTheme();
    oilChartRef = new Chart(ctx, {
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
                    backgroundColor: t.tooltipBg,
                    titleColor:      t.tooltipTitle,
                    bodyColor:       t.tooltipBody,
                    borderColor:     'rgba(99,102,241,0.35)',
                    borderWidth:     1,
                    padding:         14,
                    cornerRadius:    12,
                    callbacks: {
                        label: c => ` ${c.dataset.label}: $${c.parsed.y !== null ? c.parsed.y.toFixed(2) : '—'}`,
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxTicksLimit: 8,
                        color: t.tickColor,
                        font: { size: 11 },
                        callback: function(v) {
                            const label = this.getLabelForValue(v);
                            const d = new Date(label);
                            return isNaN(d.getTime()) ? label : d.toLocaleString('en-AU', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false });
                        },
                    },
                    grid: { display: false },
                },
                y: {
                    ticks: {
                        color: t.tickColor,
                        font:  { size: 11 },
                        callback: v => `$${(+v).toFixed(2)}`,
                    },
                    grid: { color: t.gridColor },
                },
            },
        },
    });

    oilToggleBtns = document.querySelectorAll('#oilToggles .oil-toggle');
    oilToggleBtns.forEach(btn => {
        applyOilToggleStyle(btn, btn.dataset.code === OIL_ACTIVE_CODE);

        btn.addEventListener('click', () => {
            if (OIL_ACTIVE_CODE === btn.dataset.code) return;
            OIL_ACTIVE_CODE = btn.dataset.code;
            const activeLabel = OIL_LABELS[OIL_ACTIVE_CODE] ?? OIL_ACTIVE_CODE;
            oilChartRef.data.datasets.forEach(d => { d.hidden = d.label !== activeLabel; });
            oilChartRef.update();
            oilToggleBtns.forEach(b => applyOilToggleStyle(b, b.dataset.code === OIL_ACTIVE_CODE));
        });
    });
}
```

- [ ] **Step 3: Change `spanGaps` to `false` in `buildOilDatasets()`**

In the same file, find the `buildOilDatasets()` function (around line 540–553). Change `spanGaps: true` to `spanGaps: false`:

```javascript
function buildOilDatasets(series) {
    return Object.entries(series).map(([code, values]) => ({
        label:           OIL_LABELS[code] ?? code,
        data:            values,
        borderColor:     OIL_COLOURS[code],
        backgroundColor: OIL_COLOURS[code] + '1a',
        borderWidth:     2,
        pointRadius:     0,
        tension:         0,
        fill:            false,
        spanGaps:        false,
        hidden:          code !== OIL_ACTIVE_CODE,
    }));
}
```

- [ ] **Step 4: Run the full test suite to check for regressions**

```bash
./vendor/bin/pest
```

Expected: all tests pass.

- [ ] **Step 5: Start the dev server and verify visually**

```bash
php artisan serve --port=7025
```

Open `http://localhost:7025` in a browser. In the Global Oil Prices card:
- The header should show a badge ("Live" with green pulsing dot on a weekday, or "Market Closed" with amber dot on a weekend)
- The chart line should not flatline — it stops at the last real data point

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/dashboard.blade.php
git commit -m "feat: add Live/Market Closed badge to oil chart and fix weekend gap rendering"
```
