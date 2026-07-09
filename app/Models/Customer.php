<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Promocode;
use App\Models\Order;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    public function promocodes(): BelongsToMany {
        return $this->belongsToMany(Promocode::class);
    }

    public function orders(): HasMany {
        return $this->hasMany(Order::class);
    }
}
