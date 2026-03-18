<?php

namespace App\Http\Controllers;

use App\Models\FuelSite;
use App\Models\HistoricalSitePrice;
use Illuminate\Http\Request;

class FuelSiteController extends Controller
{


    public function index(){
        return view('fuelSite.index');
    }

    public function show(FuelSite $fuelSite)
    {
        $fuelSite->load(['prices.fuelType', 'brand', 'suburb', 'city', 'state']);

        $history = HistoricalSitePrice::where('site_id', $fuelSite->id)
            ->with('fuelType')
            ->orderByDesc('transaction_date_utc')
            ->limit(30)
            ->get();

        return view('fuelSite.show', compact('fuelSite', 'history'));
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
