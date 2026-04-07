<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

test('mount restores date range from cookies', function () {
    Livewire::withCookies([
        'dash_date_from' => '2026-01-01',
        'dash_date_to'   => '2026-02-28',
    ])->test(Dashboard::class)
        ->assertSet('dateFrom', '2026-01-01')
        ->assertSet('dateTo', '2026-02-28');
});

test('mount restores fuel types from cookie', function () {
    Livewire::withCookies([
        'dash_fuel_types' => json_encode(['3', '5']),
    ])->test(Dashboard::class)
        ->assertSet('selectedFuelTypes', ['3', '5']);
});

test('mount falls back to defaults when no cookies exist', function () {
    $component = Livewire::test(Dashboard::class);

    $component->assertSet('dateFrom', now()->subDays(29)->format('Y-m-d'));
    $component->assertSet('dateTo', now()->format('Y-m-d'));
});
