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
        $this->fuelTypes = FuelType::orderBy('name')->get();
    }

    private function mapData(): array
    {
        $rows = DB::table('fuel_sites')
            ->join('prices', function ($join) {
                $join->on('prices.site_id', '=', 'fuel_sites.id')
                     ->where('prices.fuel_id', '=', $this->selectedFuelTypeId)
                     ->where('prices.price', '>', 50);
            })
            ->leftJoin('brands',     'brands.id',     '=', 'fuel_sites.brand_id')
            ->leftJoin('suburbs',    'suburbs.id',    '=', 'fuel_sites.geo_region_1')
            ->leftJoin('fuel_types', 'fuel_types.id', '=', 'prices.fuel_id')
            // Secondary prices for Unleaded (2), PUL95 (5), PUL98 (8)
            ->leftJoin('prices as p_ul', function ($join) {
                $join->on('p_ul.site_id', '=', 'fuel_sites.id')
                     ->where('p_ul.fuel_id', '=', 2);
            })
            ->leftJoin('prices as p95', function ($join) {
                $join->on('p95.site_id', '=', 'fuel_sites.id')
                     ->where('p95.fuel_id', '=', 5);
            })
            ->leftJoin('prices as p98', function ($join) {
                $join->on('p98.site_id', '=', 'fuel_sites.id')
                     ->where('p98.fuel_id', '=', 8);
            })
            ->select(
                'fuel_sites.id',
                'fuel_sites.name',
                'fuel_sites.latitude',
                'fuel_sites.longitude',
                'fuel_sites.address',
                'fuel_sites.postcode',
                'suburbs.name as suburb_name',
                'prices.price',
                'prices.transaction_date_utc',
                'brands.name as brand_name',
                'fuel_types.name as fuel_type_name',
                'p_ul.price as price_ul',
                'p95.price as price_95',
                'p98.price as price_98',
            )
            ->get();

        if ($rows->isEmpty()) {
            return ['sites' => [], 'min' => 0, 'max' => 0, 'count' => 0, 'fuel_type_name' => ''];
        }

        $rawPrices    = $rows->pluck('price');
        $fuelTypeName = $rows->first()->fuel_type_name ?? '';

        $sites = $rows->map(fn($r) => [
            'id'       => $r->id,
            'name'     => $r->name,
            'lat'      => (float) $r->latitude,
            'lng'      => (float) $r->longitude,
            'addr'     => $r->address,
            'suburb'   => $r->suburb_name ?? '',
            'postcode' => $r->postcode,
            'price'    => round($r->price / 100, 3),
            'updated'  => $r->transaction_date_utc,
            'brand'    => $r->brand_name ?? '',
            'price_ul' => $r->price_ul ? round($r->price_ul / 100, 3) : null,
            'price_95' => $r->price_95 ? round($r->price_95 / 100, 3) : null,
            'price_98' => $r->price_98 ? round($r->price_98 / 100, 3) : null,
        ])->values()->toArray();

        return [
            'sites'          => $sites,
            'min'            => round($rawPrices->min() / 100, 2),
            'max'            => round($rawPrices->max() / 100, 2),
            'count'          => count($sites),
            'fuel_type_name' => $fuelTypeName,
        ];
    }

    public function updatedSelectedFuelTypeId(): void
    {
        $data = $this->mapData();
        $this->dispatch('markersUpdated',
            sites:        $data['sites'],
            min:          $data['min'],
            max:          $data['max'],
            fuelTypeName: $data['fuel_type_name'],
        );
    }

    public function render()
    {
        return view('livewire.fuel-map', ['mapData' => $this->mapData()]);
    }
}
