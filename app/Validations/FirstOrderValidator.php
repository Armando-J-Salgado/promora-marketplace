<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;

class FirstOrderValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $currentOrders = $order->getOrderContext()->currentOrders;

        if (empty($currentOrders)) {
            // No hay órdenes previas, entonces esta es la primera
            parent::handle($order, $promocode);
            return;
        }

        // Ordenar por fecha de creación
        $firstOrder = collect($currentOrders)->sortBy('created_at')->first();

        if ($order->id === $firstOrder->id) {
            // Es la primera orden del cliente
            parent::handle($order, $promocode);
        } else {
            throw new \InvalidArgumentException(
                'El código promocional aplica solo para la primera orden del cliente'
            );
        }
    }
}
