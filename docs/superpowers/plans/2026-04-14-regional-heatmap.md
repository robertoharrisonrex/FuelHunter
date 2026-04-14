# Regional Price Heat Map — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a Regional Price Heat Map card to the Statistics dashboard — coloured Google Maps circles per city (drillable to suburb), sized by site count, coloured by deviation from QLD state average price, with its own independent fuel type selector.

**Architecture:** `MapHeatmapController` exposes two JSON endpoints (cities, suburbs). A new `RegionalHeatmap` Livewire component renders the card shell and fuel selector server-side; the Google Map and all circle logic run entirely client-side. Fuel type changes are communicated to JS via a Livewire-dispatched browser event (`heatmapFuelChanged`). Map data is always current prices — the `prices` table only, no date filtering.

**Tech Stack:** Laravel 11 · Livewire 3 · Google Maps JS API (existing bootstrap pattern from `fuel-map.blade.php`) · Pest · SQLite (local) / PostgreSQL (production)

---

### Task 1: MapHeatmapController + routes + tests

**Files:**
- Create: `app/Http/Controllers/MapHeatmapController.php`
- Create: `tests/Feature/MapHeatmapControllerTest.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/MapHeatmapControllerTest.php`:

```php
<?php

use Illuminate\Support\Facades\Cache;

test('cities endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'city_id'    => 5,
                'city_name'  => 'Brisbane',
                'lat'        => -27.471,
                'lng'        => 153.024,
                'avg_price'  => 20752,
                'deviation'  => 412,
                'site_count' => 142,
            ],
        ]);

    $response = $this->getJson('/map-heatmap/2');

    $response->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.city_id', 5)
             ->assertJsonPath('0.city_name', 'Brisbane')
             ->assertJsonPath('0.deviation', 412)
             ->assertJsonPath('0.site_count', 142);
});

test('cities endpoint returns empty array for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([]);

    $response = $this->getJson('/map-heatmap/999');

    $response->assertOk()->assertExactJson([]);
});

test('suburbs endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'suburb_id'  => 12,
                'suburb_name'=> 'Newmarket',
                'lat'        => -27.441,
                'lng'        => 153.012,
                'avg_price'  => 20100,
                'deviation'  => -240,
                'site_count' => 4,
            ],
        ]);

    $response = $this->getJson('/map-heatmap/2/city/5');

    $response->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.suburb_id', 12)
             ->assertJsonPath('0.suburb_name', 'Newmarket')
             ->assertJsonPath('0.deviation', -240);
});
```

- [ ] **Step 2: Run tests to confirm they fail**

```bash
cd /path/to/project && ./vendor/bin/pest tests/Feature/MapHeatmapControllerTest.php -v
```

Expected: 3 failures — routes not found (404).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/MapHeatmapController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapHeatmapController extends Controller
{
    public function cities(int $fuelTypeId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_heatmap_city_{$fuelTypeId}", 600, function () use ($fuelTypeId) {
            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('cities', 'cities.id', '=', 'fuel_sites.geo_region_2')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->groupBy('cities.id', 'cities.name')
                ->selectRaw('
                    cities.id   AS city_id,
                    cities.name AS city_name,
                    AVG(fuel_sites.latitude)  AS lat,
                    AVG(fuel_sites.longitude) AS lng,
                    AVG(prices.price)         AS avg_price,
                    COUNT(DISTINCT fuel_sites.id) AS site_count
                ')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $totalSites   = $rows->sum('site_count');
            $statewideAvg = $totalSites > 0
                ? $rows->sum(fn($r) => (float) $r->avg_price * (int) $r->site_count) / $totalSites
                : 0;

            return $rows->map(fn($r) => [
                'city_id'    => (int)   $r->city_id,
                'city_name'  =>         $r->city_name,
                'lat'        => (float) $r->lat,
                'lng'        => (float) $r->lng,
                'avg_price'  => (int)   round($r->avg_price),
                'deviation'  => (int)   round($r->avg_price - $statewideAvg),
                'site_count' => (int)   $r->site_count,
            ])->values()->toArray();
        });

        return response()->json($data);
    }

    public function suburbs(int $fuelTypeId, int $cityId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_heatmap_suburb_{$fuelTypeId}_{$cityId}", 600, function () use ($fuelTypeId, $cityId) {
            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('suburbs', 'suburbs.id', '=', 'fuel_sites.geo_region_1')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->where('fuel_sites.geo_region_2', $cityId)
                ->groupBy('suburbs.id', 'suburbs.name')
                ->selectRaw('
                    suburbs.id   AS suburb_id,
                    suburbs.name AS suburb_name,
                    AVG(fuel_sites.latitude)  AS lat,
                    AVG(fuel_sites.longitude) AS lng,
                    AVG(prices.price)         AS avg_price,
                    COUNT(DISTINCT fuel_sites.id) AS site_count
                ')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $totalSites   = $rows->sum('site_count');
            $areaAvg      = $totalSites > 0
                ? $rows->sum(fn($r) => (float) $r->avg_price * (int) $r->site_count) / $totalSites
                : 0;

            return $rows->map(fn($r) => [
                'suburb_id'   => (int)   $r->suburb_id,
                'suburb_name' =>         $r->suburb_name,
                'lat'         => (float) $r->lat,
                'lng'         => (float) $r->lng,
                'avg_price'   => (int)   round($r->avg_price),
                'deviation'   => (int)   round($r->avg_price - $areaAvg),
                'site_count'  => (int)   $r->site_count,
            ])->values()->toArray();
        });

        return response()->json($data);
    }
}
```

- [ ] **Step 4: Register the routes**

In `routes/web.php`, add after the existing map routes (after line `Route::get('/map-tiles/...')`):

```php
use App\Http\Controllers\MapHeatmapController;

Route::get('/map-heatmap/{fuelTypeId}',              [MapHeatmapController::class, 'cities']);
Route::get('/map-heatmap/{fuelTypeId}/city/{cityId}',[MapHeatmapController::class, 'suburbs']);
```

Also add `MapHeatmapController` to the `use` imports at the top of `routes/web.php`.

- [ ] **Step 5: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/MapHeatmapControllerTest.php -v
```

Expected: 3 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/MapHeatmapController.php \
        tests/Feature/MapHeatmapControllerTest.php \
        routes/web.php
git commit -m "feat: add MapHeatmapController with cities and suburbs endpoints"
```

---

### Task 2: Livewire component + static Blade card

**Files:**
- Create: `app/Livewire/RegionalHeatmap.php`
- Create: `resources/views/livewire/regional-heatmap.blade.php`
- Modify: `resources/views/livewire/dashboard.blade.php`

- [ ] **Step 1: Create the Livewire component**

Create `app/Livewire/RegionalHeatmap.php`:

```php
<?php

namespace App\Livewire;

use App\Models\FuelType;
use Livewire\Component;

class RegionalHeatmap extends Component
{
    public int $selectedFuelTypeId = 0;
    public $fuelTypes = [];

    public function mount(): void
    {
        $this->fuelTypes = FuelType::orderBy('name')->get();

        $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
            ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false)
            ?? $this->fuelTypes->first();

        $this->selectedFuelTypeId = $unleaded ? $unleaded->id : 0;
    }

    public function selectFuelType(int $id): void
    {
        $this->selectedFuelTypeId = $id;
        $this->dispatch('heatmapFuelChanged', fuelTypeId: $id);
    }

    public function render()
    {
        return view('livewire.regional-heatmap');
    }
}
```

- [ ] **Step 2: Create the Blade view (static card, no JS yet)**

Create `resources/views/livewire/regional-heatmap.blade.php`:

```blade
<div class="dash-card bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

    {{-- ── Header ──────────────────────────────────────────────── --}}
    <div class="p-6 pb-4 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b border-slate-100">
        <div>
            <h2 class="text-slate-900 text-xl font-bold tracking-tight">Regional Price Heat Map</h2>
            <p class="text-slate-500 text-sm mt-0.5">Average price vs QLD state average · current prices</p>
        </div>
        <div>
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Fuel Type</p>
            <div class="flex flex-wrap gap-1.5">
                @foreach($fuelTypes as $type)
                    <button wire:click="selectFuelType({{ $type->id }})"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold border transition-all duration-150
                                   {{ $selectedFuelTypeId === $type->id
                                       ? 'bg-indigo-600 text-white border-indigo-600'
                                       : 'border-gray-200 bg-gray-50 text-gray-500 hover:border-indigo-300 hover:text-indigo-500 hover:bg-indigo-50' }}">
                        {{ $type->name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── Map ─────────────────────────────────────────────────── --}}
    <div wire:ignore class="relative h-[480px]">
        <div id="regionalHeatmap" class="w-full h-full"></div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────── --}}
    <div class="px-6 py-3 border-t border-slate-100 flex items-center justify-between gap-4">
        <button id="heatmapBackBtn"
                class="hidden items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
            ← Back to cities
        </button>
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <span>Cheaper</span>
            <div class="w-24 h-2 rounded-full"
                 style="background: linear-gradient(to right, #22c55e, #f59e0b, #ef4444)"></div>
            <span>Dearer</span>
            <span class="text-slate-300 ml-1">vs QLD avg</span>
        </div>
        <span id="heatmapSubtitle" class="text-xs text-slate-400">Click a circle to see suburbs</span>
    </div>

</div>
```

- [ ] **Step 3: Embed the component in the dashboard**

In `resources/views/livewire/dashboard.blade.php`, add after the closing `</div>` of the Brand Market Share card (the last `</div>` before the outer closing `</div>`):

```blade
    {{-- ── Regional Heat Map ──────────────────────────────────── --}}
    <livewire:regional-heatmap />
```

- [ ] **Step 4: Confirm the card renders (no JS errors expected yet)**

```bash
# Ensure the dev server is running on port 7025, then navigate in browser:
php artisan cache:clear
```

Visit `http://localhost:7025/dashboard` — you should see the new card at the bottom of the dashboard with the fuel type pills and an empty map div. No JavaScript errors.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/RegionalHeatmap.php \
        resources/views/livewire/regional-heatmap.blade.php \
        resources/views/livewire/dashboard.blade.php
git commit -m "feat: add RegionalHeatmap Livewire component and static card shell"
```

---

### Task 3: JavaScript — map init + city circles + InfoWindow

**Files:**
- Modify: `resources/views/livewire/regional-heatmap.blade.php`

- [ ] **Step 1: Add the `@script` block with map init and city circle logic**

Append the following to the end of `resources/views/livewire/regional-heatmap.blade.php` (after the closing `</div>` of the component):

```blade
@script
<script>
let heatmapMap    = null;
let heatmapMarkers = [];
let activeInfoWin  = null;
let activeCityId   = null;
let currentFuelId  = $wire.selectedFuelTypeId;

function heatmapClearMarkers() {
    heatmapMarkers.forEach(m => m.setMap(null));
    heatmapMarkers = [];
    if (activeInfoWin) { activeInfoWin.close(); activeInfoWin = null; }
}

function heatmapInterpolateColour(t) {
    // t in [0, 1]: 0 = green, 0.5 = amber, 1 = red
    const stops = [
        [34,  197, 94],   // #22c55e green
        [245, 158, 11],   // #f59e0b amber
        [239, 68,  68],   // #ef4444 red
    ];
    const scaled = t * (stops.length - 1);
    const lo     = Math.floor(scaled);
    const hi     = Math.min(lo + 1, stops.length - 1);
    const frac   = scaled - lo;
    const r = Math.round(stops[lo][0] + frac * (stops[hi][0] - stops[lo][0]));
    const g = Math.round(stops[lo][1] + frac * (stops[hi][1] - stops[lo][1]));
    const b = Math.round(stops[lo][2] + frac * (stops[hi][2] - stops[lo][2]));
    return `#${r.toString(16).padStart(2,'0')}${g.toString(16).padStart(2,'0')}${b.toString(16).padStart(2,'0')}`;
}

function heatmapRadius(siteCount) {
    const MIN_SITES = 10, MAX_SITES = 200;
    const MIN_R = 8000,   MAX_R = 25000;
    const t = Math.min(1, Math.max(0, (siteCount - MIN_SITES) / (MAX_SITES - MIN_SITES)));
    return MIN_R + t * (MAX_R - MIN_R);
}

function heatmapCreateCircle(item, deviations, isSuburb) {
    const minDev  = Math.min(...deviations);
    const maxDev  = Math.max(...deviations);
    const range   = maxDev - minDev || 1;
    const t       = (item.deviation - minDev) / range;
    const colour  = heatmapInterpolateColour(t);
    const priceC  = (item.avg_price / 100).toFixed(1);
    const devC    = (item.deviation / 100).toFixed(1);
    const devSign = item.deviation >= 0 ? '+' : '';
    const devLabel = item.deviation >= 0
        ? `<span style="color:#ef4444;font-weight:700">${devSign}${devC} ¢ dearer</span>`
        : `<span style="color:#22c55e;font-weight:700">${devC} ¢ cheaper</span>`;
    const name     = isSuburb ? item.suburb_name : item.city_name;
    const id       = isSuburb ? item.suburb_id   : item.city_id;

    const circle = new google.maps.Circle({
        map:           heatmapMap,
        center:        { lat: item.lat, lng: item.lng },
        radius:        heatmapRadius(item.site_count),
        fillColor:     colour,
        fillOpacity:   0.72,
        strokeColor:   '#ffffff',
        strokeWeight:  2,
        clickable:     true,
    });

    const infoContent = `
        <div style="font-family:system-ui,sans-serif;padding:14px 16px;min-width:180px">
            <p style="font-weight:700;font-size:14px;color:#0f172a;margin:0 0 8px">${name}</p>
            <p style="font-size:12px;color:#475569;margin:0 0 3px">Avg price: <b>${priceC} ¢/L</b></p>
            <p style="font-size:12px;color:#475569;margin:0 0 3px">vs QLD avg: ${devLabel}</p>
            <p style="font-size:12px;color:#475569;margin:0 0 10px">Sites reporting: <b>${item.site_count}</b></p>
            ${!isSuburb ? `<a href="#" data-city-id="${id}" data-city-name="${name}"
                   style="font-size:12px;color:#4f46e5;font-weight:600;text-decoration:none"
                   class="heatmap-drill">See suburbs →</a>` : ''}
        </div>`;

    const infoWin = new google.maps.InfoWindow({ content: infoContent });

    circle.addListener('click', () => {
        if (activeInfoWin) activeInfoWin.close();
        infoWin.setPosition({ lat: item.lat, lng: item.lng });
        infoWin.open(heatmapMap);
        activeInfoWin = infoWin;

        // Attach drill-down listener after InfoWindow DOM is ready
        if (!isSuburb) {
            google.maps.event.addListenerOnce(infoWin, 'domready', () => {
                document.querySelectorAll('.heatmap-drill').forEach(el => {
                    el.addEventListener('click', e => {
                        e.preventDefault();
                        const cityId   = parseInt(el.dataset.cityId);
                        const cityName = el.dataset.cityName;
                        infoWin.close();
                        heatmapDrillDown(cityId, cityName);
                    });
                });
            });
        }
    });

    heatmapMarkers.push(circle);
}

async function heatmapFetchCities(fuelId) {
    const res  = await fetch(`/map-heatmap/${fuelId}`);
    const data = await res.json();
    heatmapClearMarkers();
    activeCityId = null;
    document.getElementById('heatmapBackBtn').classList.add('hidden');
    document.getElementById('heatmapSubtitle').textContent = 'Click a circle to see suburbs';

    if (!data.length) return;
    const devs = data.map(d => d.deviation);
    data.forEach(item => heatmapCreateCircle(item, devs, false));
}

async function heatmapDrillDown(cityId, cityName) {
    const res  = await fetch(`/map-heatmap/${currentFuelId}/city/${cityId}`);
    const data = await res.json();
    heatmapClearMarkers();
    activeCityId = cityId;

    document.getElementById('heatmapBackBtn').classList.remove('hidden');
    document.getElementById('heatmapBackBtn').style.display = 'flex';
    document.getElementById('heatmapSubtitle').textContent = cityName;

    if (!data.length) return;
    const devs   = data.map(d => d.deviation);
    data.forEach(item => heatmapCreateCircle(item, devs, true));

    const bounds = new google.maps.LatLngBounds();
    data.forEach(d => bounds.extend({ lat: d.lat, lng: d.lng }));
    heatmapMap.fitBounds(bounds);
}

window.heatmapBackToCities = function () {
    activeCityId = null;
    heatmapMap.setCenter({ lat: -22.0, lng: 144.0 });
    heatmapMap.setZoom(5);
    heatmapFetchCities(currentFuelId);
};

(async () => {
    await google.maps.importLibrary('maps');
    heatmapMap = new google.maps.Map(document.getElementById('regionalHeatmap'), {
        center:            { lat: -22.0, lng: 144.0 },
        zoom:              5,
        mapTypeId:         'roadmap',
        disableDefaultUI:  false,
        zoomControl:       true,
        streetViewControl: false,
        mapTypeControl:    false,
    });
    heatmapFetchCities(currentFuelId);
})();

$wire.on('heatmapFuelChanged', ({ fuelTypeId }) => {
    currentFuelId = fuelTypeId;
    heatmapFetchCities(fuelTypeId);
});
</script>
@endscript
```

- [ ] **Step 2: Verify locally**

Visit `http://localhost:7025/dashboard` and scroll to the Regional Price Heat Map card. Confirm:
- Google Map renders centred on Queensland
- Coloured circles appear for each city
- Clicking a circle shows an InfoWindow with name, price, deviation, site count, and "See suburbs →" link
- Changing the fuel type pill updates the circles

If the map is blank, check the browser console for errors. A common issue is the Google Maps API key not being set — verify `config('services.google.maps_api_key')` is non-empty.

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/regional-heatmap.blade.php
git commit -m "feat: add Google Maps circle overlays to regional heat map"
```

---

### Task 4: Suburb drill-down + back button wiring

The drill-down JS (`heatmapDrillDown`) and back button function (`heatmapBackToCities`) are already defined in Task 3. This task wires the back button's `onclick` in the Blade view and verifies the full drill-down flow.

**Files:**
- Modify: `resources/views/livewire/regional-heatmap.blade.php`

- [ ] **Step 1: Wire the back button onclick in the Blade view**

The back button in `regional-heatmap.blade.php` currently has `onclick` pointing to `heatmapBackToCities()` which is defined in the `@script` block. Confirm the button element reads exactly:

```blade
<button id="heatmapBackBtn"
        onclick="heatmapBackToCities()"
        class="hidden items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
    ← Back to cities
</button>
```

If the `onclick` attribute is missing, add it now.

- [ ] **Step 2: Verify the drill-down flow**

Visit `http://localhost:7025/dashboard`. On the Regional Heat Map card:
1. Click any city circle → InfoWindow appears with city stats and "See suburbs →"
2. Click "See suburbs →" → map zooms to city, suburb circles appear, "← Back to cities" button appears in footer
3. Click "← Back to cities" → map resets to QLD view, city circles reappear, back button hides
4. Change fuel type pill while in suburb view → map resets to city view for new fuel type

- [ ] **Step 3: Commit**

```bash
git add resources/views/livewire/regional-heatmap.blade.php
git commit -m "feat: wire suburb drill-down and back button for regional heat map"
```

---

## Self-Review

**Spec coverage check:**

| Spec requirement | Task |
|---|---|
| Google Map in dashboard card centred on QLD | Task 3 |
| Circles sized by site count (~8–25km radius) | Task 3 `heatmapRadius()` |
| Circles coloured green→amber→red by deviation | Task 3 `heatmapInterpolateColour()` |
| InfoWindow with city name, avg price, deviation, site count, "See suburbs →" | Task 3 `heatmapCreateCircle()` |
| Independent fuel type pill selector | Task 2 (Blade), Task 3 (`heatmapFuelChanged` event) |
| Legend: green→red gradient in footer | Task 2 |
| Click "See suburbs →" → suburb circles + fitBounds | Task 3 `heatmapDrillDown()` |
| "← Back to cities" resets map | Task 3 `heatmapBackToCities()`, Task 4 |
| Always current prices (prices table only) | Task 1 controller — no date filter |
| Colour scale relative per load | Task 3 — min/max deviation computed per fetch |
| Cached 10 minutes | Task 1 — `Cache::store('file')->remember(..., 600, ...)` |

All spec requirements covered. ✓
