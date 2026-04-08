<?php

namespace App\Livewire;

use App\Models\FuelType;
use Livewire\Component;

class FuelMap extends Component
{
    public int  $selectedFuelTypeId = 2; // Unleaded
    public $fuelTypes;

    public function mount(): void
    {
        $this->fuelTypes = FuelType::orderBy('name')->get();
    }

    public function updatedSelectedFuelTypeId(): void
    {
        $this->dispatch('fuelTypeChanged', fuelTypeId: $this->selectedFuelTypeId);
    }

    public function render()
    {
        return view('livewire.fuel-map');
    }
}
