<?php

namespace App\Discounts;

use App\Factories\ValidationFactory;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

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

        $postCalculationRules = ['max_discount_amount', 'global_amount_limit'];

        foreach ($postCalculationRules as $rule) {
            if (! isset($rules[$rule])) {
                continue;
            }

            $validator = ValidationFactory::make($rule, $discount);

            try {
                $validator->handle($this->order, $this->promocode);
            } catch (InvalidArgumentException $e) {
                if ($rule === 'global_amount_limit') {
                    throw $e;
                }

                if ($rule === 'max_discount_amount') {
                    $discount = (float) $rules['max_discount_amount'];
                }
            }
        }

        return $discount;
    }

    abstract protected function applyDiscount(): float;
}
