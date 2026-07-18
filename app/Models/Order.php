<?php

namespace App\Models;

use App\Orderable\OrderableInterface;
use App\Orderable\OrderContext;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model implements OrderableInterface
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'status',
        'subtotal',
        'total',
    ];

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_order')->withPivot('quantity');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function promocodeRedempetions(): HasMany
    {
        return $this->hasMany(PromocodeRedemption::class);
    }

    public function getSubtotal(): float
    {
        $subtotal = 0;

        foreach ($this->services as $service) {
            $subtotal += $service->pivot->quantity * $service->price;
        }

        $this->subtotal = $subtotal;
        $this->save();

        return $this->subtotal;
    }

    public function getOrderContext(): OrderContext
    {
        $categories = [];
        foreach ($this->services as $service) {
            $categories[] = $service->category->id;
        }
        $context = new OrderContext($this->customer, $categories, $this->customer->orders->all());

        return $context;
    }
}
