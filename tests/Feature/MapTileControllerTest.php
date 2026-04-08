<?php

use App\Http\Controllers\MapTileController;
use Illuminate\Support\Facades\Cache;

test('map tile endpoint returns sites array', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')
        ->once()
        ->andReturn([
            'sites' => [
                [
                    'id'       => 1,
                    'name'     => 'BP Newmarket',
                    'lat'      => -27.45,
                    'lng'      => 153.01,
                    'addr'     => '123 Test St',
                    'suburb'   => 'Newmarket',
                    'postcode' => 4051,
                    'price'    => 1.899,
                    'updated'  => '2026-04-01T06:00:00',
                    'brand'    => 'BP',
                    'price_ul' => 1.899,
                    'price_95' => null,
                    'price_98' => null,
                    'price_pd' => null,
                ],
            ],
        ]);

    $response = $this->getJson('/map-tiles/2/-55/306');

    $response->assertOk()
             ->assertJsonStructure(['sites'])
             ->assertJsonCount(1, 'sites')
             ->assertJsonPath('sites.0.id', 1)
             ->assertJsonPath('sites.0.brand', 'BP');
});

test('map tile returns empty sites for tile with no stations', function () {
    Cache::shouldReceive('store')->with('file')->andReturnSelf();
    Cache::shouldReceive('remember')->once()->andReturn(['sites' => []]);

    $response = $this->getJson('/map-tiles/2/-58/270');

    $response->assertOk()->assertJsonPath('sites', []);
});

test('tile bounds calculation is correct for southern hemisphere', function () {
    // latTile=-55 → south=-27.5, north=-27.0
    // lngTile=306 → west=153.0, east=153.5
    $bounds = MapTileController::tileBounds(-55, 306);

    expect($bounds['south'])->toBe(-27.5)
        ->and($bounds['north'])->toBe(-27.0)
        ->and($bounds['west'])->toBe(153.0)
        ->and($bounds['east'])->toBe(153.5);
});

test('tile bounds calculation is correct for negative lng tile', function () {
    // lngTile=-1 → west=-0.5, east=0.0
    $bounds = MapTileController::tileBounds(-55, -1);

    expect($bounds['west'])->toBe(-0.5)
        ->and($bounds['east'])->toBe(0.0);
});
