<?php

namespace App\Support\Promocode;

use App\Models\Order;
use App\Models\Promocode;

class PromocodeScenario
{
    public function __construct(
        public readonly Order $order,
        public readonly Promocode $promocode,
    ) {}
}
