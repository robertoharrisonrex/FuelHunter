<?php

use App\Livewire\FuelMap;
use Livewire\Livewire;

it('renders the locate me button', function () {
    Livewire::test(FuelMap::class)
        ->assertSeeHtml('id="locateMeBtn"');
});
