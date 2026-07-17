<?php

namespace App\Discounts;

class TieredDiscount extends DiscountTemplate
{
    protected function applyDiscount(): float
    {
        return $this->promocode->value;
    }
}
