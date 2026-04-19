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
            ->get()
            ->unique(fn($e) => $e->fuel_id . '_' . \Carbon\Carbon::parse($e->transaction_date_utc)->toDateString() . '_' . $e->price)
            ->take(30)
            ->values();

        return view('fuelSite.show', compact('fuelSite', 'history'));
    }

    public function filter(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:100']]);

        return view('fuelSite.index', ['fuelSites' => FuelSite::with([
            'Suburb','City', 'State'
        ])->whereAny([
            'address',
            'name'
        ], 'like', "%{$request['search']}%")->latest()->paginate(10)]);
    }

}
