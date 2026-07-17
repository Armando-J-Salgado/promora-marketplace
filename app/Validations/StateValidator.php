<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class StateValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        if ($promocode->status !== 'active') {
            throw new InvalidArgumentException('El código no se encuentra activo');
        }

        parent::handle($order, $promocode);
    }
}