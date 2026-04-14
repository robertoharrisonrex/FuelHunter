# Regional Price Heat Map — Design Spec

**Date:** 2026-04-14  
**Status:** Approved

---

## Overview

Add a Regional Price Heat Map card to the Statistics dashboard. The map shows coloured circle markers over a Google Map — one circle per city, sized by site count and coloured by deviation from the Queensland state average price. Clicking a city circle drills down to suburb-level circles within that city. Always shows current prices (latest from the `prices` table), independent of the dashboard's date filter.

---

## User-Facing Behaviour

### Default state (city level)
- A Google Map is rendered inside a dashboard card, centred on Queensland.
- Each city with at least one reporting site gets a circle marker:
  - **Size:** proportional to site count (radius scaled between ~8km and ~25km)
  - **Colour:** green (cheapest) → amber → red (most expensive) mapped linearly across the deviation range from the state average
- Clicking a circle opens a Google Maps InfoWindow showing: city name, avg price (¢/L), deviation vs QLD average (e.g. `−9.2 ¢ cheaper`), site count, and a "See suburbs →" link that triggers the suburb drill-down.
- A fuel type selector (pill buttons, one per fuel type from the database) sits in the card header. Changing it reloads the circles for that fuel type.
- A legend at the card footer shows a green→red gradient labelled "Cheaper → Dearer vs QLD avg".

### Drill-down state (suburb level)
- Clicking a city circle fetches suburb-level data for that city and replaces the city circles with suburb circles using the same size/colour logic.
- The map zooms to fit the selected city's suburbs.
- A "← Back to cities" button in the card footer returns to the city view.

---

## Data & API

### New endpoint: `GET /map-heatmap/{fuelTypeId}`

Returns city-level aggregates for current prices:

```json
[
  {
    "city_id": 5,
    "city_name": "Brisbane",
    "lat": -27.471,
    "lng": 153.024,
    "avg_price": 20752,
    "deviation": 412,
    "site_count": 142
  }
]
```

- `avg_price` — average of `prices.price` for sites in this city (integer, stored in 0.1¢ increments)
- `deviation` — `avg_price − statewide_avg` (positive = more expensive, negative = cheaper)
- `lat` / `lng` — centroid computed as `AVG(fuel_sites.latitude)` / `AVG(fuel_sites.longitude)` for sites in the city
- Filters: `prices.price > 50`, `prices.fuel_id = {fuelTypeId}`
- Cached for 10 minutes (file cache, key: `map_heatmap_city_{fuelTypeId}`)

### New endpoint: `GET /map-heatmap/{fuelTypeId}/city/{cityId}`

Same structure but grouped by `fuel_sites.geo_region_1` (suburb), filtered to `fuel_sites.geo_region_2 = {cityId}`.

- Cached for 10 minutes (key: `map_heatmap_suburb_{fuelTypeId}_{cityId}`)

### Controller

`App\Http\Controllers\MapHeatmapController` with two methods: `cities(int $fuelTypeId)` and `suburbs(int $fuelTypeId, int $cityId)`.

---

## Frontend

### Component

A new Livewire component `App\Livewire\RegionalHeatmap` embedded in `dashboard.blade.php` below the existing Brand Market Share card. It holds:
- `$selectedFuelTypeId` — integer, defaults to the ID of "Unleaded"
- `$fuelTypes` — loaded in `mount()` from `FuelType::orderBy('name')`
- No server-side rendering of map data — all map data is fetched client-side via the API endpoints above

### Google Map initialisation

Reuses the existing `@googlemaps/js-api-loader` pattern from `fuel-map.blade.php`. The map is initialised inside a `@script` block, centred on Queensland (`{ lat: -22.0, lng: 144.0 }`, zoom 5).

### Circle rendering (JavaScript)

```
fetchCities(fuelTypeId)
  → GET /map-heatmap/{fuelTypeId}
  → clearMarkers()
  → for each city: createCircle(city)
```

`createCircle(item)`:
- `google.maps.Circle` with:
  - `center: { lat: item.lat, lng: item.lng }`
  - `radius`: mapped from `item.site_count` (10 sites → 8km, 200 sites → 25km)
  - `fillColor`: interpolated across `['#22c55e', '#f59e0b', '#ef4444']` based on normalised deviation
  - `fillOpacity: 0.75`, `strokeColor: '#ffffff'`, `strokeWeight: 2`
- `InfoWindow` on click (city level) triggers suburb drill-down
- `InfoWindow` content: city name, avg price, deviation, site count

### Fuel type selector

Pill buttons rendered in Blade. Clicking a pill calls `$wire.set('selectedFuelTypeId', id)`, which triggers a Livewire re-render and dispatches a `heatmapFuelChanged` browser event that the JS map listener picks up and re-fetches.

### Drill-down

On "See suburbs →" link click inside a city InfoWindow:
1. Close the InfoWindow
2. Store `activeCityId`
3. `fetchSuburbs(fuelTypeId, cityId)` → `clearMarkers()` → re-render suburb circles
4. Compute `google.maps.LatLngBounds` from all suburb circle centres, call `map.fitBounds()` to zoom in
5. Show "← Back to cities" button in the card footer

Back button: clear `activeCityId`, re-fetch cities, call `map.setCenter({ lat: -22.0, lng: 144.0 })` + `map.setZoom(5)` to reset.

---

## Routes

```php
Route::get('/map-heatmap/{fuelTypeId}',              [MapHeatmapController::class, 'cities']);
Route::get('/map-heatmap/{fuelTypeId}/city/{cityId}',[MapHeatmapController::class, 'suburbs']);
```

Both are GET, unauthenticated, return `JsonResponse`.

---

## Files Changed / Created

| File | Change |
|------|--------|
| `app/Http/Controllers/MapHeatmapController.php` | New |
| `app/Livewire/RegionalHeatmap.php` | New |
| `resources/views/livewire/regional-heatmap.blade.php` | New |
| `resources/views/livewire/dashboard.blade.php` | Add `<livewire:regional-heatmap />` below brand share card |
| `routes/web.php` | Add two heatmap routes |

---

## Constraints & Decisions

- **No GeoJSON boundary data required** — centroids derived from `AVG(lat/lng)` of existing site coordinates. Avoids external data pipeline.
- **Always current prices** — queries only the `prices` table (latest per site/fuel pair), not `historical_site_prices`. Ignores the dashboard date filter.
- **Independent fuel type selector** — does not inherit from the main dashboard filter, giving the map its own context.
- **Colour scale is relative per load** — min deviation = full green, max = full red. Absolute thresholds are not used, so the map always shows contrast even when the range is narrow.
- **Radius scaled by site count, not absolute** — avoids tiny dots for rural areas with few sites.
