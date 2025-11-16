<?php

namespace App\Livewire;

use App\Models\FuelSite;
use Livewire\Component;

class FetchResults extends Component
{

    public $seadrch;

//    public function setInput()
//    {
//        $this->input =
//    }

    public function render()
    {
        return view('fuelSite.index', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->whereAny([
            'address',
            'name'
        ], 'like', "%{$this->search}%")->latest()->paginate(10)]);
    }
}
