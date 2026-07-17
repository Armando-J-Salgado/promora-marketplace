<?php

namespace App\Discounts;

class FixedDiscount extends DiscountTemplate
{
    protected function applyDiscount(): float
    {
        return $this->promocode->value;
    }
}
