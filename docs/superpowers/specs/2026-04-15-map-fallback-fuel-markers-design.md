# Map Fallback Fuel Marker Labels

**Date:** 2026-04-15
**Status:** Approved

## Summary

When a fuel site has no price for the selected fuel type, the map marker currently shows a blank pin (brand logo only). This feature makes those markers more useful by falling back to a related fuel type's price, clearly labelled in tiny font so the user knows it's a substitute.

## Fallback Chains

| Selected fuel type | Fallback 1 | Label | Fallback 2 | Label | If none |
|--------------------|------------|-------|------------|-------|---------|
| Premium Diesel (14) | Diesel (3) | `Regular` | — | — | blank |
| Diesel (3) | Premium Diesel (14) | `Premium` | — | — | blank |
| Unleaded (2) | Premium 95 (5) | `95` | Premium 98 (8) | `98` | blank |

"Blank" means the existing no-price pin (brand logo, no price text) — no change to that state.

## Backend — `MapTileController.php`

Add Diesel (fuel_id=3) as a fetched fallback price. The tile query already joins `p_ul` (2), `p95` (5), `p98` (8), and `p_pd` (14); Diesel is the only missing one.

**Add join:**
```php
->leftJoin('prices as p_d', function ($join) {
    $join->on('p_d.site_id', '=', 'fuel_sites.id')->where('p_d.fuel_id', '=', 3);
})
```

**Add to `select()`:**
```
'p_d.price as price_d'
```

**Add to `$sites` map:**
```php
'price_d' => $r->price_d ? round($r->price_d / 100, 3) : null,
```

The tile cache TTL (600s) will serve the new field after the next cache warm.

## Frontend — `fuel-map.blade.php`

### 1. `resolveFallback(site, fuelTypeId)` — new helper function

Placed near `makePinEl`. Returns `{ price, subLabel }`.

```js
function resolveFallback(site, fuelTypeId) {
    if (site.price != null) return { price: site.price, subLabel: null };
    if (fuelTypeId === 14 && site.price_d  != null) return { price: site.price_d,  subLabel: 'Regular' };
    if (fuelTypeId === 3  && site.price_pd != null) return { price: site.price_pd, subLabel: 'Premium' };
    if (fuelTypeId === 2  && site.price_95 != null) return { price: site.price_95, subLabel: '95'      };
    if (fuelTypeId === 2  && site.price_98 != null) return { price: site.price_98, subLabel: '98'      };
    return { price: null, subLabel: null };
}
```

### 2. `makePinEl` — add `subLabel` parameter

Signature becomes: `makePinEl(price, min, max, brandName, highlight = null, subLabel = null)`

When `subLabel` is non-null and the pin has a price:
- Render the label above the price in ~8px font, muted slate colour (`#94a3b8`), to visually distinguish it from a primary price
- When `highlight` is also set (cheapest/priciest), suppress `subLabel` — the highlight context takes priority

### 3. Call sites (7 locations)

There are 7 `makePinEl` calls. They split into two groups:

**Normal rendering (line ~651 in `loadViewportTiles`, lines ~523/537 de-highlight in `updateHighlights`, lines ~747/753 de-highlight on fuel type change):**
```js
const { price, subLabel } = resolveFallback(site, currentFuelTypeId);
makePinEl(price, globalMin, globalMax, site.brand, null, subLabel);
```

**Highlight rendering (lines ~529/543 in `updateHighlights` — cheapest/priciest):**
`updateHighlights` only ever selects markers where `m._site.price` is non-null (it guards with `if (!m._site.price) return`), so `subLabel` will always be null for these. They pass `highlight` as before and omit `subLabel`:
```js
makePinEl(s.price, globalMin, globalMax, s.brand, 'cheapest');
makePinEl(s.price, globalMin, globalMax, s.brand, 'priciest');
```

## What does NOT change

- The info window text still reads `"No ${currentFuelTypeName} price available"` for sites with no primary price — it is not updated to show the fallback.
- The map stats pills (min/max) are unaffected — they reflect only the primary fuel type.
- No changes to E10, LPG, Premium 95 selected states — fallback only applies to the three fuel type groups above.
