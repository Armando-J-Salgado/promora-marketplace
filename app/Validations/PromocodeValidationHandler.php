<?php

namespace App\Validations;

use App\Models\Promocode;
use App\Orderable\OrderableInterface;

abstract class PromocodeValidationHandler
{
    private ?PromocodeValidationHandler $next = null;

    public function setNext(
        PromocodeValidationHandler $handler
    ): PromocodeValidationHandler {
        $this->next = $handler;

        return $handler;
    }

    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        if ($this->next) {
            $this->next->handle($order, $promocode);
        }
    }
}
