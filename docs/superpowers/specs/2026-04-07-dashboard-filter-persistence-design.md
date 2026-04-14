# Dashboard Filter Persistence

**Date:** 2026-04-07  
**Status:** Approved

## Problem

The dashboard filters (date range and fuel type selection) reset to defaults on every page load. Users who always view a specific fuel type over a specific date range must re-apply their preferences each visit.

## Solution

Persist the three filter values in cookies with a 30-day expiry. Cookies are written automatically on every filter change via Livewire `updated*()` hooks, and read back in `mount()` before defaults are applied.

## Scope

Changes are confined entirely to `app/Livewire/Dashboard.php`. No blade, JS, route, or migration changes required.

## Cookie Schema

| Cookie name       | Value                        | Expiry  |
|-------------------|------------------------------|---------|
| `dash_date_from`  | `Y-m-d` string               | 30 days |
| `dash_date_to`    | `Y-m-d` string               | 30 days |
| `dash_fuel_types` | JSON-encoded array of IDs    | 30 days |

Three separate cookies are used so a partial state (e.g. only the date was previously saved) restores correctly without needing all three to be present.

## Read Logic (`mount()`)

1. Check `Cookie::get('dash_date_from')` — if present, use it; otherwise default to `now()->subDays(29)`.
2. Check `Cookie::get('dash_date_to')` — if present, use it; otherwise default to `now()`.
3. Check `Cookie::get('dash_fuel_types')` — if present, JSON-decode and use it; otherwise default to first Unleaded type.

## Write Logic (`updated*()` hooks)

- `updatedDateFrom()` — queues `dash_date_from` cookie.
- `updatedDateTo()` — queues `dash_date_to` cookie.
- `updatedSelectedFuelTypes()` — queues `dash_fuel_types` cookie (JSON-encoded).

`setPreset()` sets `$this->dateFrom` and `$this->dateTo` directly, which triggers the above hooks automatically — no extra code needed.

## Out of Scope

- URL-based filter sharing
- Per-user server-side persistence
- Expiry configuration UI
