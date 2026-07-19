<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Promocode;
use App\Models\Order;

class PromocodeRedemption extends Model
{
    /** @use HasFactory<\Database\Factories\PromocodeRedemptionFactory> */
    use HasFactory;

    protected $fillable = [
        'promocode_id',
        'order_id',
        'discount_amount',
    ];

    public function order(): BelongsTo {
        return $this->belongsTo(Order::class);
    }

    public function promocode(): BelongsTo {
        return $this->belongsTo(Promocode::class);
    }
}
