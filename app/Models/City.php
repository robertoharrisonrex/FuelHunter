<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;


    public function suburbs():HasMany
    {
        return $this->hasMany(Suburb::class, 'region_parent_id');
    }

    public function state():BelongsTo
    {
        return $this->belongsTo(State::class, 'region_parent_id');
    }
}
