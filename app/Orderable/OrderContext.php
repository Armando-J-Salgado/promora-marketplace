<?php
namespace App\Orderable;

use App\Models\Customer;

class OrderContext {
    public function __construct(
        Customer $buyerProfile,
        array $categoriesId,
        array $currentOrders,
    ) {}
}