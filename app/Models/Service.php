<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Order;
use App\Models\Category;

class Service extends Model
{
    /** @use HasFactory<\Database\Factories\ServiceFactory> */
    use HasFactory;

    protected $fillable = [
        "id",
        "name",
        "price",
    ];

    public function orders(): BelongsToMany {
        return $this->belongsToMany(Order::class)->withPivot('quantity');
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }
}
