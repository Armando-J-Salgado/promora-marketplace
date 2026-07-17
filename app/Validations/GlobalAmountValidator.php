<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use InvalidArgumentException;

class GlobalAmountValidator extends PromocodeValidationHandler {
    protected float $discount;
    public function __construct(float $discount) {
        $this->discount = $discount;
    }

    public function handle(Order $order, Promocode $promocode): void {
        $globalAmountLimit = $promocode->rules['global_amount_limit'] ?? null;

        if($globalAmountLimit === null) {
            throw new InvalidArgumentException('El cupón no tiene configurado la cantidad límite global');
        }

        $totalDiscounted = PromocodeRedemption::where('promocode_id', $promocode->id)->sum('discount_amount');

        if(($totalDiscounted + $this->discount) > $globalAmountLimit) {
            throw new InvalidArgumentException('El código promocional supera su presupuesto máximo de descuentos');
        }

        parent::handle($order, $promocode);
    }
}