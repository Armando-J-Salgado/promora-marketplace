<?php

namespace App\PromocodeEngine;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

class PromocodeEngine
{
    public function __construct(
        private PromocodeValidationService $validationService,
        private PriceCalculatorService $priceCalculatorService,
        private Logger $logger,
    ) {}

    public function validateCode(Order $order, Promocode $promocode): bool
    {
        $isValid = $this->validationService->validate($order, $promocode);

        if (! $isValid) {
            $this->logger->log("Promocode inválido: #{$promocode->id} para orden #{$order->id}");
            return false;
        }

        $finalPrice = $this->priceCalculatorService->calculatePrice($order, $promocode);

        $this->logger->log("Promocode #{$promocode->id} aplicado a orden #{$order->id}. Precio final: {$finalPrice}");

        return true;
    }
}