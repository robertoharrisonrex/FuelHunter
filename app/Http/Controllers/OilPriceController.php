<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OilPriceController extends Controller
{
    public function index(): JsonResponse
    {
        $data = Cache::store('file')->remember('oil_prices_chart', 300, function () {
            $codes  = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD'];
            $cutoff = now()->subDays(30)->toDateString();

            $rows = DB::table('oil_prices')
                ->whereIn('code', $codes)
                ->whereRaw('DATE(recorded_at) >= ?', [$cutoff])
                ->selectRaw('code, DATE(recorded_at) as date, ROUND(AVG(price), 2) as avg_price')
                ->groupBy('code', DB::raw('DATE(recorded_at)'))
                ->orderBy('date')
                ->get();

            $dates = $rows->pluck('date')->unique()->sort()->values()->toArray();

            $series = [];
            foreach ($codes as $code) {
                $byDate        = $rows->where('code', $code)->keyBy('date');
                $series[$code] = array_map(
                    fn($date) => ($r = $byDate->get($date)) ? (float) $r->avg_price : null,
                    $dates
                );
            }

            return compact('dates', 'series');
        });

        return response()->json($data);
    }
}
