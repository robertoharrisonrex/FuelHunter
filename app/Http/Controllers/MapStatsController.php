<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MapStatsController extends Controller
{
    public function show(int $fuelTypeId): JsonResponse
    {
        $data = Cache::store('file')->remember("map_stats_{$fuelTypeId}", 600, function () use ($fuelTypeId) {
            $row = DB::table('prices')
                ->join('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
                ->where('prices.fuel_id', $fuelTypeId)
                ->where('prices.price', '>', 50)
                ->selectRaw('fuel_types.name as fuel_type_name, MIN(prices.price) as min_price, MAX(prices.price) as max_price, COUNT(DISTINCT prices.site_id) as site_count')
                ->groupBy('fuel_types.name')
                ->first();

            if (! $row) {
                return ['min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => ''];
            }

            return [
                'min'            => round((float) $row->min_price / 100, 3),
                'max'            => round((float) $row->max_price / 100, 3),
                'count'          => (int) $row->site_count,
                'fuel_type_name' => $row->fuel_type_name,
            ];
        });

        return response()->json($data);
    }
}
