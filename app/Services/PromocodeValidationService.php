<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Promocode;
use App\Factories\ValidationFactory;

class PromocodeValidationService {
    
    private array $permanentRules = ['existence', 'validity', 'state'];

    public function validate(Order $order, Promocode $promocode): bool {
        
        $firstHandler = null;
        $currentHandler = null;
        
        foreach($this->permanentRules as $key) {
            if($firstHandler === null) {
                $firstHandler = ValidationFactory::make($key);
                $currentHandler = $firstHandler;
            } else {
                $validation = ValidationFactory::make($key);
                $currentHandler = $currentHandler->setNext($validation);
            }
        }

        foreach($promocode->rules as $key=>$value) {
            $validation = ValidationFactory::make($key);
            $currentHandler = $currentHandler->setNext($validation);
        };

        $firstHandler->handle($order, $promocode);
        return true;
    }
}