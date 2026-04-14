# Dashboard Filter Persistence Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Persist the dashboard's date range and fuel type filters in cookies so they survive page reloads and browser restarts.

**Architecture:** `Dashboard.php` reads three cookies in `mount()` before applying defaults. Three `updated*()` hooks write cookies whenever the user changes a filter via the UI. `setPreset()` writes the date cookies explicitly since it sets properties directly in PHP (bypassing `updated*()` hooks). No blade or JS changes required.

**Tech Stack:** Laravel 11, Livewire 3, Pest, `Illuminate\Support\Facades\Cookie`

---

## File Map

| File | Action | Purpose |
|------|--------|---------|
| `app/Livewire/Dashboard.php` | Modify | Add cookie reads in `mount()`, `updated*()` hooks, explicit writes in `setPreset()` |
| `tests/Feature/DashboardFilterPersistenceTest.php` | Create | Feature tests for cookie read/write behaviour |

---

### Task 1: Write failing tests for cookie read in `mount()`

**Files:**
- Create: `tests/Feature/DashboardFilterPersistenceTest.php`

- [ ] **Step 1: Create the test file**

```php
<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

test('mount restores date range from cookies', function () {
    Cookie::shouldReceive('get')
        ->with('dash_date_from', null)->andReturn('2026-01-01');
    Cookie::shouldReceive('get')
        ->with('dash_date_to', null)->andReturn('2026-02-28');
    Cookie::shouldReceive('get')
        ->with('dash_fuel_types', null)->andReturn(null);

    Livewire::test(Dashboard::class)
        ->assertSet('dateFrom', '2026-01-01')
        ->assertSet('dateTo', '2026-02-28');
});

test('mount restores fuel types from cookie', function () {
    Cookie::shouldReceive('get')
        ->with('dash_date_from', null)->andReturn(null);
    Cookie::shouldReceive('get')
        ->with('dash_date_to', null)->andReturn(null);
    Cookie::shouldReceive('get')
        ->with('dash_fuel_types', null)->andReturn('[\"3\",\"5\"]');

    Livewire::test(Dashboard::class)
        ->assertSet('selectedFuelTypes', ['3', '5']);
});

test('mount falls back to defaults when no cookies exist', function () {
    Cookie::shouldReceive('get')
        ->with('dash_date_from', null)->andReturn(null);
    Cookie::shouldReceive('get')
        ->with('dash_date_to', null)->andReturn(null);
    Cookie::shouldReceive('get')
        ->with('dash_fuel_types', null)->andReturn(null);

    $component = Livewire::test(Dashboard::class);

    $component->assertSet('dateFrom', now()->subDays(29)->format('Y-m-d'));
    $component->assertSet('dateTo', now()->format('Y-m-d'));
});
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
cd /Users/roberto/Projects/FuelHunter/FuelHunter
./vendor/bin/pest tests/Feature/DashboardFilterPersistenceTest.php -v
```

Expected: FAIL — `mount()` does not yet read cookies.

---

### Task 2: Implement cookie reads in `mount()`

**Files:**
- Modify: `app/Livewire/Dashboard.php`

- [ ] **Step 1: Add `Cookie` import at the top of the file (after existing `use` statements)**

```php
use Illuminate\Support\Facades\Cookie;
```

- [ ] **Step 2: Replace the `mount()` method**

Replace the existing `mount()`:

```php
public function mount(): void
{
    $this->dateFrom = Cookie::get('dash_date_from') ?? now()->subDays(29)->format('Y-m-d');
    $this->dateTo   = Cookie::get('dash_date_to')   ?? now()->format('Y-m-d');
    $this->fuelTypes = FuelType::orderBy('name')->get();

    $savedFuelTypes = Cookie::get('dash_fuel_types');
    if ($savedFuelTypes !== null) {
        $this->selectedFuelTypes = json_decode($savedFuelTypes, true) ?? [];
    } else {
        $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
            ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false);
        $this->selectedFuelTypes = $unleaded ? [(string) $unleaded->id] : [];
    }
}
```

- [ ] **Step 3: Run the tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/DashboardFilterPersistenceTest.php -v
```

Expected: 3 tests PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Livewire/Dashboard.php tests/Feature/DashboardFilterPersistenceTest.php
git commit -m "feat: restore dashboard filters from cookies on mount"
```

---

### Task 3: Write failing tests for cookie writes

**Files:**
- Modify: `tests/Feature/DashboardFilterPersistenceTest.php`

- [ ] **Step 1: Append these tests to the file**

```php
test('updating dateFrom queues a cookie', function () {
    Cookie::shouldReceive('get')->andReturn(null);
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('dateFrom', '2026-03-01');

    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_from', '2026-03-01', 43200)
        ->once();
});

test('updating dateTo queues a cookie', function () {
    Cookie::shouldReceive('get')->andReturn(null);
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('dateTo', '2026-03-31');

    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_to', '2026-03-31', 43200)
        ->once();
});

test('updating selectedFuelTypes queues a cookie', function () {
    Cookie::shouldReceive('get')->andReturn(null);
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('selectedFuelTypes', ['2', '4']);

    Cookie::shouldHaveReceived('queue')
        ->with('dash_fuel_types', '["2","4"]', 43200)
        ->once();
});

test('setPreset queues date cookies', function () {
    Cookie::shouldReceive('get')->andReturn(null);
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->call('setPreset', '7d');

    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_from', now()->subDays(6)->format('Y-m-d'), 43200)
        ->once();
    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_to', now()->format('Y-m-d'), 43200)
        ->once();
});
```

- [ ] **Step 2: Run the tests to verify the new ones fail**

```bash
./vendor/bin/pest tests/Feature/DashboardFilterPersistenceTest.php -v
```

Expected: first 3 PASS, last 4 FAIL — `updated*()` hooks and `setPreset()` do not yet write cookies.

---

### Task 4: Implement cookie writes

**Files:**
- Modify: `app/Livewire/Dashboard.php`

- [ ] **Step 1: Add `updated*()` hooks after the `applyFilters()` method**

```php
public function updatedDateFrom(): void
{
    Cookie::queue('dash_date_from', $this->dateFrom, 43200);
}

public function updatedDateTo(): void
{
    Cookie::queue('dash_date_to', $this->dateTo, 43200);
}

public function updatedSelectedFuelTypes(): void
{
    Cookie::queue('dash_fuel_types', json_encode($this->selectedFuelTypes), 43200);
}
```

- [ ] **Step 2: Add explicit cookie writes to `setPreset()`**

Replace the existing `setPreset()`:

```php
public function setPreset(string $preset): void
{
    [$this->dateFrom, $this->dateTo] = match ($preset) {
        '7d'  => [now()->subDays(6)->format('Y-m-d'),         now()->format('Y-m-d')],
        '30d' => [now()->subDays(29)->format('Y-m-d'),        now()->format('Y-m-d')],
        '90d' => [now()->subDays(89)->format('Y-m-d'),        now()->format('Y-m-d')],
        '1yr' => [now()->subYear()->addDay()->format('Y-m-d'), now()->format('Y-m-d')],
        default => [$this->dateFrom, $this->dateTo],
    };

    Cookie::queue('dash_date_from', $this->dateFrom, 43200);
    Cookie::queue('dash_date_to',   $this->dateTo,   43200);
}
```

- [ ] **Step 3: Run all tests**

```bash
./vendor/bin/pest tests/Feature/DashboardFilterPersistenceTest.php -v
```

Expected: all 7 tests PASS.

- [ ] **Step 4: Run the full test suite to check for regressions**

```bash
./vendor/bin/pest -v
```

Expected: all tests PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/Dashboard.php tests/Feature/DashboardFilterPersistenceTest.php
git commit -m "feat: persist dashboard filters to cookies on change"
```
