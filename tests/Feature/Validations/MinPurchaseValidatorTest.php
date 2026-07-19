<?php

use App\Models\Order;
use App\Models\Service;
use App\Models\Promocode;
use App\Validations\MinPurchaseValidator;

it('passes when order subtotal meets the minimum', function () {
    $promocode = Promocode::factory()->withMinPurchase(50.0)->create();
    
    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 50.0]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $order->getSubtotal();

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'La orden no cumple con el subtotal mínimo necesario'", function () {
    $promocode = Promocode::factory()->withMinPurchase(500.0)->create();
    
    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 100.0]);
    $order->services()->attach($service->id, ['quantity' => 1]);

    $order->getSubtotal();

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no cumple con el subtotal mínimo necesario');
});

it("throws exception when 'El código promocional no tiene definido el mínimo'", function () {
    $promocode = Promocode::factory()->create();

    $order = Order::factory()->create();

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no tiene definido el mínimo');
});
