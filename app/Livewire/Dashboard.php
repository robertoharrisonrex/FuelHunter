<?php

namespace App\Livewire;

use App\Models\FuelType;
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
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo   = now()->format('Y-m-d');
        $this->fuelTypes = FuelType::orderBy('name')->get();

        $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
            ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false);
        $this->selectedFuelTypes = $unleaded ? [(string) $unleaded->id] : [];
    }

    private function chartData(): array
    {
        if (empty($this->selectedFuelTypes)) {
            return ['labels' => [], 'datasets' => []];
        }

        $dateFrom = $this->dateFrom ?: now()->subDays(30)->format('Y-m-d');
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

        $allDates  = $rows->pluck('date')->unique()->sort()->values()->toArray();
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

    public function applyFilters(): void {}

    public function render()
    {
        $chartData = $this->chartData();

        $this->dispatch('chartUpdated', labels: $chartData['labels'], datasets: $chartData['datasets']);

        return view('livewire.dashboard', compact('chartData'));
    }
}
