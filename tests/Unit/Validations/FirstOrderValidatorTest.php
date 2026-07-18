<?php

use App\Logger\Logger;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Orderable\OrderContext;
use App\Validations\FirstOrderValidator;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('passes when the customer has no previous orders', function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->shouldReceive('getOrderContext')->andReturn(new OrderContext(new Customer, [], []));

    $promocode = new Promocode;

    $validator = new FirstOrderValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);

    expect(Logger::getInstance()->getLogs())
        ->toContain("[PASS] FirstOrderValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
});

it("throws exception when 'El código promocional aplica solo para la primera orden del cliente'", function () {
    $order = Mockery::mock(Order::class)->makePartial();
    $order->id = 2; // Current order id

    $previousOrder = new Order;
    $previousOrder->id = 1; // Previous order id
    $previousOrder->created_at = now()->subDay();

    $order->shouldReceive('getOrderContext')->andReturn(new OrderContext(new Customer, [], [$previousOrder]));

    $promocode = new Promocode;

    $validator = new FirstOrderValidator;
    expect(fn () => $validator->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aplica solo para la primera orden del cliente');

    expect(Logger::getInstance()->getLogs())
        ->toContain("[FAIL] FirstOrderValidator | code=code_already_used | promocode=#{$promocode->id} | order=#{$order->id} | El código promocional aplica solo para la primera orden del cliente");
});
