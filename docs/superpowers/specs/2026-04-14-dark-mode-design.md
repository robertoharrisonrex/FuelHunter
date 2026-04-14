# Dark Mode — Design Spec
Date: 2026-04-14

## Overview

Add a user-controlled dark mode toggle to FuelHunter. The preference persists in `localStorage` and defaults to light mode regardless of OS setting.

## Decisions

| Decision | Choice | Reason |
|---|---|---|
| Toggle mechanism | Alpine.js store | Alpine already in project; store gives reactive state across components |
| Palette | Deep dark | `#0f172a` base, `#1e293b` cards — high contrast, dramatic feel |
| Default | Always light | Start light; user explicitly opts into dark |
| Desktop placement | Inline with nav links (right of "About") | Expected location, always visible |
| Mobile placement | Inside "More" overlay, above About link | Keeps tab bar clean; pill toggle with label |

## Architecture

### 1. Tailwind config (`tailwind.config.js`)
Add `darkMode: 'class'`. Tailwind generates `dark:` variants for every utility class.

### 2. FOUC prevention (`layout.blade.php`)
Inline `<script>` runs synchronously before Alpine — reads `localStorage.getItem('theme')` and adds `dark` to `<html>` immediately. Without this the page flashes light before Alpine initialises.

```html
<script>
  if (localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark');
  }
</script>
```

### 3. Alpine store (`resources/js/app.js`)
```js
Alpine.store('theme', {
    dark: localStorage.getItem('theme') === 'dark',
    toggle() {
        this.dark = !this.dark;
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        document.documentElement.classList.toggle('dark', this.dark);
    }
})
```

### 4. Toggle buttons (`layout.blade.php`)

**Desktop nav** — icon button inserted inline with nav links, right of "About":
- Light mode: sun icon, `bg-slate-100 hover:bg-slate-200`
- Dark mode: moon icon, `bg-slate-800 hover:bg-slate-700`
- Calls `$store.theme.toggle()`

**Mobile More overlay** — new row above the About link:
- Row: moon/sun icon + "Dark mode" label + pill toggle on the right
- Pill: indigo when dark, slate when light
- Tapping the row calls `$store.theme.toggle()` (does not close the menu)

### 5. Dark palette tokens

| Role | Light | Dark |
|---|---|---|
| Page background | `bg-slate-50` | `dark:bg-slate-950` |
| Nav / bottom bar | `bg-white/80` | `dark:bg-slate-900/80` |
| Cards | `bg-white` | `dark:bg-slate-900` |
| Card border | `border-slate-200` | `dark:border-slate-700` |
| Heading text | `text-slate-900` | `dark:text-slate-100` |
| Body text | `text-slate-600` | `dark:text-slate-400` |
| Muted text | `text-slate-400` | `dark:text-slate-500` |
| Active nav link | `bg-indigo-50 text-indigo-600` | `dark:bg-indigo-950 dark:text-indigo-400` |

### 6. Chart.js dark mode (`dashboard.blade.php`)

A `getChartTheme()` helper reads `document.documentElement.classList.contains('dark')` and returns colour objects for grid lines, tick labels, and tooltips.

A single `MutationObserver` on `<html>` watches for `class` changes. When triggered it calls `getChartTheme()` and updates all active Chart.js instances, then calls `chart.update('none')` (no animation) on each.

Dark chart colours:
- Grid lines: `rgba(255,255,255,0.06)`
- Tick labels: `#64748b`
- Tooltip background: `#1e293b`
- Tooltip text: `#f1f5f9`

### 7. Files modified

| File | Change |
|---|---|
| `tailwind.config.js` | Add `darkMode: 'class'` |
| `resources/js/app.js` | Register Alpine `theme` store |
| `resources/views/components/layout.blade.php` | FOUC script, toggle buttons, `dark:` variants on nav/body |
| `resources/views/livewire/dashboard.blade.php` | `dark:` variants on all cards, Chart.js dark mode via MutationObserver |
| `resources/views/livewire/fuel-map.blade.php` | `dark:` variants |
| `resources/views/about.blade.php` | `dark:` variants |
| `resources/views/fuelSite/index.blade.php` | `dark:` variants |
| `resources/views/fuelSite/show.blade.php` | `dark:` variants |
| `resources/views/components/nav-link.blade.php` | `dark:` active/hover variants |

## Out of scope

- System preference detection (`prefers-color-scheme`) — always starts light
- Google Maps dark styling — map tile theming requires a separate Maps API style config; excluded for now
