<?php

namespace App\Discounts;

class PercentageDiscount extends DiscountTemplate
{
    /**
     * descuento = subtotal × (value / 100)
     */
    protected function applyDiscount(): float
    {
        return $this->order->subtotal * ($this->promocode->value / 100);
    }
}