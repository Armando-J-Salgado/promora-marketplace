<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Orderable\OrderContext;
use App\Models\Customer;
use App\Validations\FirstOrderValidator;

it('passes when the customer has no previous orders', function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->shouldReceive('getOrderContext')->andReturn(new OrderContext(new Customer(), [], []));

    $promocode = new Promocode();

    $validator = new FirstOrderValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El código promocional aplica solo para la primera orden del cliente'", function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->id = 2; // Current order id
    
    $previousOrder = new Order();
    $previousOrder->id = 1; // Previous order id
    $previousOrder->created_at = now()->subDay();
    
    $order->shouldReceive('getOrderContext')->andReturn(new OrderContext(new Customer(), [], [$previousOrder]));

    $promocode = new Promocode();

    $validator = new FirstOrderValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aplica solo para la primera orden del cliente');
});
