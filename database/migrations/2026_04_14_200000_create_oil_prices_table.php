<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oil_prices', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);           // WTI_USD, BRENT_CRUDE_USD, etc.
            $table->decimal('price', 10, 4);       // e.g. 74.5200
            $table->string('currency', 10)->default('USD');
            $table->timestamp('recorded_at');      // API's created_at value
            $table->timestamps();

            $table->unique(['code', 'recorded_at']);        // dedup guard
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oil_prices');
    }
};
