<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\DiscountTemplate;
use App\Discounts\FixedDiscount;
use App\Discounts\PercentageDiscount;
use App\Discounts\TieredDiscount;
use App\Factories\DiscountFactory;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Models\Tier;

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

it('applies max_discount_amount post-calc rule through template method', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 400.0]);

    $promocode = Promocode::factory()->percent(50.0)->withMaxDiscount(100.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(100.0);
});

it('does not cap when max_discount_amount is higher than calculated discount', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(10.0)->withMaxDiscount(50.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('factory creates FixedDiscount for fixed type', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->fixed(25.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(FixedDiscount::class)
        ->and($discount)->toBeInstanceOf(DiscountTemplate::class);
});

it('fixed discount subtracts exact value when subtotal is greater', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->fixed(30.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(30.0);
});

it('fixed discount does not exceed subtotal', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 20.0]);
    $promocode = Promocode::factory()->fixed(50.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(20.0);
});

it('fixed discount calculates correctly with multiple services', function () {
    $customer = Customer::factory()->create();
    $serviceA = Service::factory()->create(['price' => 40.0]);
    $serviceB = Service::factory()->create(['price' => 60.0]);
    $promocode = Promocode::factory()->fixed(75.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($serviceA->id, ['quantity' => 1]);
    $order->services()->attach($serviceB->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(75.0);
});

it('fixed discount applies max_discount_amount rule', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);
    $promocode = Promocode::factory()->fixed(150.0)->withMaxDiscount(80.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(80.0);
});


it('factory creates TieredDiscount for tiered type', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->tiered()->create();

    Tier::factory()->withMinOrders(0, 5.0)->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(TieredDiscount::class)
        ->and($discount)->toBeInstanceOf(DiscountTemplate::class);
});

it('tiered discount returns 0 when subtotal is 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 0.0]);
    $promocode = Promocode::factory()->tiered()->create();

    Tier::factory()->withMinOrders(0, 10.0)->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('tiered discount applies max_discount_amount rule', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 500.0]);
    $promocode = Promocode::factory()->tiered()->withMaxDiscount(30.0)->create();

    Tier::factory()->withMinOrders(0, 20.0)->create(['promocode_id' => $promocode->id]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    // 500 * (20/100) = 100, capped at 30.0
    expect($discount->calculatePrice())->toBe(30.0);
});
