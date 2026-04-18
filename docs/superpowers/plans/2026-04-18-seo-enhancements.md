# SEO Enhancements Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add foundational SEO to FuelHunter — dynamic meta tags, canonical URLs, Open Graph, structured data, sitemap, and robots.txt — to improve visibility for "Queensland fuel prices" queries.

**Architecture:** The layout component (`resources/views/components/layout.blade.php`) is extended with a `$seo` prop and an optional `$head` slot; each view passes its own `$seo` array and inlines JSON-LD structured data where relevant. A single Artisan command generates `public/sitemap.xml` using `spatie/laravel-sitemap`, scheduled weekly.

**Tech Stack:** Laravel 11, Blade components, Livewire 3, `spatie/laravel-sitemap`

---

### Task 1: Update robots.txt

**Files:**
- Modify: `public/robots.txt`

- [ ] **Step 1: Replace robots.txt content**

Open `public/robots.txt` and replace its entire contents with:

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

- [ ] **Step 2: Verify**

Run:
```bash
cat public/robots.txt
```

Expected output: the file above, verbatim.

- [ ] **Step 3: Commit**

```bash
git add public/robots.txt
git commit -m "seo: update robots.txt to block API/auth routes and add sitemap directive"
```

---

### Task 2: Add $seo meta tag layer to layout

**Files:**
- Modify: `resources/views/components/layout.blade.php`

This is the foundation all other tasks build on. The layout gains a `$seo` prop (array with defaults) and an optional `$head` slot for per-page content injected into `<head>`. The `<title>`, meta description, canonical, Open Graph, and Twitter Card tags are rendered from `$seo`.

- [ ] **Step 1: Replace the `@props` line and `<head>` block**

The current file starts with:
```blade
@props(['fullBleed' => false])
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FuelHunter</title>
```

Replace that opening block (lines 1–7) with:

```blade
@props([
    'fullBleed' => false,
    'seo' => [],
])

@php
    $seoTitle       = $seo['title']       ?? 'FuelHunter';
    $seoDescription = $seo['description'] ?? 'Track live fuel prices across Queensland. Find the cheapest petrol, diesel and LPG near you.';
    $seoCanonical   = $seo['canonical']   ?? rtrim(config('app.url'), '/') . request()->getPathInfo();
    $seoImage       = $seo['og_image']    ?? null;
@endphp

<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <link rel="canonical" href="{{ $seoCanonical }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FuelHunter">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    @if($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    @if($seoImage)
    <meta name="twitter:image" content="{{ $seoImage }}">
    @endif

    @if(isset($head))
        {{ $head }}
    @endif
```

- [ ] **Step 2: Verify the app still loads**

```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/ | grep -A3 "<title>"
kill %1
```

Expected: `<title>FuelHunter</title>` (default title, no $seo passed yet from home.blade.php).

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/layout.blade.php
git commit -m "seo: add \$seo meta tag layer and \$head slot to layout component"
```

---

### Task 3: Wire $seo into home page + WebSite JSON-LD

**Files:**
- Modify: `resources/views/home.blade.php`

- [ ] **Step 1: Replace home.blade.php**

Current content:
```blade
<x-layout :full-bleed="true">
    <x-slot:heading></x-slot:heading>
    <livewire:fuel-map />
</x-layout>
```

Replace with:
```blade
<x-layout
    :full-bleed="true"
    :seo="[
        'title'       => 'Queensland Fuel Prices Map | FuelHunter',
        'description' => 'Live fuel prices across Queensland. Find the cheapest petrol, diesel and LPG near you.',
    ]"
>
    <x-slot:heading></x-slot:heading>

    <x-slot:head>
        @php
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'WebSite',
            'name'        => 'FuelHunter',
            'url'         => config('app.url'),
            'description' => 'Track live fuel prices across Queensland.',
        ];
        @endphp
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:head>

    <livewire:fuel-map />
</x-layout>
```

- [ ] **Step 2: Verify**

```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/ | grep -E "<title>|description|canonical|application/ld"
kill %1
```

Expected: title contains "Queensland Fuel Prices Map | FuelHunter", description and canonical tags present, `application/ld+json` script present.

- [ ] **Step 3: Commit**

```bash
git add resources/views/home.blade.php
git commit -m "seo: add meta tags and WebSite JSON-LD to home page"
```

---

### Task 4: Wire $seo into about page

**Files:**
- Modify: `resources/views/about.blade.php`

- [ ] **Step 1: Update the opening x-layout tag**

The current file starts with:
```blade
<x-layout>
    <x-slot:heading>About</x-slot:heading>
```

Replace that first line only with:
```blade
<x-layout :seo="['title' => 'About FuelHunter | Queensland Fuel Price Tracker', 'description' => 'How FuelHunter works and where our fuel price data comes from.']">
    <x-slot:heading>About</x-slot:heading>
```

- [ ] **Step 2: Verify**

```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/about | grep -E "<title>|<meta name=\"description\""
kill %1
```

Expected: title is "About FuelHunter | Queensland Fuel Price Tracker".

- [ ] **Step 3: Commit**

```bash
git add resources/views/about.blade.php
git commit -m "seo: add meta tags to about page"
```

---

### Task 5: Wire $seo into dashboard page

**Files:**
- Modify: `resources/views/dashboard/index.blade.php`

- [ ] **Step 1: Update the opening x-layout tag**

The current file starts with:
```blade
<x-layout>
    <x-slot:heading>
        Statistics
    </x-slot:heading>
```

Replace that first line only with:
```blade
<x-layout :seo="['title' => 'Queensland Fuel Price Trends | FuelHunter', 'description' => 'Track Queensland fuel price trends and statistics over time.']">
    <x-slot:heading>
        Statistics
    </x-slot:heading>
```

- [ ] **Step 2: Verify**

```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/dashboard | grep "<title>"
kill %1
```

Expected: `<title>Queensland Fuel Price Trends | FuelHunter</title>`.

- [ ] **Step 3: Commit**

```bash
git add resources/views/dashboard/index.blade.php
git commit -m "seo: add meta tags to dashboard page"
```

---

### Task 6: Wire $seo into fuel site index + ItemList JSON-LD

**Files:**
- Modify: `resources/views/fuelSite/index.blade.php`
- Modify: `resources/views/livewire/search.blade.php`

- [ ] **Step 1: Update fuelSite/index.blade.php**

Current content:
```blade
<x-layout>
    <x-slot:heading>Fuel Sites</x-slot:heading>
    <livewire:Search />
</x-layout>
```

Replace with:
```blade
<x-layout :seo="['title' => 'Fuel Stations in Queensland | FuelHunter', 'description' => 'Browse all Queensland fuel stations with live price data.']">
    <x-slot:heading>Fuel Sites</x-slot:heading>
    <livewire:Search />
</x-layout>
```

- [ ] **Step 2: Add ItemList JSON-LD to the search Livewire view**

Open `resources/views/livewire/search.blade.php`. Find the very top of the file (before the first `<div>`). Insert the following block at the top:

```blade
@php
$itemListElements = $fuelSites->map(function ($site, $index) use ($fuelSites) {
    return [
        '@type'    => 'ListItem',
        'position' => $fuelSites->firstItem() + $index,
        'url'      => rtrim(config('app.url'), '/') . '/fuel/' . $site->id,
        'name'     => trim(($site->brand?->name ?? $site->name) . ' ' . ($site->suburb?->name ?? '')),
    ];
})->values()->toArray();

$itemListSchema = [
    '@context'       => 'https://schema.org',
    '@type'          => 'ItemList',
    'name'           => 'Queensland Fuel Stations',
    'numberOfItems'  => $fuelSites->total(),
    'itemListElement' => $itemListElements,
];
@endphp
<script type="application/ld+json">{!! json_encode($itemListSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
```

- [ ] **Step 3: Verify**

```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/fuel | grep -E "<title>|application/ld"
kill %1
```

Expected: title is "Fuel Stations in Queensland | FuelHunter" and `application/ld+json` script is present.

- [ ] **Step 4: Commit**

```bash
git add resources/views/fuelSite/index.blade.php resources/views/livewire/search.blade.php
git commit -m "seo: add meta tags to fuel index and ItemList JSON-LD to search component"
```

---

### Task 7: Wire $seo into fuel site detail + GasStation JSON-LD

**Files:**
- Modify: `resources/views/fuelSite/show.blade.php`

- [ ] **Step 1: Update the opening x-layout tag and add GasStation JSON-LD**

The current file starts with:
```blade
<x-layout>
    <x-slot:heading>{{ $fuelSite->name }}</x-slot:heading>

    @php
        $brandName   = $fuelSite->brand?->name ?? $fuelSite->name;
```

Replace the first line (`<x-layout>`) with:
```blade
<x-layout
    :seo="[
        'title'       => ($fuelSite->brand?->name ?? $fuelSite->name) . ' ' . ($fuelSite->suburb?->name ?? '') . ' Fuel Prices | FuelHunter',
        'description' => 'Live fuel prices at ' . ($fuelSite->brand?->name ?? $fuelSite->name) . ', ' . $fuelSite->address . '. Compare unleaded, diesel and premium prices.',
        'canonical'   => rtrim(config('app.url'), '/') . '/fuel/' . $fuelSite->id,
    ]"
>
```

Then, immediately after `<x-slot:heading>{{ $fuelSite->name }}</x-slot:heading>` add a `$head` slot with the GasStation schema:

```blade
    <x-slot:head>
        @php
        $gasStationSchema = [
            '@context' => 'https://schema.org',
            '@type'    => 'GasStation',
            'name'     => trim(($fuelSite->brand?->name ?? $fuelSite->name) . ' ' . ($fuelSite->suburb?->name ?? '')),
            'address'  => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => $fuelSite->address,
                'addressLocality' => $fuelSite->suburb?->name,
                'addressRegion'   => 'QLD',
                'postalCode'      => $fuelSite->postcode,
                'addressCountry'  => 'AU',
            ],
            'geo' => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => (float) $fuelSite->latitude,
                'longitude' => (float) $fuelSite->longitude,
            ],
            'brand' => [
                '@type' => 'Brand',
                'name'  => $fuelSite->brand?->name,
            ],
            'url' => rtrim(config('app.url'), '/') . '/fuel/' . $fuelSite->id,
        ];
        @endphp
        <script type="application/ld+json">{!! json_encode($gasStationSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    </x-slot:head>
```

Note: `$fuelSite` is loaded with `brand`, `suburb`, `city`, `state` by `FuelSiteController::show()` before this view renders.

- [ ] **Step 2: Verify**

Get the ID of the first fuel site:
```bash
php artisan tinker --execute="echo \App\Models\FuelSite::first()->id;" 2>/dev/null
```

Then check that page (replace `1` with the ID returned above):
```bash
php artisan serve --port=7025 &
sleep 2
curl -s http://localhost:7025/fuel/1 | grep -E "<title>|canonical|application/ld"
kill %1
```

Expected: dynamic title (e.g. "Liberty SURAT Fuel Prices | FuelHunter"), canonical URL pointing to `/fuel/1`, `application/ld+json` block with GasStation type.

- [ ] **Step 3: Commit**

```bash
git add resources/views/fuelSite/show.blade.php
git commit -m "seo: add dynamic meta tags and GasStation JSON-LD to fuel site detail page"
```

---

### Task 8: Install spatie/laravel-sitemap and create GenerateSitemap command

**Files:**
- Create: `app/Console/Commands/GenerateSitemap.php`

- [ ] **Step 1: Install the package**

```bash
composer require spatie/laravel-sitemap
```

Expected: package installed, no errors.

- [ ] **Step 2: Create the command file**

Create `app/Console/Commands/GenerateSitemap.php` with:

```php
<?php

namespace App\Console\Commands;

use App\Models\FuelSite;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate public/sitemap.xml';

    public function handle(): void
    {
        $sitemap = Sitemap::create();

        $sitemap->add(Url::create('/')->setPriority(1.0)->setChangeFrequency('daily'));
        $sitemap->add(Url::create('/fuel')->setPriority(0.8)->setChangeFrequency('weekly'));
        $sitemap->add(Url::create('/dashboard')->setPriority(0.8)->setChangeFrequency('weekly'));
        $sitemap->add(Url::create('/about')->setPriority(0.8)->setChangeFrequency('weekly'));

        FuelSite::select('id')->chunk(200, function ($sites) use ($sitemap) {
            foreach ($sites as $site) {
                $sitemap->add(
                    Url::create("/fuel/{$site->id}")
                        ->setPriority(0.6)
                        ->setChangeFrequency('weekly')
                );
            }
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap written to ' . public_path('sitemap.xml'));
    }
}
```

- [ ] **Step 3: Run the command to verify it generates a valid sitemap**

```bash
php artisan sitemap:generate
```

Expected output: `Sitemap written to /path/to/public/sitemap.xml`

```bash
head -5 public/sitemap.xml
grep -c "<loc>" public/sitemap.xml
```

Expected: valid XML with `<urlset` root, and a count of 1810 or more `<loc>` entries (4 static + 1806 stations).

- [ ] **Step 4: Commit**

```bash
git add app/Console/Commands/GenerateSitemap.php composer.json composer.lock public/sitemap.xml
git commit -m "seo: add sitemap:generate command and initial sitemap.xml via spatie/laravel-sitemap"
```

---

### Task 9: Schedule sitemap generation

**Files:**
- Modify: `routes/console.php`

- [ ] **Step 1: Add weekly schedule**

Open `routes/console.php`. The current file ends with:
```php
Schedule::command('migrate:fresh --seed')->dailyAt('01:00');
```

Add after that line:
```php
Schedule::command('sitemap:generate')->weekly();
```

- [ ] **Step 2: Verify the schedule is registered**

```bash
php artisan schedule:list
```

Expected: `sitemap:generate` appears in the list with a weekly frequency.

- [ ] **Step 3: Commit**

```bash
git add routes/console.php
git commit -m "seo: schedule sitemap:generate weekly"
```

---

## Manual Verification Checklist (post-deploy)

Run these after deploying to fuelhunter.cloud:

1. **Meta tags** — view source on `/`, `/fuel`, `/fuel/{any-id}`, `/dashboard`, `/about`. Confirm:
   - `<title>` is unique per page
   - `<meta name="description">` is present
   - `<link rel="canonical">` matches the page URL (no query strings)
   - `<meta property="og:title">` is present

2. **Sitemap** — visit `https://fuelhunter.cloud/sitemap.xml`. Confirm it loads as XML and contains static pages plus fuel station URLs.

3. **Structured data** — paste `https://fuelhunter.cloud/fuel/{any-id}` into [Google's Rich Results Test](https://search.google.com/test/rich-results). Confirm `GasStation` type is detected without errors.

4. **Google Search Console** — submit `https://fuelhunter.cloud/sitemap.xml` via the Sitemaps tool.
