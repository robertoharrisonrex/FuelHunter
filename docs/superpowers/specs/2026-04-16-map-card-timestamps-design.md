# Map Card Timestamp Labels — Design Spec

**Date:** 2026-04-16
**Status:** Approved

## Problem

The map info window currently shows "Updated X ago" for each fuel site. This is ambiguous — it could mean "when FuelHunter last checked" or "when the price actually changed". Users should see both:

1. When the selected fuel type's price last changed at that station.
2. When FuelHunter last fetched prices from the API.

## Changes Overview

Four components change: database (new table), ETL (write timestamp), backend (expose timestamp), frontend (display both labels).

---

## 1. Database — `settings` table

New migration creates a `settings` table:

| Column  | Type   | Notes                        |
|---------|--------|------------------------------|
| `key`   | string | unique, primary lookup field |
| `value` | text   | stores the timestamp as text |

One row is used: `key = 'last_prices_checked_at'`, `value = ISO datetime string`.

---

## 2. ETL — `qldfuelapi_to_sqlite_etl.py`

At the end of `load_fuel_prices()`, after inserting new prices, add:

```sql
INSERT OR REPLACE INTO settings (key, value)
VALUES ('last_prices_checked_at', datetime('now'))
```

This runs every ETL cycle (every 30 min), so the value reflects the actual last API call time, independent of whether any prices changed.

---

## 3. Backend — `MapStatsController`

Add `last_checked_at` to the existing `/map-stats/{fuelTypeId}` JSON response. Read it from the `settings` table:

```php
DB::table('settings')->where('key', 'last_prices_checked_at')->value('value')
```

Return it as an ISO string alongside the existing `min`, `max`, `count`, `fuel_type_name` fields. If the row doesn't exist (ETL hasn't run yet), return `null`.

No new route or controller needed.

---

## 4. Frontend — `fuel-map.blade.php`

### New JS variable

`globalLastChecked` is populated from the stats response (set alongside `globalMin`, `globalMax`, `currentFuelTypeName`).

### `openInfoWindow` change

The existing line:

```
Updated {formatUpdated(site.updated)}
```

Becomes two lines in the price section:

```
{currentFuelTypeName} price last changed {formatUpdated(site.updated)}
Last checked {formatUpdated(globalLastChecked)}
```

Both use the existing `formatUpdated()` function unchanged. The "Last checked" line is omitted (gracefully) if `globalLastChecked` is null.

### Fuel type change handler

`globalLastChecked` is also updated in the `fuelTypeChanged` Livewire event handler alongside the other stats fields, since stats are re-fetched on fuel type switch.

---

## What is NOT changing

- The `formatUpdated()` function — already handles relative time correctly.
- The `prices.transaction_date_utc` field — this is the correct source for "price last changed".
- The tile API — no changes.
- Any other UI element outside the info window price section.

---

## Success criteria

- Info window shows "{FuelType} price last changed X ago" where X reflects `prices.transaction_date_utc`.
- Info window shows "Last checked X ago" where X reflects the last ETL run time from the `settings` table.
- "Last checked" updates every ~30 min as the ETL runs.
- If `last_prices_checked_at` has never been written (ETL not yet run), "Last checked" line is hidden rather than showing an error.
