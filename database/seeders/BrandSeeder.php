<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
}
