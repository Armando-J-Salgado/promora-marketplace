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
        $this->logger->log("Validating promocode [{$promocode->getKey()}] for order [{$order->getKey()}]");

        try {
            $this->validationService->validate($order, $promocode);
            $this->priceCalculatorService->calculatePrice($order, $promocode);
            $this->logger->log("Promocode [{$promocode->getKey()}] validated successfully");

            return true;
        } catch (\Exception $exception) {
            $this->logger->log("Promocode [{$promocode->getKey()}] validation failed: {$exception->getMessage()}");
            throw $exception;
        }
    }
}
