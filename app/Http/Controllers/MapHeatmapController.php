<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapHeatmapController extends Controller
{
    public function cities(int $fuelTypeId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_heatmap_city_{$fuelTypeId}", 600, function () use ($fuelTypeId) {
            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('cities', 'cities.id', '=', 'fuel_sites.geo_region_2')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->groupBy('cities.id', 'cities.name')
                ->selectRaw('
                    cities.id   AS city_id,
                    cities.name AS city_name,
                    AVG(fuel_sites.latitude)  AS lat,
                    AVG(fuel_sites.longitude) AS lng,
                    AVG(prices.price)         AS avg_price,
                    COUNT(DISTINCT fuel_sites.id) AS site_count
                ')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $totalSites   = $rows->sum('site_count');
            $statewideAvg = $totalSites > 0
                ? $rows->sum(fn($r) => (float) $r->avg_price * (int) $r->site_count) / $totalSites
                : 0;

            return $rows->map(fn($r) => [
                'city_id'    => (int)   $r->city_id,
                'city_name'  =>         $r->city_name,
                'lat'        => (float) $r->lat,
                'lng'        => (float) $r->lng,
                'avg_price'  => (int)   round($r->avg_price),
                'deviation'  => (int)   round($r->avg_price - $statewideAvg),
                'site_count' => (int)   $r->site_count,
            ])->values()->toArray();
        });

        return response()->json($data);
    }

    public function suburbs(int $fuelTypeId, int $cityId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_heatmap_suburb_{$fuelTypeId}_{$cityId}", 600, function () use ($fuelTypeId, $cityId) {
            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('suburbs', 'suburbs.id', '=', 'fuel_sites.geo_region_1')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->where('fuel_sites.geo_region_2', $cityId)
                ->groupBy('suburbs.id', 'suburbs.name')
                ->selectRaw('
                    suburbs.id   AS suburb_id,
                    suburbs.name AS suburb_name,
                    AVG(fuel_sites.latitude)  AS lat,
                    AVG(fuel_sites.longitude) AS lng,
                    AVG(prices.price)         AS avg_price,
                    COUNT(DISTINCT fuel_sites.id) AS site_count
                ')
                ->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $totalSites = $rows->sum('site_count');
            $areaAvg    = $totalSites > 0
                ? $rows->sum(fn($r) => (float) $r->avg_price * (int) $r->site_count) / $totalSites
                : 0;

            return $rows->map(fn($r) => [
                'suburb_id'   => (int)   $r->suburb_id,
                'suburb_name' =>         $r->suburb_name,
                'lat'         => (float) $r->lat,
                'lng'         => (float) $r->lng,
                'avg_price'   => (int)   round($r->avg_price),
                'deviation'   => (int)   round($r->avg_price - $areaAvg),
                'site_count'  => (int)   $r->site_count,
            ])->values()->toArray();
        });

        return response()->json($data);
    }
}
