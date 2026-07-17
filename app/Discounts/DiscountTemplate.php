<?php

namespace App\Discounts;

use App\Models\Order;
use App\Models\Promocode;

abstract class DiscountTemplate
{
    protected Order $order;
    protected Promocode $promocode;

    public function __construct(Order $order, Promocode $promocode)
    {
        $this->order = $order;
        $this->promocode = $promocode;
    }

    public function calculatePrice(): float
    {
        $subtotal = $this->calculate();

        if (! $this->validate()) {
            return 0.0;
        }

        $discount = $this->applyDiscount();

        return $this->applyPostCalculationRules($discount, $subtotal);
    }

    protected function calculate(): float
    {
        return $this->order->subtotal;
    }

    protected function validate(): bool
    {
        return $this->order->subtotal > 0 && $this->promocode->value > 0;
    }

    /*
    Paso que varia porque calcula el descuento según el tipo
     */
    abstract protected function applyDiscount(): float;


    protected function applyPostCalculationRules(float $discount, float $subtotal): float
    {
        $rules = $this->promocode->rules ?? [];

        if (isset($rules['max_discount_amount'])) {
            $discount = min($discount, (float) $rules['max_discount_amount']);
        }

        return min($discount, $subtotal);
    }
}