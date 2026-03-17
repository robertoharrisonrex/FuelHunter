<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HistoricalSitePrice extends Model
{
    use HasFactory;

    public function fuelType(): HasOne
    {
        return $this->hasOne(FuelType::class, 'id', 'fuel_id');
    }

    public function fuelSite(): BelongsTo
    {
        return $this->belongsTo(FuelSite::class, 'site_id');
    }
}
