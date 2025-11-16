<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Price;
use App\Models\State;
use App\Models\Suburb;
use http\Exception\InvalidArgumentException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws \Exception
     */
    public function run(): void
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
