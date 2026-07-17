<?php

namespace App\Services;

use App\Discounts\DiscountFactory;
use App\Models\Order;
use App\Models\Promocode;

class PriceCalculatorService {
    public function calculatePrice(Order $order, Promocode $promocode): float {
        $subtotal = $order->getSubtotal();
        $discount = (new DiscountFactory)->make($promocode, $order);
        $discountAmount = $discount->calculatePrice();

        if ($discountAmount <= 0.0) {
            return $subtotal;
        }

        return max($subtotal - $discountAmount, 0.0);
    }
}