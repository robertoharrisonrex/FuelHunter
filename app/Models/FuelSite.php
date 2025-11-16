<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelSite extends Model
{
    use HasFactory;


    public function suburb():BelongsTo
    {
        return $this->belongsTo(Suburb::class, 'geo_region_1');
    }

    public function city():BelongsTo
    {
        return $this->belongsTo(City::class, 'geo_region_2');
    }

    public function state():BelongsTo
    {
        return $this->belongsTo(State::class, 'geo_region_3');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class,'site_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

}
