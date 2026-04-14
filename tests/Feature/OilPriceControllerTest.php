<?php

use Illuminate\Support\Facades\Cache;

test('oil prices endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'dates'  => ['2026-04-01', '2026-04-02'],
            'series' => [
                'WTI_USD'          => [74.52, 75.10],
                'BRENT_CRUDE_USD'  => [78.30, 79.05],
                'NATURAL_GAS_USD'  => [2.45,  2.50],
                'GASOLINE_USD'     => [2.35,  2.40],
            ],
        ]);

    $this->getJson('/oil-prices')
         ->assertOk()
         ->assertJsonStructure(['dates', 'series'])
         ->assertJsonStructure(['series' => [
             'WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD',
         ]])
         ->assertJsonPath('series.WTI_USD.0', 74.52);
});

test('oil prices endpoint returns empty data when no prices exist', function () {
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
         ->assertJsonPath('dates', []);
});
