<?php

namespace App\Jobs;

use App\Models\Brand;
use App\Models\City;
use App\Models\FuelSite;
use App\Models\FuelType;
use App\Models\Price;
use App\Models\State;
use App\Models\Suburb;
use http\Exception\InvalidArgumentException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class FetchFuelData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->fetchRegionData();
        $this->fetchBrandData();
        $this->fetchFuelSiteData();
        $this->fetchFuelTypeData();


    }

    public function fetchBrandData():void
    {

        $brands = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => env("FUEL_API_TOKEN")
        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetCountryBrands?countryId=21');

        foreach ($brands->collect()->get('Brands') as $brand) {

            Brand::factory()->create([
                "id" => $brand['BrandId'],
                "name" => $brand['Name'],
            ]);
        }

    }

    public function fetchFuelSiteData():void
    {

        $fuelCall = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => env("FUEL_API_TOKEN")
        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetFullSiteDetails?countryId=21&geoRegionLevel=3&geoRegionId=1');

        foreach ($fuelCall->collect()->get("S") as $site) {

            FuelSite::factory()->create([
                "id" => $site["S"],
                "address" => $site["A"],
                "name" => $site["N"],
                "brand_id" => $site["B"],
                "postcode" => $site["P"],
                "latitude" => $site["Lat"],
                "longitude" => $site["Lng"],
                "geo_region_1" => $site["G1"],
                "geo_region_2" => $site["G2"],
                "geo_region_3" => $site["G3"],
                "geo_region_4" => $site["G4"],
                "geo_region_5" => $site["G5"],
                "api_last_modified" => $site["M"],
                "google_place_id" => $site["GPI"],
            ]);
        };

    }

    public function fetchFuelTypeData():void
    {

        $fuelTypes = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => env("FUEL_API_TOKEN")
        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetCountryFuelTypes?CountryId=21');

        foreach ($fuelTypes->collect()->get('Fuels') as $fuelType) {

            FuelType::factory()->create([
                "id" => $fuelType['FuelId'],
                "name" => $fuelType['Name'],
            ]);
        }

    }

    public function fetchRegionData(): void
    {
        $fuelCall = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => env("FUEL_API_TOKEN")
        ])->get('https://fppdirectapi-prod.fuelpricesqld.com.au/Subscriber/GetCountryGeographicRegions?countryId=21&countryId=21');

        foreach ($fuelCall->collect()->get("GeographicRegions") as $region) {

            $type = match ($region['GeoRegionLevel']) {
                1 => "Suburb",
                2 => "City",
                3 => "State",
                default => throw new InvalidArgumentException("{$region['GeoRegionLevel']} is not a valid region, must be 1, 2, or 3."),
            };

            if ($type == "Suburb") {
                Suburb::factory()->create([
                    "id"=> $region["GeoRegionId"] ,
                    "region_level" => $region["GeoRegionLevel"],
                    "region_id" => $region["GeoRegionId"],
                    "type" => $type,
                    "name" => $region["Name"],
                    "abbreviation" => $region["Abbrev"],
                    "region_parent_id" => $region["GeoRegionParentId"],
                ]);
            } elseif ($type == "City") {
                City::factory()->create([
                    "id"=> $region["GeoRegionId"] ,
                    "region_level" => $region["GeoRegionLevel"],
                    "region_id" => $region["GeoRegionId"],
                    "type" => $type,
                    "name" => $region["Name"],
                    "abbreviation" => $region["Abbrev"],
                    "region_parent_id" => $region["GeoRegionParentId"],
                ]);
            } elseif ($type == "State") {
                State::factory()->create([
                    "id"=> $region["GeoRegionId"] ,
                    "region_level" => $region["GeoRegionLevel"],
                    "region_id" => $region["GeoRegionId"],
                    "type" => $type,
                    "name" => $region["Name"],
                    "abbreviation" => $region["Abbrev"],
                    "region_parent_id" => $region["GeoRegionParentId"],
                ]);

                $price = Http::withHeaders([
                    'Accept' => 'application/json',
                    'Authorization' => env("FUEL_API_TOKEN")
                ])->get("https://fppdirectapi-prod.fuelpricesqld.com.au/Price/GetSitesPrices?CountryId=21&GeoRegionLevel=3&GeoRegionId={$region["GeoRegionId"]}");

                foreach ($price->collect()->get("SitePrices") as $price) {
                    if ($price){
                        Price::factory()->create([
                            "site_id" => $price["SiteId"],
                            "fuel_id" => $price["FuelId"],
                            "price" => $price["Price"] != 9999.0 ? $price["Price"] / 10 : 0.0 ,
                            "collection_method" => $price["CollectionMethod"],
                            "transaction_date_utc" => $price["TransactionDateUtc"],
                        ]);
                    }
                }

            }
        };
    }
}
