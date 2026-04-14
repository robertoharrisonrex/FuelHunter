<?php

use Illuminate\Support\Facades\Cache;

test('cities endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'city_id'    => 5,
                'city_name'  => 'Brisbane',
                'lat'        => -27.471,
                'lng'        => 153.024,
                'avg_price'  => 20752,
                'deviation'  => 412,
                'site_count' => 142,
            ],
        ]);

    $response = $this->getJson('/map-heatmap/2');

    $response->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.city_id', 5)
             ->assertJsonPath('0.city_name', 'Brisbane')
             ->assertJsonPath('0.avg_price', 20752)
             ->assertJsonPath('0.lat', -27.471)
             ->assertJsonPath('0.lng', 153.024)
             ->assertJsonPath('0.deviation', 412)
             ->assertJsonPath('0.site_count', 142);
});

test('cities endpoint returns empty array for unknown fuel type', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn([]);

    $response = $this->getJson('/map-heatmap/999');

    $response->assertOk()->assertExactJson([]);
});

test('suburbs endpoint returns correct json structure', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            [
                'suburb_id'   => 12,
                'suburb_name' => 'Newmarket',
                'lat'         => -27.441,
                'lng'         => 153.012,
                'avg_price'   => 20100,
                'deviation'   => -240,
                'site_count'  => 4,
            ],
        ]);

    $response = $this->getJson('/map-heatmap/2/city/5');

    $response->assertOk()
             ->assertJsonCount(1)
             ->assertJsonPath('0.suburb_id', 12)
             ->assertJsonPath('0.suburb_name', 'Newmarket')
             ->assertJsonPath('0.avg_price', 20100)
             ->assertJsonPath('0.lat', -27.441)
             ->assertJsonPath('0.lng', 153.012)
             ->assertJsonPath('0.deviation', -240)
             ->assertJsonPath('0.site_count', 4);
});
