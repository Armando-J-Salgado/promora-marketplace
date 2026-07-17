<?php

use App\Discounts\DiscountFactory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('calculates percent discount with multiple services', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 60.0]);
    $serviceB = Service::factory()->create(['price' => 40.0]);
    $promocode = Promocode::factory()->percent(25.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 1]);
    $order->services()->attach($serviceB->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(25.0);
});

it('calculates percent discount with quantity greater than 1', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 30.0]);
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 4]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(12.0);
});

it('calculates percent discount with multiple services and quantities', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 50.0]);
    $serviceB = Service::factory()->create(['price' => 25.0]);
    $promocode = Promocode::factory()->percent(20.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 2]);
    $order->services()->attach($serviceB->id, ['quantity' => 4]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(40.0);
});

it('returns 0 when order has no services', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->percent(15.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});
