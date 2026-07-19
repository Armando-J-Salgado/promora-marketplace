<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class ExistenceValidator extends PromocodeValidationHandler
{
    public function handle(Order $order, Promocode $promocode): void
    {
        $exists = Promocode::find($promocode->id);
        if (! $exists) {
            Logger::getInstance()->log("[FAIL] ExistenceValidator | code=invalid_code | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional no existe");
            throw new InvalidArgumentException('El código promocional no existe');
        }
        Logger::getInstance()->log("[PASS] ExistenceValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
        parent::handle($order, $promocode);
    }
}
