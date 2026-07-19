<?php

namespace App\Discounts;

class FixedDiscount extends DiscountTemplate
{
    /**
     * descuento = min(value, subtotal)
     */
    protected function applyDiscount(): float
    {
        return min($this->promocode->value, $this->getSubtotal());
    }
}
