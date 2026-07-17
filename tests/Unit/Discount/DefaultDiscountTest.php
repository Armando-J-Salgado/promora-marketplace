<?php

use App\Discounts\DefaultDiscount;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;

it('returns zero for an unsupported promocode type', function () {
    $service = Service::factory()->create([
        'price' => 100.0,
    ]);

    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $promocode = Promocode::factory()->state([
        'type' => 'unknown',
        'value' => 12.0,
    ])->create();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});

it('returns zero when the template validation fails', function () {
    $order = Order::factory()->create([
        'subtotal' => 0.0,
    ]);

    $promocode = Promocode::factory()->state([
        'type' => 'unknown',
        'value' => 12.0,
    ])->create();

    $discount = new DefaultDiscount($order, $promocode);

    expect($discount->calculatePrice())->toBe(0.0);
});