<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class ValidityValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        if ($promocode->activation_date->isFuture()) {
            Logger::getInstance()->log("[FAIL] ValidityValidator | code=expired_coupon | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional aún no comienza su período de canje");
            throw new InvalidArgumentException('El código promocional aún no comienza su período de canje');
        }
        if ($promocode->expiration_date->isPast()) {
            Logger::getInstance()->log("[FAIL] ValidityValidator | code=expired_coupon | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional ha caducado");
            throw new InvalidArgumentException('El código promocional ha caducado');
        }
        Logger::getInstance()->log("[PASS] ValidityValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
