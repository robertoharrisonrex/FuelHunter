<?php

use App\Models\City;
use App\Models\State;
use App\Models\Suburb;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fuel_sites', function (Blueprint $table) {
            $table->id(); // S
            $table->string('address'); // A
            $table->string('name'); // N
            $table->foreignId('brand_id'); // B
            $table->integer("postcode"); // P
            $table->integer("latitude"); // Lat
            $table->integer("longitude"); // Lng
            $table->foreignIdFor(Suburb::class, "geo_region_1"); // G1
            $table->foreignIdFor(City::class, "geo_region_2"); // G1
            $table->foreignIdFor(State::class, "geo_region_3"); // G1
            $table->integer("geo_region_4"); // G4
            $table->integer("geo_region_5"); // G5
            $table->string("google_place_id"); // GPI
            $table->dateTime('api_last_modified')->nullable(); // M
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_sites');

    }
};
