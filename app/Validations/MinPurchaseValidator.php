<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class MinPurchaseValidator extends PromocodeValidationHandler
{
    public function handle(Order $order, Promocode $promocode): void
    {
        $min = $promocode->rules['min_purchase_amount'] ?? null;
        if (! $min || $min === 0) {
            Logger::getInstance()->log("[FAIL] MinPurchaseValidator | code=min_amount_required | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional no tiene definido el mínimo");
            throw new InvalidArgumentException('El código promocional no tiene definido el mínimo');
        }

        $subtotal = $order->getSubtotal();
        if ($subtotal < $min) {
            Logger::getInstance()->log("[FAIL] MinPurchaseValidator | code=min_amount_required | promocode=#{$promocode->id} | order=#{$order->id} | La orden no cumple con el subtotal mínimo necesario");
            throw new InvalidArgumentException('La orden no cumple con el subtotal mínimo necesario');
        }

        Logger::getInstance()->log("[PASS] MinPurchaseValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
        parent::handle($order, $promocode);

    }
}
