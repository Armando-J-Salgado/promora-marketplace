<?php

namespace App\Services;

use App\Factories\DiscountFactory;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;

class PriceCalculatorService
{
    public function calculatePrice(OrderableInterface $order, Promocode $promocode): float
    {
        $subtotal = $order->getSubtotal();
        $discount = (new DiscountFactory)->make($promocode, $order);
        $discountAmount = $discount->calculatePrice();

        if ($discountAmount <= 0.0) {
            return $subtotal;
        }

        return max($subtotal - $discountAmount, 0.0);
    }
}
