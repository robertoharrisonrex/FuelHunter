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
            $codes = ['WTI_USD', 'BRENT_CRUDE_USD', 'NATURAL_GAS_USD', 'GASOLINE_USD'];

            $isPgsql  = DB::connection()->getDriverName() === 'pgsql';
            $bucket   = $isPgsql
                ? "to_char(to_timestamp(floor(extract(epoch from recorded_at) / 1800) * 1800), 'YYYY-MM-DD HH24:MI')"
                : "strftime('%Y-%m-%d %H:', recorded_at) || printf('%02d', (cast(strftime('%M', recorded_at) as integer) / 30) * 30)";

            $rows = DB::table('oil_prices')
                ->whereIn('code', $codes)
                ->whereRaw('recorded_at >= ?', [now()->subHours(72)])
                ->selectRaw("code, {$bucket} as bucket, ROUND(AVG(price), 2) as avg_price")
                ->groupBy('code', DB::raw($bucket))
                ->orderBy('bucket')
                ->get();

            $dates = $rows->pluck('bucket')->unique()->sort()->values()->toArray();

            $series = [];
            foreach ($codes as $code) {
                $byBucket      = $rows->where('code', $code)->keyBy('bucket');
                $series[$code] = array_map(
                    fn($b) => ($r = $byBucket->get($b)) ? (float) $r->avg_price : null,
                    $dates
                );
            }

            return compact('dates', 'series');
        });

        return response()->json($data);
    }
}
