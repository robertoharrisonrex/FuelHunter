<?php

namespace Database\Seeders;

use App\Models\FuelType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class FuelTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
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
}
