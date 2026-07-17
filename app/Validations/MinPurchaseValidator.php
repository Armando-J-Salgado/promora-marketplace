<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class MinPurchaseValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $min = $promocode->rules['min_purchase_amount'] ?? null;
        if (!$min || $min === 0) {
            throw new InvalidArgumentException('El código promocional no tiene definido el mínimo');
        }
        
        $subtotal = $order->getSubtotal();
        if($subtotal < $min) {
            throw new InvalidArgumentException('La orden no cumple con el subtotal mínimo necesario');
        }

        parent::handle($order, $promocode);
    
    }
}