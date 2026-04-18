# Locate Me Button — Design Spec

**Date:** 2026-04-18
**Feature:** Floating "locate me" button on the fuel map

## Overview

Add a floating button to the map canvas that, when clicked, uses the browser Geolocation API to centre the map on the user's current location and place a pulsing "you are here" dot marker.

## Scope

Single file change: `resources/views/livewire/fuel-map.blade.php`

- New HTML button element inside the map container div
- New JS in the existing `@script` block (no new files, no backend changes)

## Button

- **Position:** Floating, bottom-right of the map canvas, above the existing zoom controls (~70px from bottom, 10px from right)
- **Size:** 40×40px, border-radius 8px
- **Icon:** Crosshair/location SVG icon

### States

| State | Appearance |
|---|---|
| Idle | Dark background (`#1e293b`), grey icon, grey border |
| Loading | Dark background, blue border glow, rotating spinner icon |
| Active | Solid blue background (`#0ea5e9`), white filled icon, blue glow |
| Error | Dark background, red border, crossed-out icon — resets to idle after 2s |

## Geolocation Flow

1. User clicks button → state changes to **Loading**
2. Call `navigator.geolocation.getCurrentPosition(success, error, { timeout: 10000 })`
3. **On success:**
   - Pan and zoom map to user coordinates (zoom level 14)
   - Place (or replace) a `userLocationMarker` `AdvancedMarkerElement` at user's position
   - Button state → **Active**
4. **On re-click while Active:** re-centre map on existing coordinates (no new permission prompt)
5. **On click while Loading:** no-op (ignore the click)
5. **On error:** button flashes **Error** state for 2s then resets to **Idle**

## "You Are Here" Marker

- Implemented as an `AdvancedMarkerElement` with a custom DOM element (consistent with `makePinEl()` pattern)
- Visual: 16px blue circle (`#3b82f6`), white 2px border, CSS pulsing ring animation
- Stored in a module-scoped `userLocationMarker` variable; replaced on each successful location fix
- Persists for the session; cleared on page reload

## Error Handling

| Error code | Behaviour |
|---|---|
| `PERMISSION_DENIED` | Error state on button, resets after 2s |
| `POSITION_UNAVAILABLE` | Error state on button, resets after 2s |
| `TIMEOUT` | Error state on button, resets after 2s |

No toast or modal — the button visual state is sufficient feedback.

## What Is Not In Scope

- Accuracy circle overlay
- Distance-sorted station list
- Continuous location tracking
- Saving location to localStorage
