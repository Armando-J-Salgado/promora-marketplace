<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Orderable\OrderableInterface;

class FirstOrderValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $currentOrders = $order->getOrderContext()->currentOrders;

        if (empty($currentOrders)) {
            // No hay órdenes previas, entonces esta es la primera
            Logger::getInstance()->log("[PASS] FirstOrderValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
            parent::handle($order, $promocode);

            return;
        }

        // Ordenar por fecha de creación
        $firstOrder = collect($currentOrders)->sortBy('created_at')->first();

        if ($order->getId() === $firstOrder->id) {
            // Es la primera orden del cliente
            Logger::getInstance()->log("[PASS] FirstOrderValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
            parent::handle($order, $promocode);
        } else {
            Logger::getInstance()->log("[FAIL] FirstOrderValidator | code=code_already_used | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional aplica solo para la primera orden del cliente");
            throw new \InvalidArgumentException(
                'El código promocional aplica solo para la primera orden del cliente'
            );
        }
    }
}
