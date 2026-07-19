<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class MinPurchaseValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $min = $promocode->rules['min_purchase_amount'] ?? null;
        if (! $min || $min === 0) {
            Logger::getInstance()->log("[FAIL] MinPurchaseValidator | code=min_amount_required | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional no tiene definido el mínimo");
            throw new InvalidArgumentException('El código promocional no tiene definido el mínimo');
        }

        $subtotal = $order->getSubtotal();
        if ($subtotal < $min) {
            Logger::getInstance()->log("[FAIL] MinPurchaseValidator | code=min_amount_required | promocode=#{$promocode->id} | order=#{$order->getId()} | La orden no cumple con el subtotal mínimo necesario");
            throw new InvalidArgumentException('La orden no cumple con el subtotal mínimo necesario');
        }

        Logger::getInstance()->log("[PASS] MinPurchaseValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);

    }
}
