<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Promocode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tier extends Model
{
    /** @use HasFactory<\Database\Factories\TierFactory> */
    use HasFactory;

    public function promocode(): BelongsTo {
        return $this->belongsTo(Promocode::class);
    }
}
