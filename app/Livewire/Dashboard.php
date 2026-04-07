<?php

namespace App\Livewire;

use App\Models\FuelType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public string $dateFrom = '';
    public string $dateTo = '';
    public array $selectedFuelTypes = [];

    public $fuelTypes = [];

    public function mount(): void
    {
        $this->dateFrom  = Cookie::get('dash_date_from') ?? now()->subDays(29)->format('Y-m-d');
        $this->dateTo    = Cookie::get('dash_date_to')   ?? now()->format('Y-m-d');
        $this->fuelTypes = FuelType::orderBy('name')->get();

        $savedFuelTypes = Cookie::get('dash_fuel_types');
        if ($savedFuelTypes !== null) {
            $this->selectedFuelTypes = json_decode($savedFuelTypes, true) ?? [];
        } else {
            $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
                ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false);
            $this->selectedFuelTypes = $unleaded ? [(string) $unleaded->id] : [];
        }
    }

    public function setPreset(string $preset): void
    {
        [$this->dateFrom, $this->dateTo] = match ($preset) {
            '7d'  => [now()->subDays(6)->format('Y-m-d'),        now()->format('Y-m-d')],
            '30d' => [now()->subDays(29)->format('Y-m-d'),       now()->format('Y-m-d')],
            '90d' => [now()->subDays(89)->format('Y-m-d'),       now()->format('Y-m-d')],
            '1yr' => [now()->subYear()->addDay()->format('Y-m-d'), now()->format('Y-m-d')],
            default => [$this->dateFrom, $this->dateTo],
        };
    }

    public function applyFilters(): void {}

    private function cacheKey(string $prefix): string
    {
        return $prefix . '_' . md5(
            implode(',', $this->selectedFuelTypes) . '_' . $this->dateFrom . '_' . $this->dateTo
        );
    }

    private function summaryStats(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_stats'), 300, function () {
            $primaryId = $this->selectedFuelTypes[0] ?? null;
            $avgPrice  = null;
            $siteCount = 0;
            $fuelName  = '';

            if ($primaryId) {
                $row = DB::table('prices')
                    ->join('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
                    ->where('prices.fuel_id', $primaryId)
                    ->where('prices.price', '>', 50)
                    ->selectRaw('fuel_types.name as fuel_type_name, round(avg(prices.price), 1) as avg_price, count(distinct prices.site_id) as site_count')
                    ->first();

                if ($row) {
                    $avgPrice  = round((float) $row->avg_price / 100, 3);
                    $siteCount = (int)   $row->site_count;
                    $fuelName  = $row->fuel_type_name;
                }
            }

            $from = Carbon::parse($this->dateFrom ?: now()->subDays(29)->format('Y-m-d'));
            $to   = Carbon::parse($this->dateTo   ?: now()->format('Y-m-d'));

            return [
                'avg_price'  => $avgPrice,
                'site_count' => $siteCount,
                'fuel_name'  => $fuelName,
                'day_count'  => (int) $from->diffInDays($to) + 1,
                'fuel_count' => count($this->selectedFuelTypes),
            ];
        });
    }

    private function brandsChartData(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_brands'), 300, function () {
            $primaryId = $this->selectedFuelTypes[0] ?? null;
            if (!$primaryId) {
                return ['labels' => [], 'values' => []];
            }

            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('brands', 'brands.id', '=', 'fuel_sites.brand_id')
                ->where('prices.fuel_id', $primaryId)
                ->where('prices.price', '>', 50)
                ->groupBy('brands.id', 'brands.name')
                ->havingRaw('COUNT(DISTINCT prices.site_id) >= 5')
                ->selectRaw('brands.name as brand_name, round(avg(prices.price), 1) as avg_price')
                ->orderBy('avg_price')
                ->limit(10)
                ->get();

            return [
                'labels' => $rows->pluck('brand_name')->toArray(),
                'values' => $rows->pluck('avg_price')->map(fn($v) => round((float) $v, 1))->toArray(),
            ];
        });
    }

    private function regionsChartData(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_regions'), 300, function () {
            $primaryId = $this->selectedFuelTypes[0] ?? null;
            if (!$primaryId) {
                return ['labels' => [], 'values' => []];
            }

            $rows = DB::table('prices')
                ->join('fuel_sites', 'fuel_sites.id', '=', 'prices.site_id')
                ->join('cities', 'cities.id', '=', 'fuel_sites.geo_region_2')
                ->where('prices.fuel_id', $primaryId)
                ->where('prices.price', '>', 50)
                ->groupBy('cities.id', 'cities.name')
                ->havingRaw('COUNT(DISTINCT prices.site_id) >= 3')
                ->selectRaw('cities.name as city_name, round(avg(prices.price), 1) as avg_price')
                ->orderBy('avg_price')
                ->limit(12)
                ->get();

            return [
                'labels' => $rows->pluck('city_name')->toArray(),
                'values' => $rows->pluck('avg_price')->map(fn($v) => round((float) $v, 1))->toArray(),
            ];
        });
    }

    private function weeklyChartData(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_weekly'), 300, function () {
            if (empty($this->selectedFuelTypes)) {
                return ['datasets' => []];
            }

            $dateFrom = $this->dateFrom ?: now()->subDays(29)->format('Y-m-d');
            $dateTo   = $this->dateTo   ?: now()->format('Y-m-d');
            $ids      = $this->selectedFuelTypes;

            $historical = DB::table('historical_site_prices')
                ->whereIn('fuel_id', $ids)
                ->where('price', '>', 50)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->selectRaw("fuel_id, STRFTIME('%w', transaction_date_utc) as day_of_week, price");

            $rows = DB::query()
                ->fromSub(
                    DB::table('prices')
                        ->whereIn('fuel_id', $ids)
                        ->where('price', '>', 50)
                        ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                        ->selectRaw("fuel_id, STRFTIME('%w', transaction_date_utc) as day_of_week, price")
                        ->unionAll($historical),
                    'combined'
                )
                ->selectRaw('fuel_id, day_of_week, round(avg(price), 1) as avg_price')
                ->groupBy('fuel_id', 'day_of_week')
                ->get();

            $byFuelType = $rows->groupBy('fuel_id');

            $datasets = [];
            foreach ($ids as $fuelTypeId) {
                $fuelType = $this->fuelTypes->firstWhere('id', $fuelTypeId);
                $byDay    = $byFuelType->get($fuelTypeId, collect())->keyBy('day_of_week');

                $data = [];
                for ($d = 0; $d < 7; $d++) {
                    $row    = $byDay->get((string) $d);
                    $data[] = $row ? round((float) $row->avg_price, 1) : null;
                }

                $datasets[] = [
                    'label' => $fuelType?->name ?? "Fuel {$fuelTypeId}",
                    'data'  => $data,
                ];
            }

            return ['datasets' => $datasets];
        });
    }

    private function availabilityChartData(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_availability'), 300, function () {
            if (empty($this->selectedFuelTypes)) {
                return ['labels' => [], 'datasets' => []];
            }

            $dateFrom = $this->dateFrom ?: now()->subDays(29)->format('Y-m-d');
            $dateTo   = $this->dateTo   ?: now()->format('Y-m-d');
            $ids      = $this->selectedFuelTypes;

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            // params: all_daily ×2 (no fuel filter), selected_fuel_daily ×2 (with fuel filter)
            $params = array_merge(
                [$dateFrom, $dateTo],
                [$dateFrom, $dateTo],
                [$dateFrom, $dateTo], $ids,
                [$dateFrom, $dateTo], $ids,
            );

            $sql = "
                WITH all_daily AS (
                    SELECT site_id, transaction_date FROM prices
                    WHERE price > 50 AND transaction_date BETWEEN ? AND ?
                    UNION ALL
                    SELECT site_id, transaction_date FROM historical_site_prices
                    WHERE price > 50 AND transaction_date BETWEEN ? AND ?
                ),
                active_per_day AS (
                    SELECT transaction_date, COUNT(DISTINCT site_id) AS total_active
                    FROM all_daily
                    GROUP BY transaction_date
                ),
                selected_fuel_daily AS (
                    SELECT site_id, fuel_id, transaction_date FROM prices
                    WHERE price > 50 AND transaction_date BETWEEN ? AND ? AND fuel_id IN ({$placeholders})
                    UNION ALL
                    SELECT site_id, fuel_id, transaction_date FROM historical_site_prices
                    WHERE price > 50 AND transaction_date BETWEEN ? AND ? AND fuel_id IN ({$placeholders})
                ),
                with_fuel_per_day AS (
                    SELECT fuel_id, transaction_date, COUNT(DISTINCT site_id) AS sites_with_fuel
                    FROM selected_fuel_daily
                    GROUP BY fuel_id, transaction_date
                )
                SELECT w.fuel_id, ft.name AS fuel_name, w.transaction_date,
                       (a.total_active - w.sites_with_fuel) AS sites_without_fuel
                FROM with_fuel_per_day w
                JOIN active_per_day a ON a.transaction_date = w.transaction_date
                JOIN fuel_types ft ON ft.id = w.fuel_id
                ORDER BY w.fuel_id, w.transaction_date
            ";

            $rows = collect(DB::select($sql, $params));

            $allDates   = $rows->pluck('transaction_date')->unique()->sort()->values()->toArray();
            $byFuelType = $rows->groupBy('fuel_id');

            $datasets = [];
            foreach ($ids as $fuelTypeId) {
                $group  = $byFuelType->get($fuelTypeId, collect());
                $byDate = $group->keyBy('transaction_date');
                $name   = $group->first()?->fuel_name ?? "Fuel {$fuelTypeId}";

                $data = array_map(
                    fn($date) => ($row = $byDate->get($date)) ? (int) $row->sites_without_fuel : null,
                    $allDates
                );

                $datasets[] = [
                    'label' => $name,
                    'data'  => array_values($data),
                ];
            }

            return ['labels' => $allDates, 'datasets' => $datasets];
        });
    }

    private function chartData(): array
    {
        return Cache::store('file')->remember($this->cacheKey('dash_chart'), 300, function () {
            if (empty($this->selectedFuelTypes)) {
                return ['labels' => [], 'datasets' => []];
            }

            $dateFrom = $this->dateFrom ?: now()->subDays(29)->format('Y-m-d');
            $dateTo   = $this->dateTo   ?: now()->format('Y-m-d');
            $ids      = $this->selectedFuelTypes;

            $historical = DB::table('historical_site_prices')
                ->whereIn('fuel_id', $ids)
                ->where('price', '>', 0)
                ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                ->selectRaw('fuel_id, transaction_date as date, price');

            $rows = DB::query()
                ->fromSub(
                    DB::table('prices')
                        ->whereIn('fuel_id', $ids)
                        ->where('price', '>', 0)
                        ->whereBetween('transaction_date', [$dateFrom, $dateTo])
                        ->selectRaw('fuel_id, transaction_date as date, price')
                        ->unionAll($historical),
                    'combined'
                )
                ->selectRaw('fuel_id, date, round(avg(price), 2) as avg_price')
                ->groupBy('fuel_id', 'date')
                ->orderBy('date')
                ->get();

            $allDates   = $rows->pluck('date')->unique()->sort()->values()->toArray();
            $byFuelType = $rows->groupBy('fuel_id');

            $datasets = [];
            foreach ($ids as $fuelTypeId) {
                $fuelType = $this->fuelTypes->firstWhere('id', $fuelTypeId);
                $byDate   = $byFuelType->get($fuelTypeId, collect())->keyBy('date');

                $data = array_map(
                    fn($date) => ($row = $byDate->get($date)) ? (float) $row->avg_price : null,
                    $allDates
                );

                $datasets[] = [
                    'label' => $fuelType?->name ?? "Fuel {$fuelTypeId}",
                    'data'  => array_values($data),
                ];
            }

            return ['labels' => $allDates, 'datasets' => $datasets];
        });
    }

    public function render()
    {
        $chartData        = $this->chartData();
        $stats            = $this->summaryStats();
        $brandsChart      = $this->brandsChartData();
        $regionsChart     = $this->regionsChartData();
        $weeklyChart      = $this->weeklyChartData();
        $availabilityChart = $this->availabilityChartData();

        $this->dispatch('chartUpdated',            labels: $chartData['labels'],        datasets: $chartData['datasets']);
        $this->dispatch('brandsChartUpdated',       labels: $brandsChart['labels'],      values: $brandsChart['values']);
        $this->dispatch('regionsChartUpdated',      labels: $regionsChart['labels'],     values: $regionsChart['values']);
        $this->dispatch('weeklyChartUpdated',       datasets: $weeklyChart['datasets']);
        $this->dispatch('availabilityChartUpdated', labels: $availabilityChart['labels'], datasets: $availabilityChart['datasets']);

        return view('livewire.dashboard', compact('chartData', 'stats', 'brandsChart', 'regionsChart', 'weeklyChart', 'availabilityChart'));
    }
}
