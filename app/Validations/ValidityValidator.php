<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class ValidityValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        if($promocode->activation_date->isFuture()) {
            throw new InvalidArgumentException('El código promocional aún no comienza su período de canje');
        }
        if ($promocode->expiration_date->isPast()) {
            throw new InvalidArgumentException('El código promocional ha caducado');
        }
        parent::handle($order, $promocode);
    }
}