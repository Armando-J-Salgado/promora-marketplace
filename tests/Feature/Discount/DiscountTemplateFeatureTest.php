<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\DiscountTemplate;
use App\Discounts\PercentageDiscount;
use App\Factories\DiscountFactory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('factory creates PercentageDiscount for percent type', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(PercentageDiscount::class)
        ->and($discount)->toBeInstanceOf(DiscountTemplate::class);
});

it('factory creates DefaultDiscount for unknown type', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->create(['type' => 'bogus', 'value' => 10.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(DefaultDiscount::class)
        ->and($discount)->toBeInstanceOf(DiscountTemplate::class);
});

it('percent discount calculates correctly with multiple services', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 60.0]);
    $serviceB = Service::factory()->create(['price' => 40.0]);

    $promocode = Promocode::factory()->percent(20.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 1]);
    $order->services()->attach($serviceB->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(20.0);
});

it('percent discount calculates correctly with quantity > 1', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 50.0]);

    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 3]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(15.0);
});

it('default discount returns 0 regardless of order value', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 500.0]);

    $promocode = Promocode::factory()->create(['type' => 'invalid_type', 'value' => 100.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 2]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('caps discount to max_discount_amount when exceeded via factory', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 400.0]);

    $promocode = Promocode::factory()->percent(50.0)->withMaxDiscount(100.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(100.0);
});

it('passes when discount is within max_discount_amount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(10.0)->withMaxDiscount(50.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(10.0);
});
