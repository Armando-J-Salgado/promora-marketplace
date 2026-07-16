<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;

abstract class PromocodeValidationHandler {
    private ?PromocodeValidationHandler $next = null;

    public function setNext(
        PromocodeValidationHandler $handler
    ): PromocodeValidationHandler
    {
        $this->next = $handler;
        return $handler;
    }

    public function handle(Order $order, Promocode $promocode): void
    {
        if ($this->next) {
            $this->next->handle($order, $promocode);
        }
    }
}