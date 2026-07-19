<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class StateValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        if ($promocode->status !== 'active') {
            Logger::getInstance()->log("[FAIL] StateValidator | code=invalid_code | promocode=#{$promocode->id} | order=#{$order->getId()} | El código no se encuentra activo");
            throw new InvalidArgumentException('El código no se encuentra activo');
        }

        Logger::getInstance()->log("[PASS] StateValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
