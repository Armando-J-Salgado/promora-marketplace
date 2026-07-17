<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use InvalidArgumentException;

class GlobalUsageValidator extends PromocodeValidationHandler {
    public function handle(Order $order, Promocode $promocode): void {
        $globalLimit = $promocode->rules['global_usage_limit'] ?? null;
        if ($globalLimit === null) {
            throw new InvalidArgumentException('El máximo global no ha sido definido para este cupón');
        }

        $redemptionsCount = PromocodeRedemption::where('promocode_id', $promocode->id)->count();
        if ($redemptionsCount >= $globalLimit) {
            throw new InvalidArgumentException('El código promocional ya ha superado el número máximo de canjes globales');
        }

        parent::handle($order, $promocode);
    }
}