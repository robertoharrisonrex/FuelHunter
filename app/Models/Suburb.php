<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suburb extends Model
{
    use HasFactory;

    public function city():BelongsTo
    {
        return $this->belongsTo(City::class, 'region_parent_id');
    }

    public function fuelSites():HasMany
    {
        return $this->HasMany(FuelSite::class, 'geo_region_level_1');
    }

}
