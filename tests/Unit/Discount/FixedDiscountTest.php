<?php

use App\Discounts\FixedDiscount;
use App\Models\Order;
use App\Models\Promocode;

beforeEach(function () {
    $this->order = Mockery::mock(Order::class)->makePartial();
    $this->promocode = Mockery::mock(Promocode::class)->makePartial();
});

afterEach(function () {
    Mockery::close();
});

it('aplica el descuento fijo cuando el subtotal lo permite', function () {
    $this->promocode->value = 10.0;

    $this->order->shouldReceive('getSubtotal')->once()->andReturn(100.0);

    $discount = new FixedDiscount($this->order, $this->promocode);

    expect($discount->calculatePrice())->toBe(10.0);
});

it('limita el descuento al subtotal cuando el valor del código lo excede', function () {
    $this->promocode->value = 20.0;

    $this->order->shouldReceive('getSubtotal')->once()->andReturn(5.0);

    $discount = new FixedDiscount($this->order, $this->promocode);

    expect($discount->calculatePrice())->toBe(5.0);
});

it('retorna 0 cuando el subtotal es cero', function () {
    $this->promocode->value = 10.0;

    $this->order->shouldReceive('getSubtotal')->once()->andReturn(0.0);

    $discount = new FixedDiscount($this->order, $this->promocode);

    // validate() retorna false cuando subtotal es 0
    expect($discount->calculatePrice())->toBe(0.0);
});

it('retorna 0 cuando el valor del código es cero', function () {
    $this->promocode->value = 0.0;

    $this->order->shouldReceive('getSubtotal')->once()->andReturn(100.0);

    $discount = new FixedDiscount($this->order, $this->promocode);

    // validate() retorna false cuando value es 0
    expect($discount->calculatePrice())->toBe(0.0);
});
