<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\PercentageDiscount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('returns 0 when validate fails because subtotal is 0', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal' => 0]);

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 when validate fails because promocode value is 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(0.0)->create(['value' => 0.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('calls applyDiscount when validate passes', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);

    $promocode = Promocode::factory()->percent(25.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(50.0);
});

it('validate returns false when both subtotal and value are 0', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->create(['value' => 0.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal' => 0]);

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('validate passes when subtotal > 0 and value > 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 50.0]);

    $promocode = Promocode::factory()->percent(20.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('PercentageDiscount calculates subtotal * (value / 100)', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 300.0]);

    $promocode = Promocode::factory()->percent(20.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(60.0);
});

it('PercentageDiscount with 100% returns full subtotal as discount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 150.0]);

    $promocode = Promocode::factory()->percent(100.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(150.0);
});

it('DefaultDiscount always returns 0 as discount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->create(['value' => 20.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});
