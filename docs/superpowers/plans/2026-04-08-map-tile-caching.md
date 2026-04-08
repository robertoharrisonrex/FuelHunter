# Map Tile Caching Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the all-sites-at-once map loading with a geospatial 0.5°×0.5° tile system that fetches only sites visible in the current viewport, with per-fuel-type JS session caching for instant re-panning.

**Architecture:** Two new JSON endpoints serve map stats (`/map-stats/{fuelTypeId}`) and per-tile site data (`/map-tiles/{fuelTypeId}/{latTile}/{lngTile}`). The Google Maps `idle` event drives tile fetching; tiles are cached in JS per fuel type for the session. A zoom guard (MIN_ZOOM=11) prevents loading when zoomed out too far.

**Tech Stack:** Laravel 11, Pest (PHP tests), Google Maps JS API v3 (AdvancedMarkerElement), Livewire 3

---

## File Map

| File | Action |
|------|--------|
| `routes/web.php` | Add 2 new routes + use statements |
| `app/Http/Controllers/MapStatsController.php` | Create |
| `app/Http/Controllers/MapTileController.php` | Create |
| `tests/Feature/MapStatsControllerTest.php` | Create |
| `tests/Feature/MapTileControllerTest.php` | Create |
| `resources/views/livewire/fuel-map.blade.php` | Modify: add overlay HTML + replace @script block |

---

### Task 1: MapStatsController

Returns `{ min, max, count, fuel_type_name }` for a given fuel type. Used by the JS to anchor a consistent global colour scale across all tiles.

**Files:**
- Create: `app/Http/Controllers/MapStatsController.php`
- Create: `tests/Feature/MapStatsControllerTest.php`
- Modify: `routes/web.php` (add use + route)

- [ ] **Step 1.1: Write the failing test**

Create `tests/Feature/MapStatsControllerTest.php`:

```php
<?php

use Illuminate\Support\Facades\Cache;

test('map stats endpoint returns correct json structure', function () {
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
             ->assertJsonStructure(['min', 'max', 'count', 'fuel_type_name'])
             ->assertJsonFragment(['fuel_type_name' => 'Unleaded'])
             ->assertJsonPath('min', 1.699)
             ->assertJsonPath('max', 2.059)
             ->assertJsonPath('count', 350);
});

test('map stats returns zeros for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => '']);

    $response = $this->getJson('/map-stats/999');

    $response->assertOk()
             ->assertJsonPath('count', 0)
             ->assertJsonPath('fuel_type_name', '');
});
```

- [ ] **Step 1.2: Run test to verify it fails**

```bash
cd /Users/roberto/Projects/FuelHunter/FuelHunter
./vendor/bin/pest tests/Feature/MapStatsControllerTest.php -v
```

Expected: FAIL with `Expected response status code [200] but received [404]`

- [ ] **Step 1.3: Create MapStatsController**

Create `app/Http/Controllers/MapStatsController.php`:

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

        return response()->json($data);
    }
}
```

- [ ] **Step 1.4: Register the route**

In `routes/web.php`, add the import after the existing use statements and the route after `/map-data`:

```php
// Add to the use block at the top:
use App\Http\Controllers\MapStatsController;

// Add after the existing map-data route:
Route::get('/map-stats/{fuelTypeId}', [MapStatsController::class, 'show']);
```

- [ ] **Step 1.5: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/MapStatsControllerTest.php -v
```

Expected: `2 tests passed`

- [ ] **Step 1.6: Commit**

```bash
git add app/Http/Controllers/MapStatsController.php tests/Feature/MapStatsControllerTest.php routes/web.php
git commit -m "feat: add MapStatsController for global fuel price stats endpoint"
```

---

### Task 2: MapTileController

Returns `{ sites: [...] }` for a single 0.5°×0.5° grid tile. `latTile = floor(lat / 0.5)` and `lngTile = floor(lng / 0.5)` are signed integers (negative for Southern Hemisphere). Each tile is cached in PHP file cache independently.

**Files:**
- Create: `app/Http/Controllers/MapTileController.php`
- Create: `tests/Feature/MapTileControllerTest.php`
- Modify: `routes/web.php`

- [ ] **Step 2.1: Write the failing tests**

Create `tests/Feature/MapTileControllerTest.php`:

```php
<?php

use App\Http\Controllers\MapTileController;
use Illuminate\Support\Facades\Cache;

test('map tile endpoint returns sites array', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'sites' => [
                [
                    'id'       => 1,
                    'name'     => 'BP Newmarket',
                    'lat'      => -27.45,
                    'lng'      => 153.01,
                    'addr'     => '123 Test St',
                    'suburb'   => 'Newmarket',
                    'postcode' => 4051,
                    'price'    => 1.899,
                    'updated'  => '2026-04-01T06:00:00',
                    'brand'    => 'BP',
                    'price_ul' => 1.899,
                    'price_95' => null,
                    'price_98' => null,
                    'price_pd' => null,
                ],
            ],
        ]);

    $response = $this->getJson('/map-tiles/2/-55/306');

    $response->assertOk()
             ->assertJsonStructure(['sites'])
             ->assertJsonCount(1, 'sites')
             ->assertJsonPath('sites.0.id', 1)
             ->assertJsonPath('sites.0.brand', 'BP');
});

test('map tile returns empty sites for tile with no stations', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn(['sites' => []]);

    $response = $this->getJson('/map-tiles/2/-58/270');

    $response->assertOk()->assertJsonPath('sites', []);
});

test('tile bounds calculation is correct for southern hemisphere', function () {
    // latTile=-55 → south=-27.5, north=-27.0
    // lngTile=306 → west=153.0, east=153.5
    $bounds = MapTileController::tileBounds(-55, 306);

    expect($bounds['south'])->toBe(-27.5)
        ->and($bounds['north'])->toBe(-27.0)
        ->and($bounds['west'])->toBe(153.0)
        ->and($bounds['east'])->toBe(153.5);
});

test('tile bounds calculation is correct for negative lng tile', function () {
    // lngTile=-1 → west=-0.5, east=0.0
    $bounds = MapTileController::tileBounds(-55, -1);

    expect($bounds['west'])->toBe(-0.5)
        ->and($bounds['east'])->toBe(0.0);
});
```

- [ ] **Step 2.2: Run tests to verify they fail**

```bash
./vendor/bin/pest tests/Feature/MapTileControllerTest.php -v
```

Expected: FAIL with `Expected response status code [200] but received [404]`

- [ ] **Step 2.3: Create MapTileController**

Create `app/Http/Controllers/MapTileController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapTileController extends Controller
{
    public static function tileBounds(int $latTile, int $lngTile): array
    {
        $size = 0.5;

        return [
            'south' => $latTile * $size,
            'north' => ($latTile + 1) * $size,
            'west'  => $lngTile * $size,
            'east'  => ($lngTile + 1) * $size,
        ];
    }

    public function show(int $fuelTypeId, int $latTile, int $lngTile): JsonResponse
    {
        $cacheKey = "map_tile_{$fuelTypeId}_{$latTile}_{$lngTile}";

        $data = Cache::store('file')->remember($cacheKey, 600, function () use ($fuelTypeId, $latTile, $lngTile) {
            $bounds = static::tileBounds($latTile, $lngTile);

            $rows = DB::table('fuel_sites')
                ->join('prices', function ($join) use ($fuelTypeId) {
                    $join->on('prices.site_id', '=', 'fuel_sites.id')
                         ->where('prices.fuel_id', '=', $fuelTypeId)
                         ->where('prices.price', '>', 50);
                })
                ->leftJoin('brands',     'brands.id',     '=', 'fuel_sites.brand_id')
                ->leftJoin('suburbs',    'suburbs.id',    '=', 'fuel_sites.geo_region_1')
                ->leftJoin('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
                ->leftJoin('prices as p_ul', function ($join) {
                    $join->on('p_ul.site_id', '=', 'fuel_sites.id')->where('p_ul.fuel_id', '=', 2);
                })
                ->leftJoin('prices as p95', function ($join) {
                    $join->on('p95.site_id', '=', 'fuel_sites.id')->where('p95.fuel_id', '=', 5);
                })
                ->leftJoin('prices as p98', function ($join) {
                    $join->on('p98.site_id', '=', 'fuel_sites.id')->where('p98.fuel_id', '=', 8);
                })
                ->leftJoin('prices as p_pd', function ($join) {
                    $join->on('p_pd.site_id', '=', 'fuel_sites.id')->where('p_pd.fuel_id', '=', 14);
                })
                ->whereBetween('fuel_sites.latitude',  [$bounds['south'], $bounds['north']])
                ->whereBetween('fuel_sites.longitude', [$bounds['west'],  $bounds['east']])
                ->select(
                    'fuel_sites.id',
                    'fuel_sites.name',
                    'fuel_sites.latitude',
                    'fuel_sites.longitude',
                    'fuel_sites.address',
                    'fuel_sites.postcode',
                    'suburbs.name as suburb_name',
                    'prices.price',
                    'prices.transaction_date_utc',
                    'brands.name as brand_name',
                    'fuel_types.name as fuel_type_name',
                    'p_ul.price as price_ul',
                    'p95.price as price_95',
                    'p98.price as price_98',
                    'p_pd.price as price_pd',
                )
                ->get();

            $sites = $rows->map(fn ($r) => [
                'id'       => $r->id,
                'name'     => $r->name,
                'lat'      => (float) $r->latitude,
                'lng'      => (float) $r->longitude,
                'addr'     => $r->address,
                'suburb'   => $r->suburb_name ?? '',
                'postcode' => $r->postcode,
                'price'    => round($r->price / 100, 3),
                'updated'  => $r->transaction_date_utc,
                'brand'    => $r->brand_name ?? '',
                'price_ul' => $r->price_ul ? round($r->price_ul / 100, 3) : null,
                'price_95' => $r->price_95 ? round($r->price_95 / 100, 3) : null,
                'price_98' => $r->price_98 ? round($r->price_98 / 100, 3) : null,
                'price_pd' => $r->price_pd ? round($r->price_pd / 100, 3) : null,
            ])->values()->toArray();

            return ['sites' => $sites];
        });

        return response()->json($data);
    }
}
```

- [ ] **Step 2.4: Register the tile route**

In `routes/web.php`, add:

```php
// Add to the use block at the top:
use App\Http\Controllers\MapTileController;

// Add after the map-stats route:
Route::get('/map-tiles/{fuelTypeId}/{latTile}/{lngTile}', [MapTileController::class, 'show'])
    ->where(['latTile' => '-?\d+', 'lngTile' => '-?\d+']);
```

- [ ] **Step 2.5: Run tile tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/MapTileControllerTest.php -v
```

Expected: `4 tests passed`

- [ ] **Step 2.6: Run full test suite to confirm no regressions**

```bash
./vendor/bin/pest -v
```

Expected: all tests pass

- [ ] **Step 2.7: Commit**

```bash
git add app/Http/Controllers/MapTileController.php tests/Feature/MapTileControllerTest.php routes/web.php
git commit -m "feat: add MapTileController for viewport-scoped geospatial tile endpoint"
```

---

### Task 3: Add Zoom Overlay HTML

Adds a hidden overlay div inside the map wrapper that the JS will show/hide based on zoom level.

**Files:**
- Modify: `resources/views/livewire/fuel-map.blade.php`

- [ ] **Step 3.1: Insert the overlay div**

In `resources/views/livewire/fuel-map.blade.php`, insert the following between the closing `</div>` of the top control bar (after line 139) and `{{-- ── Map canvas ──}}` (line 142):

```blade
    {{-- ── Zoom overlay ──────────────────────────────────────── --}}
    <div id="zoomOverlay" class="hidden absolute inset-0 z-20 flex items-center justify-center">
        <div class="bg-white/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-slate-200 p-8 text-center max-w-xs mx-4">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607ZM10.5 7.5v6m3-3h-6"/>
                </svg>
            </div>
            <h3 class="text-slate-900 font-bold text-base mb-1">Zoom in to see fuel stations</h3>
            <p class="text-slate-500 text-sm">Zoom in to suburb level to load nearby fuel sites.</p>
        </div>
    </div>
```

- [ ] **Step 3.2: Verify HTML renders**

Open http://192.168.4.23:7025/ and navigate to the map page. Open DevTools → Elements. Confirm `#zoomOverlay` exists with the `hidden` class. The overlay should be invisible on the page.

- [ ] **Step 3.3: Commit**

```bash
git add resources/views/livewire/fuel-map.blade.php
git commit -m "feat: add zoom-level overlay HTML to fuel map"
```

---

### Task 4: Replace Map JS with Tile System

Replaces the entire `@script` block with the tile-based system. Preserves all visual helpers (`makePinEl`, `openInfoWindow`, `priceColor`, etc.) and removes `fetchMapData`, `buildMarkers`, `clearMarkersAsync`, `updateMarkerVisibility`, and the `bounds_changed` listener.

**Files:**
- Modify: `resources/views/livewire/fuel-map.blade.php` (the `@script` … `@endscript` block)

- [ ] **Step 4.1: Replace the entire @script block**

In `resources/views/livewire/fuel-map.blade.php`, replace everything from `@script` through `@endscript` (lines 147–645) with:

```blade
@script
<script>
    // ── Constants ────────────────────────────────────────────
    const TILE_SIZE = 0.5;  // degrees per tile edge
    const MIN_ZOOM  = 11;   // minimum map zoom to show markers

    // ── Session caches (keyed by fuelTypeId) ─────────────────
    const tileCache      = {};  // tileCache[fuelTypeId]['latTile_lngTile'] = Site[]
    const markerRegistry = {};  // markerRegistry[fuelTypeId][siteId] = AdvancedMarkerElement
    const statsCache     = {};  // statsCache[fuelTypeId] = { min, max, count, fuel_type_name }

    // ── Map state ─────────────────────────────────────────────
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
    let currentFuelTypeName = '';
    let globalMin = 0, globalMax = 0;
    let currentFuelTypeId = parseInt($wire.selectedFuelTypeId);

    // ── Brand → favicon URL mapping ──────────────────────────
    const BRAND_LOGOS = {
        '7 Eleven':          'https://www.google.com/s2/favicons?domain=7eleven.com.au&sz=64',
        'Ampol':             'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'EG Ampol':          'https://www.google.com/s2/favicons?domain=ampol.com.au&sz=64',
        'Apco':              'https://www.google.com/s2/favicons?domain=apcostores.com.au&sz=64',
        'BP':                'https://companieslogo.com/img/orig/BP-6284f908.png?t=1770216700',
        'Caltex':            'https://www.google.com/s2/favicons?domain=caltex.com.au&sz=64',
        'Coles Express':     'https://www.google.com/s2/favicons?domain=colesexpress.com.au&sz=64',
        'Costco':            'https://www.google.com/s2/favicons?domain=costco.com.au&sz=64',
        'Freedom Fuels':     'https://www.google.com/s2/favicons?domain=freedomfuels.com.au&sz=64',
        'Gull':              'https://www.google.com/s2/favicons?domain=gull.com.au&sz=64',
        'Liberty':           'https://www.google.com/s2/favicons?domain=libertyoil.com.au&sz=64',
        'Mobil':             'https://www.google.com/s2/favicons?domain=mobil.com.au&sz=64',
        'On the Run':        'https://www.google.com/s2/favicons?domain=ontherun.com.au&sz=64',
        'Puma Energy':       'https://www.google.com/s2/favicons?domain=pumaenergy.com&sz=64',
        'Shell':             'https://www.google.com/s2/favicons?domain=shell.com.au&sz=64',
        'Reddy Express':     'https://www.google.com/s2/favicons?domain=shell.com.au&sz=64',
        'United':            'https://www.google.com/s2/favicons?domain=unitedpetroleum.com.au&sz=64',
        'Vibe':              'https://www.google.com/s2/favicons?domain=vibeenergy.com.au&sz=64',
        'Speedway':          'https://www.google.com/s2/favicons?domain=speedway.com.au&sz=64',
        'Metro Fuel':        'https://www.google.com/s2/favicons?domain=metropetroleum.com.au&sz=64',
        'Budget':            'https://www.google.com/s2/favicons?domain=budgetpetrol.com.au&sz=64',
        'Prime Petroleum':   'https://www.google.com/s2/favicons?domain=primepetroleum.com.au&sz=64',
        'U-Go':              'https://www.google.com/s2/favicons?domain=ugoselfserve.com.au&sz=64',
    };

    // ── Secondary fuel types shown in info window ─────────────
    const SECONDARY_FUELS = [
        { id: 2,  key: 'price_ul', label: 'Unleaded'     },
        { id: 5,  key: 'price_95', label: 'P.ULP 95'     },
        { id: 8,  key: 'price_98', label: 'P.ULP 98'     },
        { id: 14, key: 'price_pd', label: 'Prem. Diesel' },
    ];

    // ── Colour interpolation (green → yellow → red) ──────────
    function priceColor(price, min, max) {
        if (max <= min) return '#6366f1';
        const t = Math.max(0, Math.min(1, (price - min) / (max - min)));
        let r, g;
        if (t < 0.5) {
            const f = t * 2;
            r = Math.round(34 + f * 200);
            g = 197;
        } else {
            const f = (t - 0.5) * 2;
            r = 234;
            g = Math.round(197 - f * 129);
        }
        return `rgb(${r},${g},34)`;
    }

    // ── Logo circle element ───────────────────────────────────
    function makeLogoEl(brandName, color, size = 22) {
        const wrap = document.createElement('div');
        wrap.style.cssText = `
            width:${size}px;height:${size}px;border-radius:999px;
            background:#f8fafc;border:1.5px solid #e2e8f0;
            display:flex;align-items:center;justify-content:center;
            overflow:hidden;flex-shrink:0;
        `;
        const logoUrl = BRAND_LOGOS[brandName];
        if (logoUrl) {
            const img = document.createElement('img');
            img.src = logoUrl;
            const px = Math.round(size * 0.7);
            img.style.cssText = `width:${px}px;height:${px}px;object-fit:contain;`;
            img.onerror = () => {
                wrap.innerHTML = '';
                wrap.textContent = (brandName || '?').charAt(0).toUpperCase();
                wrap.style.background = color;
                wrap.style.border     = 'none';
                wrap.style.color      = '#fff';
                wrap.style.fontSize   = Math.round(size * 0.45) + 'px';
                wrap.style.fontWeight = '800';
                wrap.style.fontFamily = 'system-ui,sans-serif';
            };
            wrap.appendChild(img);
        } else {
            wrap.textContent = (brandName || '?').charAt(0).toUpperCase();
            wrap.style.background = color;
            wrap.style.border     = 'none';
            wrap.style.color      = '#fff';
            wrap.style.fontSize   = Math.round(size * 0.45) + 'px';
            wrap.style.fontWeight = '800';
            wrap.style.fontFamily = 'system-ui,sans-serif';
        }
        return wrap;
    }

    // ── Format price-update timestamp ────────────────────────
    function formatUpdated(dateStr) {
        if (!dateStr) return '—';
        const d      = new Date(dateStr + 'Z');
        const mins   = Math.floor((new Date() - d) / 60000);
        const hours  = Math.floor(mins / 60);
        const days   = Math.floor(hours / 24);
        const weeks  = Math.floor(days / 7);
        const months = Math.floor(days / 30);
        if (days > 28)   return months === 1 ? '1 month ago' : `${months} months ago`;
        if (days > 6)    return weeks  === 1 ? '1 week ago'  : `${weeks} weeks ago`;
        if (hours >= 48) return `${days} days ago`;
        if (hours > 0)   return mins % 60 > 0 ? `${hours}h ${mins % 60}m ago` : `${hours}h ago`;
        return `${mins}m ago`;
    }

    // ── Secondary price pill ──────────────────────────────────
    function pricePill(label, price, color) {
        const val  = price ? (price * 100).toFixed(1) : '—';
        const bold = price ? `color:${color};font-weight:700` : 'color:#cbd5e1;font-weight:500';
        return `<div style="flex:1;background:#f8fafc;border-radius:8px;padding:6px 8px;text-align:center">
                    <div style="font-size:9px;color:#94a3b8;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:2px">${label}</div>
                    <div style="font-size:12px;${bold}">${val}</div>
                </div>`;
    }

    // ── Custom price pin element ──────────────────────────────
    function makePinEl(price, min, max, brandName, highlight = null) {
        const color    = priceColor(price, min, max);
        const pinColor = highlight ? color : '#1e40af';

        const hlBg     = highlight === 'cheapest' ? '#f0fdf4' : highlight === 'priciest' ? '#fef2f2' : null;
        const hlBorder = highlight === 'cheapest' ? '#86efac' : highlight === 'priciest' ? '#fca5a5' : null;
        const hlAccent = highlight === 'cheapest' ? '#16a34a' : highlight === 'priciest' ? '#dc2626' : null;
        const hlText   = highlight === 'cheapest' ? '#15803d' : highlight === 'priciest' ? '#b91c1c'  : null;
        const hlShadow = highlight === 'cheapest'
            ? '0 2px 10px rgba(22,163,74,0.20)'
            : highlight === 'priciest'
                ? '0 2px 10px rgba(220,38,38,0.20)'
                : null;

        const bg         = highlight ? hlBg     : '#ffffff';
        const border     = highlight ? `1px solid ${hlBorder}` : '1px solid #e8edf2';
        const borderLeft = highlight ? `3px solid ${hlAccent}` : `3px solid ${pinColor}`;
        const shadow     = highlight ? hlShadow : '0 1px 3px rgba(0,0,0,0.10),0 2px 8px rgba(0,0,0,0.06)';
        const baseScale  = highlight ? 'scale(1.14)' : 'scale(1)';

        const el = document.createElement('div');
        el.style.cssText = `
            display:flex;align-items:center;gap:5px;
            background:${bg};
            padding:${highlight ? '5px 10px 5px 5px' : '3px 8px 3px 3px'};
            border-radius:10px;
            border:${border};
            border-left:${borderLeft};
            box-shadow:${shadow};
            transform:${baseScale};
            cursor:pointer;
            transition:transform .14s ease,box-shadow .14s ease;
            white-space:nowrap;
        `;

        el.appendChild(makeLogoEl(brandName, color, highlight ? 26 : 22));

        const textWrap = document.createElement('div');
        textWrap.style.cssText = 'display:flex;flex-direction:column;gap:0px;';

        if (highlight) {
            const label = document.createElement('span');
            label.textContent = highlight === 'cheapest' ? '✓ CHEAPEST' : '▲ PRICIEST';
            label.style.cssText = `
                font-size:8px;font-weight:700;color:${hlText};
                font-family:system-ui,sans-serif;letter-spacing:0.07em;
                line-height:1;margin-bottom:1px;
            `;
            textWrap.appendChild(label);
        }

        const priceEl = document.createElement('span');
        priceEl.style.cssText = `
            font-size:${highlight ? '13' : '11'}px;font-weight:800;
            font-family:system-ui,sans-serif;
            color:${highlight ? hlText : pinColor};
            line-height:1;
        `;
        priceEl.textContent = (price * 100).toFixed(1);
        textWrap.appendChild(priceEl);
        el.appendChild(textWrap);

        el.classList.add('fuel-pin');
        el.style.setProperty('--scale-h', highlight ? 'scale(1.26)' : 'scale(1.12)');
        el.style.setProperty('--shadow-h', highlight
            ? hlShadow.replace('0.20', '0.35')
            : '0 3px 12px rgba(0,0,0,0.16),0 4px 16px rgba(0,0,0,0.10)');

        return el;
    }

    // ── Stats pills ───────────────────────────────────────────
    function updateStatsPills(stats) {
        const pills = document.getElementById('mapStatsPills');
        if (!pills) return;
        if (stats.count > 0) {
            document.getElementById('mapStatMin').textContent = (stats.min * 100).toFixed(1);
            document.getElementById('mapStatMax').textContent = (stats.max * 100).toFixed(1);
            pills.style.removeProperty('display');
        } else {
            pills.style.setProperty('display', 'none', 'important');
        }
    }

    // ── Map position persistence ──────────────────────────────
    const MAP_STORAGE_KEY = 'fuelmap_position';

    function saveMapPosition() {
        const c = map.getCenter();
        localStorage.setItem(MAP_STORAGE_KEY, JSON.stringify({
            lat:  c.lat(),
            lng:  c.lng(),
            zoom: map.getZoom(),
        }));
    }

    function loadMapPosition() {
        try {
            const raw = localStorage.getItem(MAP_STORAGE_KEY);
            if (raw) return JSON.parse(raw);
        } catch {}
        return null;
    }

    // ── Open info window ──────────────────────────────────────
    function openInfoWindow(m, site, min, max) {
        if (activeInfoWindow) activeInfoWindow.close();
        if (activeMarker) { activeMarker.zIndex = null; activeMarker = null; }
        m.zIndex     = 2000;
        activeMarker = m;

        const color    = priceColor(site.price, min, max);
        const logoUrl  = BRAND_LOGOS[site.brand];
        const logoHtml = logoUrl
            ? `<img src="${logoUrl}" style="width:36px;height:36px;flex-shrink:0;object-fit:contain;border-radius:8px;border:1px solid #e2e8f0;padding:3px;background:#fff;" onerror="this.style.display='none'">`
            : `<div style="width:36px;height:36px;flex-shrink:0;border-radius:8px;background:${color};display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:800;font-family:system-ui,sans-serif;">${(site.brand||'?').charAt(0).toUpperCase()}</div>`;

        const fullAddr = [site.addr, `${site.suburb} QLD ${site.postcode}`]
            .filter(Boolean).join(', ');

        const iw = new google.maps.InfoWindow({
            maxWidth: 290,
            content: `
            <div style="font-family:system-ui,-apple-system,sans-serif;width:280px;">
                <div style="display:flex;align-items:center;gap:11px;padding:14px 16px 11px;background:#fff;">
                    ${logoHtml}
                    <div style="min-width:0">
                        <div style="font-weight:700;font-size:13px;color:#0f172a;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${site.name}</div>
                        <div style="font-size:10px;color:#94a3b8;font-weight:500;margin-top:1px">${site.brand || ''}</div>
                    </div>
                </div>
                <div style="padding:0 16px 10px;background:#fff;">
                    <div style="font-size:10px;color:#64748b;line-height:1.6">${fullAddr}</div>
                </div>
                <div style="height:1px;background:#f1f5f9"></div>
                <div style="padding:12px 16px 10px;background:#fff;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                        <span style="font-size:9px;font-weight:700;color:#6366f1;text-transform:uppercase;letter-spacing:0.08em;background:#eef2ff;padding:2px 8px;border-radius:6px">${currentFuelTypeName}</span>
                        <span style="font-size:10px;color:#94a3b8">Updated ${formatUpdated(site.updated)}</span>
                    </div>
                    <div style="display:flex;align-items:baseline;gap:3px">
                        <span style="font-size:36px;font-weight:900;color:${color};line-height:1;letter-spacing:-1px">${(site.price * 100).toFixed(1)}</span>
                        <span style="font-size:13px;color:#94a3b8;font-weight:600">/L</span>
                    </div>
                </div>
                <div style="height:1px;background:#f1f5f9"></div>
                <div style="display:flex;gap:4px;padding:10px 12px;background:#fff;">
                    ${SECONDARY_FUELS
                        .filter(f => f.id !== parseInt($wire.selectedFuelTypeId))
                        .map(f => pricePill(f.label, site[f.key], priceColor(site[f.key] || 0, min, max)))
                        .join('')}
                </div>
            </div>`,
        });
        iw.open({ map, anchor: m });
        activeInfoWindow = iw;
    }

    // ── Highlight cheapest & priciest in viewport ─────────────
    function updateHighlights() {
        if (!map || !markerRegistry[currentFuelTypeId]) return;
        const bounds = map.getBounds();
        if (!bounds) return;

        let minMarker = null, maxMarker = null;
        let minPrice = Infinity, maxPrice = -Infinity;

        Object.values(markerRegistry[currentFuelTypeId]).forEach(m => {
            if (!m.map) return;
            if (bounds.contains(m.position)) {
                if (m._site.price < minPrice) { minPrice = m._site.price; minMarker = m; }
                if (m._site.price > maxPrice) { maxPrice = m._site.price; maxMarker = m; }
            }
        });

        if (minMarker === maxMarker) { minMarker = null; maxMarker = null; }

        if (highlightedMin !== minMarker) {
            if (highlightedMin) {
                const s = highlightedMin._site;
                highlightedMin.content = makePinEl(s.price, globalMin, globalMax, s.brand);
                highlightedMin.zIndex  = null;
            }
            highlightedMin = minMarker;
            if (minMarker) {
                const s = minMarker._site;
                minMarker.content = makePinEl(s.price, globalMin, globalMax, s.brand, 'cheapest');
                minMarker.zIndex  = 1000;
            }
        }

        if (highlightedMax !== maxMarker) {
            if (highlightedMax) {
                const s = highlightedMax._site;
                highlightedMax.content = makePinEl(s.price, globalMin, globalMax, s.brand);
                highlightedMax.zIndex  = null;
            }
            highlightedMax = maxMarker;
            if (maxMarker) {
                const s = maxMarker._site;
                maxMarker.content = makePinEl(s.price, globalMin, globalMax, s.brand, 'priciest');
                maxMarker.zIndex  = 999;
            }
        }
    }

    // ── Zoom overlay helpers ──────────────────────────────────
    function showZoomOverlay() {
        document.getElementById('zoomOverlay').classList.remove('hidden');
    }
    function hideZoomOverlay() {
        document.getElementById('zoomOverlay').classList.add('hidden');
    }

    // ── Tile coordinate helpers ───────────────────────────────
    function tileKey(latTile, lngTile) {
        return `${latTile}_${lngTile}`;
    }

    function getTilesForBounds(bounds) {
        const sw = bounds.getSouthWest(), ne = bounds.getNorthEast();
        const tiles = [];
        for (let lat = Math.floor(sw.lat() / TILE_SIZE); lat <= Math.floor(ne.lat() / TILE_SIZE); lat++) {
            for (let lng = Math.floor(sw.lng() / TILE_SIZE); lng <= Math.floor(ne.lng() / TILE_SIZE); lng++) {
                tiles.push({ lat, lng });
            }
        }
        return tiles;
    }

    // ── Fetch helpers ─────────────────────────────────────────
    async function fetchTile(fuelTypeId, latTile, lngTile) {
        const res = await fetch(`/map-tiles/${fuelTypeId}/${latTile}/${lngTile}`);
        return res.json();
    }

    async function fetchStats(fuelTypeId) {
        if (statsCache[fuelTypeId]) return statsCache[fuelTypeId];
        const res  = await fetch(`/map-stats/${fuelTypeId}`);
        const data = await res.json();
        statsCache[fuelTypeId] = data;
        return data;
    }

    // ── Hide all markers for a fuel type ─────────────────────
    function hideAllMarkersForFuelType(fuelTypeId) {
        const registry = markerRegistry[fuelTypeId];
        if (!registry) return;
        Object.values(registry).forEach(m => { m.map = null; });
    }

    // ── Load tiles covering the current viewport ──────────────
    async function loadViewportTiles(fuelTypeId, bounds) {
        if (!markerRegistry[fuelTypeId]) markerRegistry[fuelTypeId] = {};
        if (!tileCache[fuelTypeId])      tileCache[fuelTypeId]      = {};

        const viewportTiles = getTilesForBounds(bounds);
        const viewportKeys  = new Set(viewportTiles.map(t => tileKey(t.lat, t.lng)));

        // Show cached tile markers immediately
        viewportTiles.forEach(t => {
            const key   = tileKey(t.lat, t.lng);
            const sites = tileCache[fuelTypeId][key];
            if (!sites) return;
            sites.forEach(site => {
                const m = markerRegistry[fuelTypeId][site.id];
                if (m) m.map = map;
            });
        });

        // Hide markers whose tile has left the viewport
        Object.values(markerRegistry[fuelTypeId]).forEach(m => {
            if (m._tileKey && !viewportKeys.has(m._tileKey)) m.map = null;
        });

        // Fetch missing tiles in parallel
        const missingTiles = viewportTiles.filter(
            t => tileCache[fuelTypeId][tileKey(t.lat, t.lng)] === undefined
        );

        if (missingTiles.length === 0) {
            updateHighlights();
            return;
        }

        await Promise.all(missingTiles.map(async t => {
            const key  = tileKey(t.lat, t.lng);
            const data = await fetchTile(fuelTypeId, t.lat, t.lng);

            tileCache[fuelTypeId][key] = data.sites ?? [];

            // Only render markers if this tile is still in the viewport that triggered the fetch
            if (!viewportKeys.has(key)) return;

            (data.sites ?? []).forEach(site => {
                if (markerRegistry[fuelTypeId][site.id]) {
                    markerRegistry[fuelTypeId][site.id].map = map;
                    return;
                }
                const m = new google.maps.marker.AdvancedMarkerElement({
                    position: { lat: site.lat, lng: site.lng },
                    map,
                    title:   `${site.name} — ${(site.price * 100).toFixed(1)}/L`,
                    content: makePinEl(site.price, globalMin, globalMax, site.brand),
                });
                m._site    = site;
                m._tileKey = key;
                m.addListener('click', () => openInfoWindow(m, site, globalMin, globalMax));
                markerRegistry[fuelTypeId][site.id] = m;
            });
        }));

        updateHighlights();
    }

    // ── Initialise Google Map ─────────────────────────────────
    async function initMap() {
        const { Map }          = await google.maps.importLibrary('maps');
        await google.maps.importLibrary('marker');
        const { Autocomplete } = await google.maps.importLibrary('places');

        const saved = loadMapPosition();

        map = new Map(document.getElementById('fuelMap'), {
            center:            saved ? { lat: saved.lat, lng: saved.lng } : { lat: -27.4698, lng: 153.0251 },
            zoom:              saved ? saved.zoom : 12,
            mapId:             'DEMO_MAP_ID',
            clickableIcons:    false,
            mapTypeControl:    false,
            streetViewControl: false,
            fullscreenControl: true,
            zoomControlOptions: { position: google.maps.ControlPosition.RIGHT_CENTER },
        });

        let highlightTimer = null;

        map.addListener('idle', async () => {
            saveMapPosition();

            const zoom = map.getZoom();
            if (zoom < MIN_ZOOM) {
                showZoomOverlay();
                hideAllMarkersForFuelType(currentFuelTypeId);
                return;
            }
            hideZoomOverlay();

            if (!statsCache[currentFuelTypeId]) {
                const stats = await fetchStats(currentFuelTypeId);
                globalMin           = stats.min;
                globalMax           = stats.max;
                currentFuelTypeName = stats.fuel_type_name;
                updateStatsPills(stats);
            }

            await loadViewportTiles(currentFuelTypeId, map.getBounds());

            clearTimeout(highlightTimer);
            highlightTimer = setTimeout(updateHighlights, 150);
        });

        map.addListener('click', () => {
            if (activeInfoWindow) { activeInfoWindow.close(); activeInfoWindow = null; }
            if (activeMarker) { activeMarker.zIndex = null; activeMarker = null; }
        });

        // ── Address search ────────────────────────────────────
        const input = document.getElementById('addressSearch');
        const ac    = new Autocomplete(input, {
            componentRestrictions: { country: 'au' },
            fields: ['geometry', 'name'],
        });
        ac.addListener('place_changed', () => {
            const place = ac.getPlace();
            if (place.geometry?.location) {
                map.panTo(place.geometry.location);
                map.setZoom(15);
            }
        });
    }

    initMap();

    // ── Persist selected fuel type across visits ──────────────
    const FUEL_TYPE_KEY = 'fuelmap_fuel_type';
    const savedFuelType = localStorage.getItem(FUEL_TYPE_KEY);
    if (savedFuelType && parseInt(savedFuelType) !== currentFuelTypeId) {
        $wire.set('selectedFuelTypeId', parseInt(savedFuelType));
    }

    // ── Livewire event — fuel type changed ───────────────────
    $wire.on('fuelTypeChanged', async ({ fuelTypeId }) => {
        localStorage.setItem(FUEL_TYPE_KEY, fuelTypeId);

        if (activeInfoWindow) { activeInfoWindow.close(); activeInfoWindow = null; }
        if (activeMarker)     { activeMarker.zIndex = null; activeMarker = null; }

        if (highlightedMin) {
            const s = highlightedMin._site;
            highlightedMin.content = makePinEl(s.price, globalMin, globalMax, s.brand);
            highlightedMin.zIndex  = null;
            highlightedMin         = null;
        }
        if (highlightedMax) {
            const s = highlightedMax._site;
            highlightedMax.content = makePinEl(s.price, globalMin, globalMax, s.brand);
            highlightedMax.zIndex  = null;
            highlightedMax         = null;
        }

        hideAllMarkersForFuelType(currentFuelTypeId);
        currentFuelTypeId = parseInt(fuelTypeId);

        const stats = await fetchStats(currentFuelTypeId);
        globalMin           = stats.min;
        globalMax           = stats.max;
        currentFuelTypeName = stats.fuel_type_name;
        updateStatsPills(stats);

        if (map && map.getZoom() >= MIN_ZOOM) {
            await loadViewportTiles(currentFuelTypeId, map.getBounds());
        }
    });
</script>
@endscript
```

- [ ] **Step 4.2: Verify tile loading in DevTools**

Open http://192.168.4.23:7025/ → navigate to the map page → open DevTools → Network tab (filter: Fetch/XHR).

1. On page load confirm a request to `/map-stats/{id}` appears
2. Confirm multiple `/map-tiles/{id}/{lat}/{lng}` requests appear as the map settles (one per viewport tile)
3. Confirm fuel station pins appear on the map after tiles resolve

- [ ] **Step 4.3: Verify zoom guard**

1. Zoom the map out to zoom level 10 or lower
2. Confirm "Zoom in to see fuel stations" overlay appears and all markers disappear
3. Zoom back to level 11 or higher — confirm overlay hides and markers reload

- [ ] **Step 4.4: Verify tile cache — no re-fetch on re-pan**

1. Pan the map to a new area and wait for tiles to load
2. Pan back to the original area
3. In DevTools Network tab: confirm no new `/map-tiles/` requests fire for the already-visited area — markers appear instantly from the JS cache

- [ ] **Step 4.5: Verify fuel type cache — no re-fetch on re-select**

1. Change the fuel type via the selector — confirm old markers hide, new markers load
2. Switch back to the original fuel type
3. In DevTools Network tab: confirm zero new `/map-tiles/` or `/map-stats/` requests fire — everything comes from JS cache

- [ ] **Step 4.6: Run full test suite**

```bash
./vendor/bin/pest -v
```

Expected: all tests pass

- [ ] **Step 4.7: Commit**

```bash
git add resources/views/livewire/fuel-map.blade.php
git commit -m "feat: replace map marker loading with geospatial tile caching system"
```
