<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\State>
 */
class StateFactory extends Factory
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
            "region_level" => 3,
            "region_id" => 1,
            "name" => "Queensland",
            "type" => "State",
            "region_parent_id" => null
        ];
    }
}
