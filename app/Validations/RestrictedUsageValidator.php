<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class RestrictedUsageValidator extends PromocodeValidationHandler
{
    public function handle(Order $order, Promocode $promocode): void
    {
        $customer = $order->customer;

        $allowedCustomers = $promocode->allowedCustomers();
        if (! $allowedCustomers->where('customers.id', $customer->id)->exists()) {
            Logger::getInstance()->log("[FAIL] RestrictedUsageValidator | code=restricted_usage | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional no ha sido asignado a este usuario");
            throw new InvalidArgumentException('El código promocional no ha sido asignado a este usuario');
        }

        Logger::getInstance()->log("[PASS] RestrictedUsageValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
        parent::handle($order, $promocode);
    }
}
