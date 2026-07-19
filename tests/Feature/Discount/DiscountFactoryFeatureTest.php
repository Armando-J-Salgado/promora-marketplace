<?php

use App\Factories\DiscountFactory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Models\Tier;

it('percent discount created by factory calculates correct amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);
    $promocode = Promocode::factory()->percent(15.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(30.0);
});

it('fixed discount created by factory calculates correct amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->fixed(25.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(25.0);
});

it('tiered discount created by factory calculates correct amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->tiered()->create();

    // Tramo base: 0+ órdenes → 20%
    Tier::factory()->withMinOrders(0, 20.0)->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    // subtotal=100, tramo=20% → 20.0
    expect($discount->calculatePrice())->toBe(20.0);
});

it('default discount created by factory returns 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->create(['type' => 'nonexistent', 'value' => 50.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('factory produces working discounts with multiple services', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 80.0]);
    $serviceB = Service::factory()->create(['price' => 120.0]);
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 1]);
    $order->services()->attach($serviceB->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(20.0);
});
