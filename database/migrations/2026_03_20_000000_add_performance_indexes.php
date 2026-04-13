<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        Schema::table('prices', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->date('transaction_date')->storedAs("transaction_date_utc::date")->nullable();
            } else {
                $table->string('transaction_date', 10)->virtualAs("date(transaction_date_utc)")->nullable();
            }
            $table->index(['fuel_id', 'transaction_date'], 'prices_fuel_id_date_index');
            $table->index(['site_id', 'fuel_id'], 'prices_site_id_fuel_id_index');
        });

        Schema::table('historical_site_prices', function (Blueprint $table) use ($driver) {
            if ($driver === 'pgsql') {
                $table->date('transaction_date')->storedAs("transaction_date_utc::date")->nullable();
            } else {
                $table->string('transaction_date', 10)->virtualAs("date(transaction_date_utc)")->nullable();
            }
            $table->index(['fuel_id', 'transaction_date'], 'hsp_fuel_id_date_index');
            $table->index('site_id', 'hsp_site_id_index');
        });

        Schema::table('fuel_sites', function (Blueprint $table) {
            $table->index('brand_id', 'fuel_sites_brand_id_index');
            $table->index('geo_region_2', 'fuel_sites_geo_region_2_index');
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->dropIndex('prices_fuel_id_date_index');
            $table->dropIndex('prices_site_id_fuel_id_index');
            $table->dropColumn('transaction_date');
        });

        Schema::table('historical_site_prices', function (Blueprint $table) {
            $table->dropIndex('hsp_fuel_id_date_index');
            $table->dropIndex('hsp_site_id_index');
            $table->dropColumn('transaction_date');
        });

        Schema::table('fuel_sites', function (Blueprint $table) {
            $table->dropIndex('fuel_sites_brand_id_index');
            $table->dropIndex('fuel_sites_geo_region_2_index');
        });
    }
};
