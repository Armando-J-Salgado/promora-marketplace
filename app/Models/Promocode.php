<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Customer;
use App\Models\Tier;
use App\Models\PromocodeRedemption;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promocode extends Model
{
    /** @use HasFactory<\Database\Factories\PromocodeFactory> */
    use HasFactory;

    public function allowedCustomers(): BelongsToMany {
        return $this->belongsToMany(Customer::class);
    }

    public function tiers(): HasMany {
        return $this->hasMany(Tier::class);
    }

    public function promocodeRedemptions(): HasMany {
        return $this->hasMany(PromocodeRedemption::class);
    }


}
