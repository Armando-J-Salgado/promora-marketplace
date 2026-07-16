<?php
namespace App\Orderable;
use App\Orderable\OrderContext;

interface OrderableInterface {
    public function getSubtotal(): float;
    public function getOrderContext(): OrderContext;
}