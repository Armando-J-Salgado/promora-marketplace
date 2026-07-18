<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Order;
use App\Models\Promocode;

class FirstOrderValidator extends PromocodeValidationHandler
{
    public function handle(Order $order, Promocode $promocode): void
    {
        $currentOrders = $order->getOrderContext()->currentOrders;

        if (empty($currentOrders)) {
            // No hay órdenes previas, entonces esta es la primera
            Logger::getInstance()->log("[PASS] FirstOrderValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
            parent::handle($order, $promocode);

            return;
        }

        // Ordenar por fecha de creación
        $firstOrder = collect($currentOrders)->sortBy('created_at')->first();

        if ($order->id === $firstOrder->id) {
            // Es la primera orden del cliente
            Logger::getInstance()->log("[PASS] FirstOrderValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
            parent::handle($order, $promocode);
        } else {
            Logger::getInstance()->log("[FAIL] FirstOrderValidator | code=code_already_used | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional aplica solo para la primera orden del cliente");
            throw new \InvalidArgumentException(
                'El código promocional aplica solo para la primera orden del cliente'
            );
        }
    }
}
