<?php

namespace App\Services;

use App\Factories\ValidationFactory;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;

class PromocodeValidationService
{
    private array $permanentRules = ['existence', 'validity', 'state'];
    private array $postCalculationRules = ['max_discount_amount', 'global_amount_limit'];

    public function validate(OrderableInterface $order, Promocode $promocode): bool
    {

        $firstHandler = null;
        $currentHandler = null;

        foreach ($this->permanentRules as $key) {
            if ($firstHandler === null) {
                $firstHandler = ValidationFactory::make($key);
                $currentHandler = $firstHandler;
            } else {
                $validation = ValidationFactory::make($key);
                $currentHandler = $currentHandler->setNext($validation);
            }
        }

        foreach ($promocode->rules as $key => $value) {
            if (in_array($key, $this->postCalculationRules)) {
                continue;
            }
            $validation = ValidationFactory::make($key);
            $currentHandler = $currentHandler->setNext($validation);
        }

        $firstHandler->handle($order, $promocode);

        return true;
    }
}
