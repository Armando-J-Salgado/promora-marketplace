<?php
namespace App\Services;

use App\Models\Order;
use App\Models\Promocode;

class PromocodeValidationService {
    public function validate(Order $order, Promocode $promocode): bool {
        return true;
    }
}