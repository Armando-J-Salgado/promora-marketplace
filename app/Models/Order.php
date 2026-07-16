<?php

namespace App\Models;

use App\Orderable\OrderContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Service;
use App\Models\Customer;
use App\Models\PromocodeRedemption;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Orderable\OrderableInterface;

class Order extends Model implements OrderableInterface
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

    public function getSubtotal(): float {
        return $this->subtotal;
    }

    public function getOrderContext(): OrderContext {
        $categories = [];
        foreach($this->services as $service) {
            $categories[] = $service->id;
        }
        $context = new OrderContext($this->customer,$categories, $this->customer->orders);
        return $context;
    }
}
