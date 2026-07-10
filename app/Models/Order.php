<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Service;
use App\Models\Customer;
use App\Models\PromocodeRedemption;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    public function services(): BelongsToMany {
        return $this->belongsToMany(Service::class)->withPivot('quantity');
    }

    public function customer(): BelongsTo {
        return $this->belongsTo(Customer::class);
    }

    public function promocodeRedempetions(): HasMany {
        return $this->hasMany(PromocodeRedemption::class);
    }
}
