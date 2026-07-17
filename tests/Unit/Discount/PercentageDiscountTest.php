<?php

use App\Discounts\PercentageDiscount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('calculates 10% of subtotal', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(20.0);
});

it('calculates 50% of subtotal', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->percent(50.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(50.0);
});

it('calculates 100% of subtotal', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 75.0]);
    $promocode = Promocode::factory()->percent(100.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(75.0);
});

it('returns 0 when subtotal is 0', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->percent(25.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal' => 0]);

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 when value is 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->percent(0.0)->create(['value' => 0.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('calculates correctly with decimal percentage', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);
    $promocode = Promocode::factory()->percent(7.5)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(15.0);
});
