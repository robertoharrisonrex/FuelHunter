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
                ? "to_char(date_trunc('hour', (recorded_at AT TIME ZONE 'UTC') AT TIME ZONE 'Australia/Brisbane') + floor(date_part('minute', (recorded_at AT TIME ZONE 'UTC') AT TIME ZONE 'Australia/Brisbane') / 30) * interval '30 min', 'YYYY-MM-DD HH24:MI')"
                : "strftime('%Y-%m-%d %H:', datetime(recorded_at, '+10 hours')) || printf('%02d', (cast(strftime('%M', datetime(recorded_at, '+10 hours')) as integer) / 30) * 30)";

            $rows = DB::table('oil_prices')
                ->whereIn('code', $codes)
                ->whereRaw('recorded_at >= ?', [now()->utc()->subHours(72)])
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

            // Strip weekend dates and insert a null break at each session boundary,
            // so the chart pauses (line ends Friday, resumes Monday) with no empty x-axis space.
            $filteredDates  = [];
            $filteredSeries = array_fill_keys($codes, []);
            $needsBreak     = false;

            foreach ($dates as $i => $date) {
                $dow = (int) \Carbon\Carbon::createFromFormat('Y-m-d H:i', $date)->dayOfWeek;
                if ($dow === 0 || $dow === 6) {
                    if (!empty($filteredDates)) {
                        $needsBreak = true;
                    }
                    continue;
                }

                if ($needsBreak) {
                    $filteredDates[] = $date;
                    foreach ($codes as $code) {
                        $filteredSeries[$code][] = null;
                    }
                    $needsBreak = false;
                }

                $filteredDates[] = $date;
                foreach ($codes as $code) {
                    $filteredSeries[$code][] = $series[$code][$i];
                }
            }

            $dates  = $filteredDates;
            $series = $filteredSeries;

            return compact('dates', 'series');
        });

        $day = (int) now()->setTimezone('Australia/Brisbane')->dayOfWeek;
        $data['market_open'] = !in_array($day, [0, 6]); // 0 = Sunday, 6 = Saturday

        return response()->json($data);
    }
}
