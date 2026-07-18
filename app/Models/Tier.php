<?php

namespace App\Models;

use Database\Factories\TierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tier extends Model
{
    /** @use HasFactory<TierFactory> */
    use HasFactory;

    protected $fillable = [
        'minimum_orders',
        'discount_value',
        'promocode_id',
    ];

    public function promocode(): BelongsTo
    {
        return $this->belongsTo(Promocode::class);
    }
}
