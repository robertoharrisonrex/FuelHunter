<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapDataController extends Controller
{
    public function show(int $fuelTypeId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_data_{$fuelTypeId}", 600, function () use ($fuelTypeId) {
            $rows = DB::table('fuel_sites')
                ->join('prices', function ($join) use ($fuelTypeId) {
                    $join->on('prices.site_id', '=', 'fuel_sites.id')
                         ->where('prices.fuel_id', '=', $fuelTypeId)
                         ->where('prices.price', '>', 50);
                })
                ->leftJoin('brands',     'brands.id',     '=', 'fuel_sites.brand_id')
                ->leftJoin('suburbs',    'suburbs.id',    '=', 'fuel_sites.geo_region_1')
                ->leftJoin('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
                ->leftJoin('prices as p_ul', function ($join) {
                    $join->on('p_ul.site_id', '=', 'fuel_sites.id')
                         ->where('p_ul.fuel_id', '=', 2);
                })
                ->leftJoin('prices as p95', function ($join) {
                    $join->on('p95.site_id', '=', 'fuel_sites.id')
                         ->where('p95.fuel_id', '=', 5);
                })
                ->leftJoin('prices as p98', function ($join) {
                    $join->on('p98.site_id', '=', 'fuel_sites.id')
                         ->where('p98.fuel_id', '=', 8);
                })
                ->leftJoin('prices as p_pd', function ($join) {
                    $join->on('p_pd.site_id', '=', 'fuel_sites.id')
                         ->where('p_pd.fuel_id', '=', 14);
                })
                ->select(
                    'fuel_sites.id',
                    'fuel_sites.name',
                    'fuel_sites.latitude',
                    'fuel_sites.longitude',
                    'fuel_sites.address',
                    'fuel_sites.postcode',
                    'suburbs.name as suburb_name',
                    'prices.price',
                    'prices.transaction_date_utc',
                    'brands.name as brand_name',
                    'fuel_types.name as fuel_type_name',
                    'p_ul.price as price_ul',
                    'p95.price as price_95',
                    'p98.price as price_98',
                    'p_pd.price as price_pd',
                )
                ->get();

            if ($rows->isEmpty()) {
                return ['sites' => [], 'min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => ''];
            }

            $rawPrices    = $rows->pluck('price');
            $fuelTypeName = $rows->first()->fuel_type_name ?? '';

            $sites = $rows->map(fn($r) => [
                'id'       => $r->id,
                'name'     => $r->name,
                'lat'      => (float) $r->latitude,
                'lng'      => (float) $r->longitude,
                'addr'     => $r->address,
                'suburb'   => $r->suburb_name ?? '',
                'postcode' => $r->postcode,
                'price'    => round($r->price / 100, 3),
                'updated'  => $r->transaction_date_utc,
                'brand'    => $r->brand_name ?? '',
                'price_ul' => $r->price_ul ? round($r->price_ul / 100, 3) : null,
                'price_95' => $r->price_95 ? round($r->price_95 / 100, 3) : null,
                'price_98' => $r->price_98 ? round($r->price_98 / 100, 3) : null,
                'price_pd' => $r->price_pd ? round($r->price_pd / 100, 3) : null,
            ])->values()->toArray();

            return [
                'sites'          => $sites,
                'min'            => round($rawPrices->min() / 100, 2),
                'max'            => round($rawPrices->max() / 100, 2),
                'count'          => count($sites),
                'fuel_type_name' => $fuelTypeName,
            ];
        });

        return response()->json($data);
    }
}
