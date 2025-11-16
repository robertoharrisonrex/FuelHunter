<?php

namespace App\Livewire;

use App\Models\FuelSite;
use Livewire\Component;

class Search extends Component
{
    public string $search = "";


    public function render()
    {

        return view('livewire.search', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->whereAny([
            'address',
            'name'
        ], 'like', "%{$this->search}%")->latest()->paginate(10)]);
    }

    public function getResults()
    {
        return view('livewire.search', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->whereAny([
            'address',
            'name'
        ], 'like', "%{$this->search}%")->latest()->paginate(10)]);


    }

}
