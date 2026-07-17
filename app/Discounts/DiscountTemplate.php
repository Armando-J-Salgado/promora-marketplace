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
        $this->order->getSubtotal();

        if (! $this->validate()) {
            return 0.0;
        }

        return $this->applyDiscount();
    }

    protected function validate(): bool
    {
        return $this->order->subtotal > 0 && $this->promocode->value > 0;
    }

    /*
    Paso que varia porque calcula el descuento según el tipo
     */
    abstract protected function applyDiscount(): float;
}
