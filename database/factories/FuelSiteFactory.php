<?php

namespace Database\Factories;

use App\Models\FuelSite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FuelSite>
 */
class FuelSiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        $fuel = FuelSite::all()->first();

        return [
            "id" => 61401157,
            "address" => "118 Birkdale Rd",
            "name" => "EG Ampol Birkdale",
            "brand_id" => 3421073,
            "postcode" => 4159,
            "latitude" => "-27.494931",
            "longitude" => "153.212762",
            "geo_region_1" => 178,
            "geo_region_2" => 1,
            "geo_region_3" => 1,
            "geo_region_4" => 0,
            "geo_region_5" => 0,
            "google_place_id" => "ChIJJwlYTq5gkWsRo76rItS39YM",
            "api_last_modified" => "2020-11-26T12:23:39.433"
        ];


        // "S": 61401157,
        //			"A": "118 Birkdale Rd",
        //			"N": "EG Ampol Birkdale",
        //			"B": 3421073,
        //			"P": "4159",
        //			"G1": 178,
        //			"G2": 1,
        //			"G3": 1,
        //			"G4": 0,
        //			"G5": 0,
        //			"Lat": -27.494931,
        //			"Lng": 153.212762,
        //			"M": "2020-11-26T12:23:39.433",
        //			"GPI": "ChIJJwlYTq5gkWsRo76rItS39YM",
    }
}
