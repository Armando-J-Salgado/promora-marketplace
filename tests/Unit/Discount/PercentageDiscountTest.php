<?php

use App\Discounts\PercentageDiscount;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('calculates the percentage discount from the order subtotal', function () {
    $service = Service::factory()->create([
        'price' => 100.0,
    ]);

    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $promocode = Promocode::factory()->percent(10.0)->create();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('returns zero when the subtotal is not positive', function () {
    $order = Order::factory()->create([
        'subtotal' => 0.0,
    ]);

    $promocode = Promocode::factory()->percent(10.0)->create();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns zero when the promocode value is not positive', function () {
    $service = Service::factory()->create([
        'price' => 100.0,
    ]);

    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $promocode = Promocode::factory()->state([
        'type' => 'percent',
        'value' => 0.0,
    ])->create();

    $discount = new PercentageDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});