<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ValidationFactory;
use App\Validations\ExistenceValidator;

class PromocodeValidationService {
    public function validate(Order $order, Promocode $promocode): bool {
        
        $firstHandler = new ExistenceValidator();
        $currentHandler = $firstHandler;

        foreach($promocode->rules as $key=>$value) {
            $validation = ValidationFactory::make($key);
            $currentHandler = $currentHandler->setNext($validation);
        };

        $firstHandler->handle($order, $promocode);
        return true;
    }
}