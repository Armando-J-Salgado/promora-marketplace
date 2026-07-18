<?php

namespace App\Factories;

use App\Discounts\DefaultDiscount;
use App\Discounts\DiscountTemplate;
use App\Discounts\FixedDiscount;
use App\Discounts\PercentageDiscount;
use App\Discounts\TieredDiscount;
use App\Models\Order;
use App\Models\Promocode;

class DiscountFactory
{
    public function make(Promocode $promocode, Order $order): DiscountTemplate
    {
        return match ($promocode->type) {
            'fixed' => new FixedDiscount($order, $promocode),
            'percent' => new PercentageDiscount($order, $promocode),
            'tiered' => new TieredDiscount($order, $promocode),
            default => new DefaultDiscount($order, $promocode),
        };
    }
}
