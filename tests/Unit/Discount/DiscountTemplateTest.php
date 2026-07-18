<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\PercentageDiscount;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;

it('calculates discount and applies max_discount_amount post-calc rule', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);

    $promocode = Promocode::factory()->percent(50.0)->withMaxDiscount(30.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(30.0);
});

it('does not cap discount when max_discount_amount is not configured', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);

    $promocode = Promocode::factory()->percent(50.0)->create([
        'rules' => ['validity' => true, 'state' => true],
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(100.0);
});

it('returns 0 when subtotal is 0 because formula yields 0', function () {
    $customer = Customer::factory()->create();
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id, 'subtotal' => 0]);

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns 0 when promocode value is 0', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(0.0)->create(['value' => 0.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('max_discount_amount does not increase discount when cap is higher than calculated', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(10.0)->withMaxDiscount(500.0)->create();

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

it('throws when global_amount_limit has been fully consumed', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $promocode = Promocode::factory()->percent(20.0)->withGlobalAmountLimit(50.0)->create();

    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'discount_amount' => 50.0,
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect(fn () => $discount->calculatePrice())
        ->toThrow(InvalidArgumentException::class, 'Se alcanzó el límite de monto global acumulado');
});

it('caps discount to remaining global_amount_limit', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 200.0]);

    $promocode = Promocode::factory()->percent(50.0)->withGlobalAmountLimit(80.0)->create();

    PromocodeRedemption::factory()->create([
        'promocode_id' => $promocode->id,
        'discount_amount' => 60.0,
    ]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(20.0);
});

it('global_amount_limit and max_discount_amount apply together taking the lowest', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 500.0]);

    $promocode = Promocode::factory()->percent(50.0)
        ->withGlobalAmountLimit(200.0)
        ->withMaxDiscount(100.0)
        ->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(100.0);
});
