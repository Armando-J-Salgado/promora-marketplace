<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Orderable\OrderableInterface;
use InvalidArgumentException;

class GlobalUsageValidator extends PromocodeValidationHandler
{
    public function handle(OrderableInterface $order, Promocode $promocode): void
    {
        $globalLimit = $promocode->rules['global_usage_limit'] ?? null;
        if ($globalLimit === null) {
            Logger::getInstance()->log("[FAIL] GlobalUsageValidator | code=usage_limit_reached | promocode=#{$promocode->id} | order=#{$order->getId()} | El máximo global no ha sido definido para este cupón");
            throw new InvalidArgumentException('El máximo global no ha sido definido para este cupón');
        }

        $redemptionsCount = PromocodeRedemption::where('promocode_id', $promocode->id)->count();
        if ($redemptionsCount >= $globalLimit) {
            Logger::getInstance()->log("[FAIL] GlobalUsageValidator | code=usage_limit_reached | promocode=#{$promocode->id} | order=#{$order->getId()} | El código promocional ya ha superado el número máximo de canjes globales");
            throw new InvalidArgumentException('El código promocional ya ha superado el número máximo de canjes globales');
        }

        Logger::getInstance()->log("[PASS] GlobalUsageValidator | promocode=#{$promocode->id} | order=#{$order->getId()} | regla superada");
        parent::handle($order, $promocode);
    }
}
