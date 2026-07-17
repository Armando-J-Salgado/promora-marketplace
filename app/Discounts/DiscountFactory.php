<?php

namespace App\Discounts;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

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