<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class ExistenceValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $exists = Promocode::find($promocode->id);
        if(!$exists) {
            throw new InvalidArgumentException('El código promocional no existe');
        }
        parent::handle($order, $promocode);
    }
}