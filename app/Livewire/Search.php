<?php

namespace App\Livewire;

use App\Models\FuelSite;
use Livewire\Component;
use Livewire\WithPagination;

class Search extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = strtolower($this->search);

        return view('livewire.search', [
            'fuelSites' => FuelSite::with([
                'Suburb', 'City', 'State', 'Brand',
                'prices' => fn($q) => $q->where('fuel_id', 2)->where('price', '>', 50),
            ])
            ->whereAny(['address', 'name'], 'like', "%{$search}%")
            ->latest()
            ->paginate(12),
        ]);
    }
}
