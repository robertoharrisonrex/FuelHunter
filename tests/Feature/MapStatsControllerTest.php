<?php

use Illuminate\Support\Facades\Cache;

test('map stats endpoint returns correct json structure', function () {
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
             ->assertJsonStructure(['min', 'max', 'count', 'fuel_type_name'])
             ->assertJsonFragment(['fuel_type_name' => 'Unleaded'])
             ->assertJsonPath('min', 1.699)
             ->assertJsonPath('max', 2.059)
             ->assertJsonPath('count', 350);
});

test('map stats returns zeros for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => '']);

    $response = $this->getJson('/map-stats/999');

    $response->assertOk()
             ->assertJsonPath('count', 0)
             ->assertJsonPath('fuel_type_name', '');
});
