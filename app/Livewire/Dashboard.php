<?php

namespace App\Livewire;

use App\Models\FuelType;
use Carbon\Carbon;
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
        $this->dateFrom = now()->subDays(29)->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
        $this->fuelTypes = FuelType::orderBy('name')->get();

        $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
            ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false);
        $this->selectedFuelTypes = $unleaded ? [(string) $unleaded->id] : [];
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

    private function summaryStats(): array
    {
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
    }

    private function chartData(): array
    {
        if (empty($this->selectedFuelTypes)) {
            return ['labels' => [], 'datasets' => []];
        }

        $dateFrom = $this->dateFrom ?: now()->subDays(29)->format('Y-m-d');
        $dateTo   = $this->dateTo   ?: now()->format('Y-m-d');
        $ids      = $this->selectedFuelTypes;

        $historical = DB::table('historical_site_prices')
            ->whereIn('fuel_id', $ids)
            ->where('price', '>', 0)
            ->whereBetween(DB::raw('date(transaction_date_utc)'), [$dateFrom, $dateTo])
            ->selectRaw('fuel_id, date(transaction_date_utc) as date, price');

        $rows = DB::query()
            ->fromSub(
                DB::table('prices')
                    ->whereIn('fuel_id', $ids)
                    ->where('price', '>', 0)
                    ->whereBetween(DB::raw('date(transaction_date_utc)'), [$dateFrom, $dateTo])
                    ->selectRaw('fuel_id, date(transaction_date_utc) as date, price')
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
    }

    public function render()
    {
        $chartData = $this->chartData();
        $stats     = $this->summaryStats();

        $this->dispatch('chartUpdated', labels: $chartData['labels'], datasets: $chartData['datasets']);

        return view('livewire.dashboard', compact('chartData', 'stats'));
    }
}
