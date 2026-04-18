<?php

use Illuminate\Support\Facades\Cache;

test('oil prices endpoint returns correct json structure', function () {
    // Wednesday 2026-04-15 12:00 AEST = 2026-04-15 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-15 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => ['2026-04-01 00:00:00', '2026-04-01 00:20:00'],
            'series' => [
                'WTI_USD'          => [74.52, 75.10],
                'BRENT_CRUDE_USD'  => [78.30, 79.05],
                'NATURAL_GAS_USD'  => [2.45,  2.50],
                'GASOLINE_USD'     => [2.35,  2.40],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonStructure(['dates', 'series', 'market_open'])
         ->assertJsonStructure(['series' => [
             'WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD',
         ]])
         ->assertJsonPath('series.WTI_USD.0', 74.52)
         ->assertJsonPath('market_open', true);

    \Carbon\Carbon::setTestNow();
});

test('oil prices endpoint returns empty data when no prices exist', function () {
    // Thursday 2026-04-16 12:00 AEST = 2026-04-16 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-16 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => [],
            'series' => [
                'WTI_USD'         => [],
                'BRENT_CRUDE_USD' => [],
                'NATURAL_GAS_USD' => [],
                'GASOLINE_USD'    => [],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('dates', [])
         ->assertJsonPath('market_open', true);

    \Carbon\Carbon::setTestNow();
});

test('market_open is false on Saturday in Brisbane time', function () {
    // Saturday 2026-04-18 12:00 AEST = 2026-04-18 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-18 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', false);

    \Carbon\Carbon::setTestNow();
});

test('market_open is false on Sunday in Brisbane time', function () {
    // Sunday 2026-04-19 12:00 AEST = 2026-04-19 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-19 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', false);

    \Carbon\Carbon::setTestNow();
});

test('market_open is true on a weekday in Brisbane time', function () {
    // Monday 2026-04-14 12:00 AEST = 2026-04-14 02:00 UTC
    \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-04-14 02:00:00', 'UTC'));

    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([
        'dates' => [], 'series' => [],
    ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonPath('market_open', true);

    \Carbon\Carbon::setTestNow();
});
