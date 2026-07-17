<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\MinPurchaseValidator;

it('passes when subtotal meets the minimum', function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->shouldReceive('getSubtotal')->andReturn(100.0);

    $promocode = tap(new Promocode(), fn($p) => $p->rules = ['min_purchase_amount' => 50.0]);

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'La orden no cumple con el subtotal mínimo necesario'", function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->shouldReceive('getSubtotal')->andReturn(30.0);

    $promocode = tap(new Promocode(), fn($p) => $p->rules = ['min_purchase_amount' => 50.0]);

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no cumple con el subtotal mínimo necesario');
});

it("throws exception when 'El código promocional no tiene definido el mínimo'", function () {
    $order = Mockery::mock(Order::class)->makePartial();

    $promocode = tap(new Promocode(), fn($p) => $p->rules = ['min_purchase_amount' => 0.0]);

    $validator = new MinPurchaseValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no tiene definido el mínimo');
});
