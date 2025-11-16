<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            "is_admin" => 1,
            "first_name" => "admin",
            "last_name" => "admin",
            "email" => "admin@admin.com",
            "password" => "admin",
            "logo" => "https://picsum.photos/id/" . fake()->numberBetween(1, 200) . "/200/200",
            "newsletter" => 1
        ]);

        User::factory(3)->create();

        $this->call([
            RegionSeeder::class,
            FuelSiteSeeder::class,
            BrandSeeder::class,
            FuelTypeSeeder::class
        ]);
    }
}
