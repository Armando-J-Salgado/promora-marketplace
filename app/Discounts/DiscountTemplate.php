<?php

namespace App\Discounts;

use App\Factories\ValidationFactory;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

abstract class DiscountTemplate
{
    protected OrderableInterface $order;

    protected Promocode $promocode;

    protected ?float $subtotal = null;

    public function __construct(OrderableInterface $order, Promocode $promocode)
    {
        $this->order = $order;
        $this->promocode = $promocode;
    }

    protected function getSubtotal(): float
    {
        if ($this->subtotal === null) {
            $this->subtotal = $this->order->getSubtotal();
        }

        return $this->subtotal;
    }

    public function calculatePrice(): float
    {
        $subtotal = $this->getSubtotal();

        if ($subtotal <= 0) {
            return 0.0;
        }

        $discount = $this->applyDiscount();

        return $this->validate($discount, $subtotal);
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
