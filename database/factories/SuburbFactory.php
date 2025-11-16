<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Suburb>
 */
class SuburbFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            "id"=> 178,
            "region_level" => 1,
            "region_id" => 178,
            "name" => "Birkdale",
            "type" => "Suburb",
            "region_parent_id" => 1

        ];
    }
}
