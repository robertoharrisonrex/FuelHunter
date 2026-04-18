# Oil Price Chart — Market Closed State

## Overview

The Global Oil Prices chart on the dashboard flatlines over weekends because commodity markets (NYMEX/ICE) are closed Saturday–Sunday. This spec covers adding a Live/Market Closed badge to the chart header and fixing the chart to stop cleanly at the last real data point instead of drawing a flat line across the gap.

## Background

- ETL runs every 20 minutes, storing prices with `INSERT OR IGNORE` keyed on `(code, recorded_at)`
- Over weekends the API returns the same last-known price with the same timestamp, so no new rows are inserted
- The chart uses `spanGaps: true`, which bridges null values with a flat line — visually misleading
- The fuel trends chart has a "Live Data" badge; the oil chart has no equivalent status indicator

## Market Hours

NYMEX (WTI, Natural Gas, Gasoline) and ICE (Brent Crude) trade continuously from Sunday ~6 PM ET to Friday ~5 PM ET — equivalent to Monday ~8 AM AEST through Saturday ~7 AM AEST. For display purposes, Saturday and Sunday in Brisbane time (AEST, UTC+10, no DST) are treated as fully closed.

## Design

### 1. Backend — `OilPriceController`

Add `market_open: bool` to the JSON response from `/oil-prices`.

Logic:
- Get the current day-of-week in `Australia/Brisbane` timezone
- `market_open = true` if Monday–Friday, `false` if Saturday or Sunday

The existing query, caching, and response structure are unchanged. Only one new key is appended.

### 2. Frontend — Status badge

A placeholder `<div id="oilStatusBadge">` is added to the Global Oil Prices card header, alongside the existing title/subtitle. After `initOilChart()` fetches `/oil-prices`, it reads `data.market_open` and injects:

**When open:**
```html
<div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
  <span class="relative flex h-2 w-2">
    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
    <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-400"></span>
  </span>
  <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Live</span>
</div>
```

**When closed:**
```html
<div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-800 rounded-xl px-3 py-1.5 border border-slate-200 dark:border-slate-700">
  <span class="relative flex h-2 w-2">
    <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-400"></span>
  </span>
  <span class="text-xs text-slate-600 dark:text-slate-400 font-semibold">Market Closed</span>
</div>
```

No badge is shown if the fetch fails (existing error path is unchanged).

### 3. Chart — gap rendering

Change `spanGaps: true` → `spanGaps: false` in `buildOilDatasets()`.

This makes the line stop at the last real Friday data point and not resume until Monday's first point appears, rather than drawing a misleading flat horizontal line across the weekend.

## Files Changed

| File | Change |
|------|--------|
| `app/Http/Controllers/OilPriceController.php` | Add `market_open` boolean to response |
| `resources/views/livewire/dashboard.blade.php` | Add `#oilStatusBadge` div to header; inject badge in `initOilChart()`; change `spanGaps` to `false` |

## Out of Scope

- Daily maintenance break (07:00–08:00 AEST Mon–Fri) — too brief to warrant showing "Market Closed"
- Public holidays — not handled
- Changing the ETL schedule over weekends
