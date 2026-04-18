# Locate Me Button Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a floating "Locate Me" button to the fuel map that centres the map on the user's GPS position and places a pulsing blue dot marker.

**Architecture:** Single file change — `resources/views/livewire/fuel-map.blade.php`. The button is absolutely positioned inside the existing `#fuelMapWrapper` div. CSS state is driven by a `data-state` attribute on the button. Geolocation is handled in the existing `@script` block using the browser Geolocation API and the already-imported `AdvancedMarkerElement`.

**Tech Stack:** Laravel 11, Livewire 3, Tailwind CSS, Google Maps JS API (beta), browser Geolocation API

---

### Task 1: Button HTML, CSS, and Livewire render test

**Files:**
- Create: `tests/Feature/FuelMapTest.php`
- Modify: `resources/views/livewire/fuel-map.blade.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/FuelMapTest.php`:

```php
<?php

use App\Livewire\FuelMap;
use Livewire\Livewire;

it('renders the locate me button', function () {
    Livewire::test(FuelMap::class)
        ->assertSeeHtml('id="locateMeBtn"');
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
./vendor/bin/pest tests/Feature/FuelMapTest.php
```

Expected: FAIL — `Failed asserting that ... contains "id="locateMeBtn""`.

- [ ] **Step 3: Add CSS for button states and pulse animation**

In `resources/views/livewire/fuel-map.blade.php`, find the closing `</style>` tag inside the `@assets` block (currently at line 79) and insert the following block immediately before it:

```css
/* ── Locate Me button ─────────────────────────────────── */
.locate-me-btn {
    bottom: 70px; right: 10px;
    width: 40px; height: 40px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    transition: background 0.15s, border-color 0.15s, box-shadow 0.15s;
}
.locate-me-btn[data-state="idle"] {
    background: #1e293b;
    border: 1px solid #334155;
    color: #94a3b8;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
.locate-me-btn[data-state="loading"] {
    background: #1e293b;
    border: 1px solid #0ea5e9;
    color: #0ea5e9;
    box-shadow: 0 2px 8px rgba(14,165,233,0.2);
}
.locate-me-btn[data-state="active"] {
    background: #0ea5e9;
    border: 1px solid #38bdf8;
    color: white;
    box-shadow: 0 2px 12px rgba(14,165,233,0.5);
}
.locate-me-btn[data-state="error"] {
    background: #1e293b;
    border: 1px solid #ef4444;
    color: #ef4444;
    box-shadow: 0 2px 8px rgba(239,68,68,0.2);
}
@keyframes locatePulse {
    0%   { box-shadow: 0 0 0 0 rgba(59,130,246,0.4); }
    70%  { box-shadow: 0 0 0 8px rgba(59,130,246,0); }
    100% { box-shadow: 0 0 0 0 rgba(59,130,246,0); }
}
```

- [ ] **Step 4: Add button HTML to the map wrapper**

In `resources/views/livewire/fuel-map.blade.php`, find the map canvas section and add the button after it, before the closing `</div>` of `#fuelMapWrapper`. Replace:

```html
    {{-- ── Map canvas ───────────────────────────────────────── --}}
    <div wire:ignore id="fuelMap" class="w-full h-full bg-slate-800"></div>

</div>
```

With:

```html
    {{-- ── Map canvas ───────────────────────────────────────── --}}
    <div wire:ignore id="fuelMap" class="w-full h-full bg-slate-800"></div>

    {{-- ── Locate Me button ──────────────────────────────────── --}}
    <button id="locateMeBtn"
            data-state="idle"
            aria-label="Centre map on my location"
            class="locate-me-btn absolute z-10">
        <svg id="locateMeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="3"/>
            <path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>
        </svg>
        <svg id="locateMeSpinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" class="hidden animate-spin">
            <path d="M12 2a10 10 0 0 1 10 10"/>
        </svg>
    </button>

</div>
```

- [ ] **Step 5: Run test to confirm it passes**

```bash
./vendor/bin/pest tests/Feature/FuelMapTest.php
```

Expected: PASS — 1 test, 1 assertion.

- [ ] **Step 6: Commit**

```bash
git add tests/Feature/FuelMapTest.php resources/views/livewire/fuel-map.blade.php
git commit -m "feat: add locate me button HTML and CSS to fuel map"
```

---

### Task 2: Geolocation JavaScript

**Files:**
- Modify: `resources/views/livewire/fuel-map.blade.php` (the `@script` block)

- [ ] **Step 1: Add `userLocationMarker` state variable**

In the `@script` block, find the map state variables section:

```javascript
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
```

Replace with:

```javascript
    let map, activeInfoWindow, activeMarker;
    let highlightedMin = null, highlightedMax = null;
    let userLocationMarker = null;
```

- [ ] **Step 2: Add `makeUserLocationEl`, `setLocateState`, and `locateMe` functions**

Find the comment `// ── Initialise Google Map ─────────────────────────────────` (just before `async function initMap()`) and insert the following three functions immediately before it:

```javascript
    // ── User location dot element ─────────────────────────
    function makeUserLocationEl() {
        const el = document.createElement('div');
        el.style.cssText = [
            'width:16px', 'height:16px', 'border-radius:50%',
            'background:#3b82f6', 'border:2px solid white',
            'animation:locatePulse 2s infinite',
        ].join(';');
        return el;
    }

    // ── Locate Me button state ────────────────────────────
    function setLocateState(state) {
        const btn     = document.getElementById('locateMeBtn');
        const icon    = document.getElementById('locateMeIcon');
        const spinner = document.getElementById('locateMeSpinner');
        if (!btn) return;
        btn.dataset.state = state;
        if (state === 'loading') {
            icon.classList.add('hidden');
            spinner.classList.remove('hidden');
        } else {
            icon.classList.remove('hidden');
            spinner.classList.add('hidden');
        }
    }

    // ── Locate Me ─────────────────────────────────────────
    function locateMe() {
        const btn = document.getElementById('locateMeBtn');
        if (!btn || btn.dataset.state === 'loading') return;

        if (btn.dataset.state === 'active' && userLocationMarker) {
            map.panTo(userLocationMarker.position);
            map.setZoom(14);
            return;
        }

        setLocateState('loading');

        navigator.geolocation.getCurrentPosition(
            ({ coords: { latitude: lat, longitude: lng } }) => {
                const pos = { lat, lng };
                map.panTo(pos);
                map.setZoom(14);
                if (userLocationMarker) userLocationMarker.map = null;
                userLocationMarker = new google.maps.marker.AdvancedMarkerElement({
                    map,
                    position: pos,
                    content:  makeUserLocationEl(),
                    zIndex:   999,
                });
                setLocateState('active');
            },
            () => {
                setLocateState('error');
                setTimeout(() => setLocateState('idle'), 2000);
            },
            { timeout: 10000 }
        );
    }

```

- [ ] **Step 3: Wire up the click handler inside `initMap`**

In `initMap()`, find the end of the autocomplete setup:

```javascript
        ac.addListener('place_changed', () => {
            const place = ac.getPlace();
            if (place.geometry?.location) {
                map.panTo(place.geometry.location);
                map.setZoom(15);
            }
        });
    }
```

Replace with:

```javascript
        ac.addListener('place_changed', () => {
            const place = ac.getPlace();
            if (place.geometry?.location) {
                map.panTo(place.geometry.location);
                map.setZoom(15);
            }
        });

        document.getElementById('locateMeBtn').addEventListener('click', locateMe);
    }
```

- [ ] **Step 4: Run existing tests to confirm nothing is broken**

```bash
./vendor/bin/pest
```

Expected: all tests pass.

- [ ] **Step 5: Manual browser test**

Start the dev server:
```bash
php artisan serve --port=7025
```

Open `http://localhost:7025` and verify:

1. Button visible bottom-right of map, above zoom controls — grey/dark (idle state)
2. Click button → spinner appears, browser shows permission prompt
3. Allow location → map pans and zooms to your position, blue pulsing dot placed, button turns solid blue (active)
4. Click button again → map re-centres on dot without prompting again
5. Hard-reload the page, click button, **deny** permission → button flashes red for ~2s then resets to grey
6. Confirm the dot is above all fuel price pins (not hidden behind them)

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/fuel-map.blade.php
git commit -m "feat: add geolocation JS for locate me button"
```
