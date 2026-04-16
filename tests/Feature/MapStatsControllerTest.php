<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

test('map stats endpoint returns correct json structure including last_checked_at', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'min'            => 1.699,
            'max'            => 2.059,
            'count'          => 350,
            'fuel_type_name' => 'Unleaded',
        ]);

    $response = $this->getJson('/map-stats/2');

    $response->assertOk()
             ->assertJsonStructure(['min', 'max', 'count', 'fuel_type_name', 'last_checked_at'])
             ->assertJsonFragment(['fuel_type_name' => 'Unleaded'])
             ->assertJsonPath('min', 1.699)
             ->assertJsonPath('max', 2.059)
             ->assertJsonPath('count', 350)
             ->assertJsonPath('last_checked_at', null);
});

test('map stats returns zeros for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => '']);

    $response = $this->getJson('/map-stats/999');

    $response->assertOk()
             ->assertJsonPath('count', 0)
             ->assertJsonPath('fuel_type_name', '')
             ->assertJsonPath('last_checked_at', null);
});

test('map stats returns last_checked_at from settings table when present', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 1.699, 'max' => 2.059, 'count' => 350, 'fuel_type_name' => 'Unleaded']);

    DB::table('settings')->insert([
        'key'   => 'last_prices_checked_at',
        'value' => '2026-04-16 08:30:00',
    ]);

    $response = $this->getJson('/map-stats/2');

    $response->assertOk()
             ->assertJsonPath('last_checked_at', '2026-04-16 08:30:00');
});
