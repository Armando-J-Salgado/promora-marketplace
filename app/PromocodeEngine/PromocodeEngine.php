<?php
namespace App\PromocodeEngine;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

class PromocodeEngine {

    public function __construct(
        private PromocodeValidationService $validationService,
        private PriceCalculatorService $priceCalculatorService,
        private Logger $logger, //Agregar al app provider para singleton
    ) {}

    public function validateCode(Order $order, Promocode $promocode): bool {
        $this->validationService->validate($order, $promocode);
        $this->priceCalculatorService->calculatePrice($order, $promocode);
        //Agregar el resto de lógica aquí
        return true; 
    }

}