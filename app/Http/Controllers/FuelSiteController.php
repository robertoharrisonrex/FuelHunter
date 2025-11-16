<?php

namespace App\Http\Controllers;

use App\Models\FuelSite;
use Illuminate\Http\Request;

class FuelSiteController extends Controller
{


    public function index(){

        return view('fuelSite.index', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->latest()->paginate(10)]);
    }

    public function show(FuelSite $fuelSite){

        return view('fuelSite.show', ['fuelSite' => $fuelSite]);
    }

    public function filter(Request $request)
    {

        return view('fuelSite.index', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->whereAny([
            'address',
            'name'
        ], 'like', "%{$request['search']}%")->latest()->paginate(10)]);
    }

}
