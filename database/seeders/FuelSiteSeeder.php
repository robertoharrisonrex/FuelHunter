<?php

namespace Database\Seeders;

use App\Models\FuelSite;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class FuelSiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * @throws GuzzleException
     */
    public function run(): void
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
}
