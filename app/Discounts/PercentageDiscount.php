<?php

namespace App\Discounts;

class PercentageDiscount extends DiscountTemplate
{
    /**
     * descuento = subtotal × (value / 100)
     */
    protected function applyDiscount(): float
    {
        return $this->getSubtotal() * ($this->promocode->value / 100);
    }
}
