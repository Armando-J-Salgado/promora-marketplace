<?php

use App\Discounts\DefaultDiscount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('returns 0 regardless of subtotal and value', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 500.0]);
    $promocode = Promocode::factory()->create(['value' => 100.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 when subtotal is 0', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->create(['value' => 50.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal' => 0]);

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 when value is 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->create(['value' => 0.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 with a high value promocode', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 10.0]);
    $promocode = Promocode::factory()->create(['value' => 9999.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});
