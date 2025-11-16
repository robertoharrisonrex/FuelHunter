<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "id"=> 1,
            "region_level" => 2,
            "region_id" => 1,
            "name" => "Brisbane",
            "type" => "City",
            "region_parent_id" => 1
        ];
    }
}
