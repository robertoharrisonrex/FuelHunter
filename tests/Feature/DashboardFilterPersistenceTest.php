<?php

use App\Livewire\Dashboard;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
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
    DB::table('fuel_types')->insert([
        ['id' => 2, 'name' => 'Unleaded', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 3, 'name' => 'Diesel',   'created_at' => now(), 'updated_at' => now()],
    ]);
    DB::table('prices')->insert([
        ['site_id' => 1, 'fuel_id' => 2, 'collection_method' => 'T', 'transaction_date_utc' => now(), 'price' => 1800, 'created_at' => now(), 'updated_at' => now()],
        ['site_id' => 1, 'fuel_id' => 3, 'collection_method' => 'T', 'transaction_date_utc' => now(), 'price' => 2000, 'created_at' => now(), 'updated_at' => now()],
    ]);

    Livewire::withCookies([
        'dash_fuel_types' => json_encode(['2', '3']),
    ])->test(Dashboard::class)
        ->assertSet('selectedFuelTypes', ['2', '3']);
});

test('mount falls back to defaults when no cookies exist', function () {
    $component = Livewire::test(Dashboard::class);

    $component->assertSet('dateFrom', now()->subDays(29)->format('Y-m-d'));
    $component->assertSet('dateTo', now()->format('Y-m-d'));
});

test('updating dateFrom queues a cookie', function () {
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('dateFrom', '2026-03-01');

    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_from', '2026-03-01', 43200)
        ->once();
});

test('updating dateTo queues a cookie', function () {
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('dateTo', '2026-03-31');

    Cookie::shouldHaveReceived('queue')
        ->with('dash_date_to', '2026-03-31', 43200)
        ->once();
});

test('updating selectedFuelTypes queues a cookie', function () {
    Cookie::spy();

    Livewire::test(Dashboard::class)
        ->set('selectedFuelTypes', ['2', '4']);

    Cookie::shouldHaveReceived('queue')
        ->with('dash_fuel_types', '["2","4"]', 43200)
        ->once();
});

test('setPreset queues date cookies', function () {
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
