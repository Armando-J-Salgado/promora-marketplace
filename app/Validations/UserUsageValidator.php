<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use InvalidArgumentException;

class UserUsageValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $userLimit = $promocode->rules['user_usage_limit'] ?? null;

        if ($userLimit === null) {
            parent::handle($order, $promocode);
            return;
        }

        // Contar todas las redenciones de este cliente para este promocode
        $redemptionsCount = PromocodeRedemption::where('promocode_id', $promocode->id)
            ->whereHas('order', function ($query) use ($order) {
                $query->where('customer_id', $order->customer_id);
            })
            ->count();

        if ($redemptionsCount >= $userLimit) {
            throw new InvalidArgumentException(
                'El usuario ha excedido el número máximo de usos permitidos para este código promocional'
            );
        }

        parent::handle($order, $promocode);
    }
}