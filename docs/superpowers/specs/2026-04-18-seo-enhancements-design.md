# SEO Enhancements Design

**Date:** 2026-04-18
**Goal:** Broader brand/directory visibility for "Queensland fuel prices" queries
**Site:** https://fuelhunter.cloud

## Background

FuelHunter is live at fuelhunter.cloud with rich, crawlable content but no SEO implementation. Every page has a static `<title>FuelHunter</title>`, no meta descriptions, no canonical URLs, no sitemap, and no structured data.

Public indexable routes:
- `/` — interactive fuel price map
- `/fuel` — fuel station directory
- `/fuel/{fuelSite}` — individual station detail pages
- `/dashboard` — price trends and statistics
- `/about` — about page
- `/oil-prices` — global oil prices

## Approach

Option B: `spatie/laravel-sitemap` for sitemap generation; hand-rolled meta tag layer; JSON-LD structured data as Blade partials.

## Deliverable 1: robots.txt

Update `public/robots.txt` to block API endpoints and auth pages, and add a sitemap directive.

```
User-agent: *
Disallow: /map-data/
Disallow: /map-stats/
Disallow: /map-tiles/
Disallow: /login
Disallow: /register
Disallow: /profile/
Disallow: /tool/

Sitemap: https://fuelhunter.cloud/sitemap.xml
```

## Deliverable 2: Dynamic Meta Tag Layer

Each controller and Livewire component passes a `$seo` array into the layout. The layout renders `<title>`, `<meta name="description">`, `<link rel="canonical">`, and Open Graph/Twitter Card tags from it.

The layout provides defaults so pages without explicit `$seo` values degrade gracefully.

**Meta tags per page:**

| Page | Title | Description |
|------|-------|-------------|
| `/` | Queensland Fuel Prices Map \| FuelHunter | Live fuel prices across Queensland. Find the cheapest petrol, diesel and LPG near you. |
| `/fuel` | Fuel Stations in Queensland \| FuelHunter | Browse all Queensland fuel stations with live price data. |
| `/fuel/{fuelSite}` | {Brand} {Suburb} Fuel Prices \| FuelHunter | Live fuel prices at {Brand} {Address}. Compare unleaded, diesel and premium prices. |
| `/dashboard` | Queensland Fuel Price Trends \| FuelHunter | Track Queensland fuel price trends and statistics over time. |
| `/about` | About FuelHunter \| Queensland Fuel Price Tracker | How FuelHunter works and where our data comes from. |
| `/oil-prices` | Global Oil Prices \| FuelHunter | Track global crude oil prices and their impact on Queensland fuel. |

**Canonical URL:** Always `https://fuelhunter.cloud{current path}` — prevents duplicate content from query strings on map/dashboard pages.

**Open Graph + Twitter Cards:** Rendered from the same `$seo` array. No additional logic required.

## Deliverable 3: Sitemap

Install `spatie/laravel-sitemap`. Create an Artisan command `sitemap:generate` that writes `public/sitemap.xml`.

**Coverage:**

| URL group | Priority | Change freq |
|-----------|----------|-------------|
| `/` | 1.0 | daily |
| `/fuel`, `/dashboard`, `/oil-prices`, `/about` | 0.8 | weekly |
| `/fuel/{fuelSite}` (all stations) | 0.6 | weekly |

API endpoints and auth pages are excluded.

The command is scheduled weekly in `routes/console.php`. It can also be run manually on deploy.

## Deliverable 4: Structured Data (JSON-LD)

Three Blade partials included conditionally via the layout.

**`WebSite` (homepage `/`):**
Declares the site name and URL. Minimal markup; important for Google Sitelinks eligibility.

**`GasStation` (`/fuel/{fuelSite}`):**
- `name` — brand + suburb (e.g. "BP Fortitude Valley")
- `address` — street, suburb, city, state, postcode
- `geo` — latitude/longitude (both stored on the `FuelSite` model)
- `brand` — fuel brand name
- `url` — canonical page URL

**`ItemList` (`/fuel`):**
List of fuel station names and URLs for the current paginated set. Signals to Google this is a directory page.

## Deliverable 5: Post-Deploy (Manual)

Submit `https://fuelhunter.cloud/sitemap.xml` to Google Search Console.

## Testing

No automated tests. Three manual checks after deploy:

1. **Meta tags** — view source on each page type; confirm title, description, canonical, and OG tags are present and correct
2. **Sitemap** — visit `https://fuelhunter.cloud/sitemap.xml`; confirm static pages and a sample of station URLs are present
3. **Structured data** — paste a `/fuel/{fuelSite}` URL into Google's Rich Results Test; confirm `GasStation` markup is valid
