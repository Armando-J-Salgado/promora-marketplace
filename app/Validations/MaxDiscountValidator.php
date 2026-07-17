<?php

namespace App\Validations;

use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class MaxDiscountValidator extends PromocodeValidationHandler {
    protected $discount;

    public function __construct(float $discount) {
        $this->discount = $discount;
    }

    public function handle(Order $order, Promocode $promocode): void {
        $maxAmount = $promocode->rules['max_discount_amount'] ?? null;

        if ($maxAmount === null) {
            throw new InvalidArgumentException('El monto máximo que se puede descontar no ha sido establecido para el código promocional');
        }

        if($this->discount > $maxAmount) {
            throw new InvalidArgumentException('El monto a descontar sobrepasa el límite del cupón');
        }

        parent::handle($order, $promocode);
    }
}