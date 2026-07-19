<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class GlobalAmountValidator extends PromocodeValidationHandler
{
    protected float $discount;

    public function __construct(float $discount)
    {
        $this->discount = $discount;
    }

    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $globalAmountLimit = $promocode->rules['global_amount_limit'] ?? null;

        if ($globalAmountLimit === null) {
            Logger::getInstance()->log("[FAIL] GlobalAmountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->getId()} | El cupón no tiene configurado la cantidad límite global");
            throw new InvalidArgumentException('El cupón no tiene configurado la cantidad límite global');
        }

        $totalDiscounted = PromocodeRedemption::where('promocode_id', $promocode->id)->sum('discount_amount');

        if (($totalDiscounted + $this->discount) > $globalAmountLimit) {
            Logger::getInstance()->log("[FAIL] GlobalAmountValidator | code=maximum_discount_reached | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional supera su presupuesto máximo de descuentos");
            throw new InvalidArgumentException('El código promocional supera su presupuesto máximo de descuentos');
        }

        Logger::getInstance()->log("[PASS] GlobalAmountValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
