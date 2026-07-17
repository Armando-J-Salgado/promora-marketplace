<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class RestrictedUsageValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $customer = $order->customer;
    
        $allowedCustomers = $promocode->allowedCustomers();
        if(!$allowedCustomers->where('customers.id', $customer->id)->exists()) {
            throw new InvalidArgumentException('El código promocional no ha sido asignado a este usuario');
        }

        parent::handle($order, $promocode);
    }
}