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

it('returns a PercentageDiscount when type is percent', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->percent(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(PercentageDiscount::class);
});

it('returns a FixedDiscount when type is fixed', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->fixed(10.0)->create();

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(FixedDiscount::class);
});

it('returns a TieredDiscount when type is tiered', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->create(['type' => 'tiered', 'value' => 10.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(TieredDiscount::class);
});

it('returns a DefaultDiscount when type is unknown', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $promocode = Promocode::factory()->create(['type' => 'unknown', 'value' => 10.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $discount = (new DiscountFactory)->make($promocode, $order);

    expect($discount)->toBeInstanceOf(DefaultDiscount::class);
});

it('always returns an instance of DiscountTemplate', function () {
    $customer = Customer::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);

    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $types = ['percent', 'fixed', 'tiered', 'anything_else'];

    foreach ($types as $type) {
        $promocode = Promocode::factory()->create(['type' => $type, 'value' => 10.0]);
        $discount = (new DiscountFactory)->make($promocode, $order);

        expect($discount)->toBeInstanceOf(DiscountTemplate::class);
    }
});
