<?php

namespace App\Discounts;

class DefaultDiscount extends DiscountTemplate
{
    protected function applyDiscount(): float
    {
        return 0.0;
    }
}