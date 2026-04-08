# Map Tile Caching Design

**Date:** 2026-04-08  
**Status:** Approved

## Problem

The fuel map currently fetches all Queensland fuel sites in a single request on every load and fuel type change — potentially thousands of markers. All marker objects are created and held in memory even when off-screen. This makes the map sluggish to load and slow to pan.

## Goal

Only load fuel sites visible in the current map viewport. Cache tiles per fuel type in both JS (session) and PHP (file cache) so re-panning or switching back to a previous fuel type is instant. Prevent loading at all when too zoomed out.

---

## Architecture

### New Server Endpoints

**1. `GET /map-stats/{fuelTypeId}`**  
Returns global statistics for the selected fuel type. Used to anchor the color scale consistently across all tiles.

```json
{ "min": 1.689, "max": 2.159, "count": 1240, "fuel_type_name": "Unleaded" }
```

- Backed by a single DB query, cached in PHP file cache (`map_stats_{fuelTypeId}`, TTL 600s).
- Implemented in a new `MapStatsController`.

**2. `GET /map-tiles/{fuelTypeId}/{latTile}/{lngTile}`**  
Returns the fuel sites within a single 0.5° × 0.5° grid tile.

- `latTile = floor(lat / 0.5)`, `lngTile = floor(lng / 0.5)` (signed integers).
- Laravel route constraint: `where(['latTile' => '-?\d+', 'lngTile' => '-?\d+'])`.
- Each tile is cached independently (`map_tile_{fuelTypeId}_{latTile}_{lngTile}`, TTL 600s).
- Implemented in a new `MapTileController`.
- Returns: `{ sites: [...] }` — same site shape as the current `/map-data/` endpoint.

**Tile size rationale:** 0.5° ≈ 55km N-S × 40km E-W in Queensland. At map zoom 11 (minimum), a typical desktop viewport covers ~6–8 tiles. At zoom 13–14, typically 1–4 tiles.

### Removed / Unchanged

- `/map-data/{fuelTypeId}` — kept as-is for now (used nowhere after this change, can be removed later).
- `FuelMap.php` Livewire component — no PHP changes needed; map data is now fetched entirely client-side.

---

## Client-Side Design

### Zoom Guard

Constant `MIN_ZOOM = 11`. When `map.getZoom() < MIN_ZOOM`:
- Show the zoom overlay (see below).
- Clear all active markers for the current fuel type.
- Do not fetch any tiles.

When zoom returns to ≥ 11: hide overlay, trigger tile load for current bounds.

### Zoom Overlay

An absolutely-positioned panel layered over the map (`z-index` above the canvas). Contains a zoom-in icon and the message: *"Zoom in to see fuel stations."* Shown/hidden by toggling a CSS class — no Livewire round-trip.

### Tile Coordinate Calculation (JS)

```js
const TILE_SIZE = 0.5;

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
```

Any tile that has _any_ overlap with the viewport is included, so sites near tile edges are never missed.

### Tile Cache (JS)

```js
// tileCache[fuelTypeId][`${latTile}_${lngTile}`] = Site[]
const tileCache = {};

// markerRegistry[fuelTypeId][siteId] = AdvancedMarkerElement
const markerRegistry = {};
```

Both caches persist for the browser session. Switching back to a previously-viewed fuel type or panning back to a visited area requires zero network requests.

### Idle Event Handler

Replaces the current `bounds_changed` + direct fetch approach.

```
map idle fires:
  saveMapPosition()
  zoom < MIN_ZOOM:
    showZoomOverlay()
    hide all active markers for currentFuelTypeId
    return
  hideZoomOverlay()
  loadViewportTiles(currentFuelTypeId, map.getBounds())
```

### loadViewportTiles(fuelTypeId, bounds)

```
1. Compute tile grid from bounds
2. Build set of tile keys in viewport
3. For each tile key NOT in tileCache[fuelTypeId]: fetch in parallel
4. For each tile in viewport: ensure markers exist in markerRegistry[fuelTypeId]
     - If site already has a marker → set marker.map = map (show)
     - If site is new → create AdvancedMarkerElement, store in registry
5. For each marker in markerRegistry[fuelTypeId]:
     - If site's tile is NOT in viewport → set marker.map = null (hide)
```

Parallel fetching means a 6-tile viewport dispatches 6 simultaneous requests. Markers appear as each tile resolves — no full-map loading state.

### Fuel Type Switch

```
1. Hide all markers for old fuel type (marker.map = null)
2. Switch currentFuelTypeId
3. Fetch /map-stats/{newFuelTypeId} (or use cached statsCache[fuelTypeId])
4. Update globalMin/globalMax for color scale
5. loadViewportTiles(newFuelTypeId, map.getBounds())
   → hits JS tile cache first; only fetches truly missing tiles
```

Stats are also cached per fuel type in JS (`statsCache[fuelTypeId]`).

### Color Scale

- `globalMin` / `globalMax` sourced from `/map-stats/{fuelTypeId}` — represents all QLD sites, not just visible ones.
- All markers use this global scale, so green = cheapest in QLD, red = priciest in QLD.
- Scale is stable; colors don't shift as tiles load.

### Stats Pills (Low / High)

Updated from the `/map-stats/` response, showing the QLD-wide low and high for the selected fuel type. Unchanged from current behavior.

### Highlight (Cheapest / Priciest)

Unchanged logic — scans `markerRegistry[fuelTypeId]` for markers whose `marker.map !== null` and within `map.getBounds()`. Runs after each `loadViewportTiles` call completes.

---

## Files Changed

| File | Change |
|------|--------|
| `routes/web.php` | Add 2 new routes |
| `app/Http/Controllers/MapStatsController.php` | New — global stats endpoint |
| `app/Http/Controllers/MapTileController.php` | New — per-tile sites endpoint |
| `resources/views/livewire/fuel-map.blade.php` | Replace fetch + marker logic with tile system; add zoom overlay |

---

## Out of Scope

- Removing the old `/map-data/{fuelTypeId}` endpoint (safe to do later).
- Tile eviction / memory limits (session accumulation is bounded and acceptable).
- Tile size configurability.
