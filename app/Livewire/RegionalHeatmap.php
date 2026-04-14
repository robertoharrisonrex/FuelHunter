<?php

namespace App\Livewire;

use App\Models\FuelType;
use Livewire\Component;

class RegionalHeatmap extends Component
{
    public int $selectedFuelTypeId = 0;
    public $fuelTypes = [];

    public function mount(): void
    {
        $this->fuelTypes = FuelType::orderBy('name')->get();

        $unleaded = $this->fuelTypes->first(fn($t) => strtolower($t->name) === 'unleaded')
            ?? $this->fuelTypes->first(fn($t) => stripos($t->name, 'unleaded') !== false)
            ?? $this->fuelTypes->first();

        $this->selectedFuelTypeId = $unleaded ? $unleaded->id : 0;
    }

    public function selectFuelType(int $id): void
    {
        $this->selectedFuelTypeId = $id;
        $this->dispatch('heatmapFuelChanged', fuelTypeId: $id);
    }

    public function render()
    {
        return view('livewire.regional-heatmap');
    }
}
