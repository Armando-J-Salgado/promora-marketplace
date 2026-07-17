<?php

use App\Discounts\DiscountFactory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('factory returns DefaultDiscount that calculates 0 for unknown type', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);
    $promocode = Promocode::factory()->create(['type' => 'unknown', 'value' => 50.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('factory returns DefaultDiscount that calculates 0 with multiple services', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 100.0]);
    $serviceB = Service::factory()->create(['price' => 150.0]);
    $promocode = Promocode::factory()->create(['type' => 'invalid', 'value' => 30.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 2]);
    $order->services()->attach($serviceB->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('factory returns DefaultDiscount that calculates 0 with empty order', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->create(['type' => 'fake', 'value' => 10.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});
