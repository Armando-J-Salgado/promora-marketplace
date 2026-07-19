<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class UserUsageValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $userLimit = $promocode->rules['user_usage_limit'] ?? null;

        if ($userLimit === null) {
            Logger::getInstance()->log("[PASS] UserUsageValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
            parent::handle($order, $promocode);

            return;
        }

        // Contar todas las redenciones de este cliente para este promocode
        $redemptionsCount = PromocodeRedemption::where('promocode_id', $promocode->id)
            ->whereHas('order', function ($query) use ($order) {
                $query->where('customer_id', $order->getOrderContext()->buyerProfile->id);
            })
            ->count();

        if ($redemptionsCount >= $userLimit) {
            Logger::getInstance()->log("[FAIL] UserUsageValidator | code=usage_limit_reached | promocode=#{$promocode->id} | order=#{$order->getId()} | El usuario ha excedido el número máximo de usos permitidos para este código promocional");
            throw new InvalidArgumentException(
                'El usuario ha excedido el número máximo de usos permitidos para este código promocional'
            );
        }

        Logger::getInstance()->log("[PASS] UserUsageValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
