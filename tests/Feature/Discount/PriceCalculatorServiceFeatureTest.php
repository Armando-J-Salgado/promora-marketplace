<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Services\PriceCalculatorService;

it('returns the final price after applying a percentage discount', function () {
    $service = Service::factory()->create([
        'price' => 200.0,
    ]);

    $order = Order::factory()->create();
    $order->services()->attach($service->id, ['quantity' => 2]);
    $order->getSubtotal();

    $promocode = Promocode::factory()->percent(15.0)->create();

    $result = (new PriceCalculatorService)->calculatePrice($order, $promocode);

    expect($result)->toBe(340.0);
});

it('returns the original subtotal when the discount template validation fails', function () {
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

    $result = (new PriceCalculatorService)->calculatePrice($order, $promocode);

    expect($result)->toBe(100.0);
});