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
        $orderedIds = [2, 14, 3, 5, 8, 12, 4]; // Unleaded, Premium Diesel, Diesel, Premium 95, Premium 98, e10, LPG

        $this->fuelTypes = FuelType::select('fuel_types.*')
            ->join('prices', 'prices.fuel_id', '=', 'fuel_types.id')
            ->whereIn('fuel_types.id', $orderedIds)
            ->groupBy('fuel_types.id', 'fuel_types.name')
            ->get()
            ->sortBy(fn($ft) => array_search($ft->id, $orderedIds))
            ->values();

        $validIds = $this->fuelTypes->pluck('id')->map(fn($id) => (string) $id)->all();

        $savedFuelTypes = Cookie::get('dash_fuel_types');
        if ($savedFuelTypes !== null) {
            $decoded = json_decode($savedFuelTypes, true) ?? [];
            $this->selectedFuelTypes = array_values(array_filter($decoded, fn($id) => in_array($id, $validIds)));
        }

        if (empty($this->selectedFuelTypes)) {
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

        Cookie::queue('dash_date_from', $this->dateFrom, 43200);
        Cookie::queue('dash_date_to',   $this->dateTo,   43200);
    }

    public function applyFilters(): void {}

    public function updatedDateFrom(): void
    {
        Cookie::queue('dash_date_from', $this->dateFrom, 43200);
    }

    public function updatedDateTo(): void
    {
        Cookie::queue('dash_date_to', $this->dateTo, 43200);
    }

    public function updatedSelectedFuelTypes(): void
    {
        Cookie::queue('dash_fuel_types', json_encode($this->selectedFuelTypes), 43200);
    }

    private function cacheKey(string $prefix): string
    {
        return $prefix . '_' . md5(
            implode(',', $this->selectedFuelTypes) . '_' . $this->dateFrom . '_' . $this->dateTo
        );
    }

    private function roundAvg(string $col, int $decimals): string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? "round(avg({$col})::numeric, {$decimals})"
            : "round(avg({$col}), {$decimals})";
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
                    ->selectRaw('fuel_types.name as fuel_type_name, ' . $this->roundAvg('prices.price', 1) . ' as avg_price, count(distinct prices.site_id) as site_count')
                    ->groupBy('fuel_types.name')
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

    private function brandMarketShareData(): array
    {
        return Cache::store('file')->remember('dash_brand_market_share', 3600, function () {
            $rows = DB::table('fuel_sites')
                ->join('brands', 'brands.id', '=', 'fuel_sites.brand_id')
                ->groupBy('brands.id', 'brands.name')
                ->selectRaw('brands.name as brand_name, COUNT(fuel_sites.id) as site_count')
                ->orderByDesc('site_count')
                ->get();

            $total = $rows->sum('site_count');
            if ($total === 0) {
                return ['labels' => [], 'values' => [], 'counts' => []];
            }

            return [
                'labels' => $rows->pluck('brand_name')->toArray(),
                'values' => $rows->map(fn($r) => round($r->site_count / $total * 100, 1))->toArray(),
                'counts' => $rows->pluck('site_count')->map(fn($v) => (int) $v)->toArray(),
            ];
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
                ->selectRaw('fuel_id, date, ' . $this->roundAvg('price', 2) . ' as avg_price')
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
        $chartData   = $this->chartData();
        $stats       = $this->summaryStats();
        $brandShare  = $this->brandMarketShareData();

        $this->dispatch('chartUpdated',      labels: $chartData['labels'],  datasets: $chartData['datasets']);
        $this->dispatch('brandShareUpdated', labels: $brandShare['labels'], values: $brandShare['values'], counts: $brandShare['counts']);

        return view('livewire.dashboard', compact('chartData', 'stats', 'brandShare'));
    }
}
