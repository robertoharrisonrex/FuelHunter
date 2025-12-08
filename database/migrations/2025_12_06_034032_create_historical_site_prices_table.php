<?php

use App\Models\FuelSite;
use App\Models\FuelType;
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
        Schema::create('historical_site_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(FuelSite::class, 'site_id');
            $table->foreignIdFor(FuelType::class, 'fuel_id');
            $table->string('collection_method');
            $table->dateTime('transaction_date_utc');
            $table->float('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historical_site_prices');
    }
};
