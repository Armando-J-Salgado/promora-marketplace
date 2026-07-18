<?php

namespace App\Discounts;

use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;

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
        $discount = $this->applyDiscount();

        return $this->validate($discount, $this->order->subtotal);
    }

    protected function validate(float $discount, float $subtotal): float
    {
        $rules = $this->promocode->rules ?? [];

        if (isset($rules['global_amount_limit'])) {
            $remaining = $rules['global_amount_limit']
                - PromocodeRedemption::where('promocode_id', $this->promocode->id)->sum('discount_amount');

            if ($remaining <= 0) {
                throw new \InvalidArgumentException('Se alcanzó el límite de monto global acumulado');
            }

            $discount = min($discount, $remaining);
        }

        if (isset($rules['max_discount_amount'])) {
            $discount = min($discount, $rules['max_discount_amount']);
        }

        return $discount;
    }

    /*
    Paso que varia porque calcula el descuento según el tipo
     */
    abstract protected function applyDiscount(): float;
}
