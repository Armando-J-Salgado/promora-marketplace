<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class RestrictedUsageValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $customer = $order->getOrderContext()->buyerProfile;

        $allowedCustomers = $promocode->allowedCustomers();
        if (! $allowedCustomers->where('customers.id', $customer->id)->exists()) {
            Logger::getInstance()->log("[FAIL] RestrictedUsageValidator | code=restricted_usage | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional no ha sido asignado a este usuario");
            throw new InvalidArgumentException('El código promocional no ha sido asignado a este usuario');
        }

        Logger::getInstance()->log("[PASS] RestrictedUsageValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
