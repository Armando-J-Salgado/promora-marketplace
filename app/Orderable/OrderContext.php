<?php
namespace App\Orderable;

use App\Models\Customer;

class OrderContext {
    public Customer $buyerProfile;
    public array $categoriesId;
    public array $currentOrders;

    public function __construct(Customer $customer, array $categories, array $orders) {
        $this->buyerProfile = $customer;
        $this->categoriesId = $categories;
        $this->currentOrders = $orders;
    }
}