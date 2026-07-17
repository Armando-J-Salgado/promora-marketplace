<?php

use App\Discounts\DefaultDiscount;
use App\Discounts\PercentageDiscount;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('calculates the percentage discount from the order subtotal', function () {
    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $order->services()->attach($service->id, ['quantity' => 2]);

    $promocode = Promocode::factory()->percent(10.0)->create();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(20.0);
});

it('returns zero when the order subtotal or promocode value is invalid', function () {
    $order = Order::factory()->create();
    $promocode = Promocode::factory()->create(['value' => 0]);

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns zero for the default discount implementation', function () {
    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $order->services()->attach($service->id, ['quantity' => 2]);

    $promocode = Promocode::factory()->percent(10.0)->create();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});
