<?php

namespace App\Livewire;

use App\Models\FuelType;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class FuelMap extends Component
{
    public int  $selectedFuelTypeId = 2; // Unleaded
    public $fuelTypes;

    public function mount(): void
    {
        $this->selectedFuelTypeId = (int) session('fuelmap_fuel_type', 2);

        $orderedIds = [2, 14, 3, 5, 8, 12, 4]; // Unleaded, Premium Diesel, Diesel, Premium 95, Premium 98, e10, LPG

        $this->fuelTypes = FuelType::select('fuel_types.*')
            ->join('prices', 'prices.fuel_id', '=', 'fuel_types.id')
            ->whereIn('fuel_types.id', $orderedIds)
            ->groupBy('fuel_types.id', 'fuel_types.name')
            ->get()
            ->sortBy(fn($ft) => array_search($ft->id, $orderedIds))
            ->values();
    }

    public function updatedSelectedFuelTypeId(): void
    {
        session(['fuelmap_fuel_type' => $this->selectedFuelTypeId]);
        $this->dispatch('fuelTypeChanged', fuelTypeId: $this->selectedFuelTypeId);
    }

    public function render()
    {
        return view('livewire.fuel-map');
    }
}
