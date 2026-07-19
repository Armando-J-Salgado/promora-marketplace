<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class MaxDiscountValidator extends PromocodeValidationHandler
{
    protected $discount;

    public function __construct(float $discount)
    {
        $this->discount = $discount;
    }

    public function handle(Order $order, Promocode $promocode): void
    {
        $maxAmount = $promocode->rules['max_discount_amount'] ?? null;

        if ($maxAmount === null) {
            Logger::getInstance()->log("[FAIL] MaxDiscountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El monto máximo que se puede descontar no ha sido establecido para el código promocional");
            throw new InvalidArgumentException('El monto máximo que se puede descontar no ha sido establecido para el código promocional');
        }

        if ($this->discount > $maxAmount) {
            Logger::getInstance()->log("[FAIL] MaxDiscountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->id} | El monto a descontar sobrepasa el límite del cupón");
            throw new InvalidArgumentException('El monto a descontar sobrepasa el límite del cupón');
        }

        Logger::getInstance()->log("[PASS] MaxDiscountValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
        parent::handle($order, $promocode);
    }
}
