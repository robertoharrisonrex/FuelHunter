<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelType extends Model
{
    use HasFactory;


    public function prices(): HasMany
    {
        return $this->hasMany(Price::class,'fuel_id', 'id');
    }

}
