<?php

namespace App\PromocodeEngine;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;
use App\Services\PriceCalculatorService;
use App\Services\PromocodeValidationService;

class PromocodeEngine
{
    public function __construct(
        private PromocodeValidationService $validationService,
        private PriceCalculatorService $priceCalculatorService,
        private Logger $logger,
    ) {}

    public function validateCode(OrderableInterface $order, Promocode $promocode): bool
    {
        $isValid = $this->validationService->validate($order, $promocode);

        if (! $isValid) {
            $this->logger->log("Promocode inválido: #{$promocode->id} para orden #{$order->getId()}");

            return false;
        }

        $finalPrice = $this->priceCalculatorService->calculatePrice($order, $promocode);

        $this->logger->log("Promocode #{$promocode->id} aplicado a orden #{$order->getId()}. Precio final: {$finalPrice}");

        return true;
    }
}
