<?php
namespace App\Discounts;

use App\Models\Order;
use App\Models\Promocode;

abstract class DiscountTemplate {
    protected Order $order;
    protected Promocode $promocode;

    public function calculatePrice(): float {
        $this->calculate();
        $this->validate();
        //Completar lógica aquí
        return 0.0;
    }

    protected function calculate(): float {
        //Agregar lógica aquí
        return 0.0;
    }

    protected abstract function applyDiscount(): float;

    protected function validate() {
        //Agregar lógica aquí
    }
}