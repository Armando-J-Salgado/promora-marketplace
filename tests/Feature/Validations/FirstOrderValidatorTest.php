<?php

use App\Models\Order;
use App\Models\Customer;
use App\Models\Promocode;
use App\Validations\FirstOrderValidator;

it('passes when the customer has no previous orders', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    
    $promocode = Promocode::factory()->firstOrderOnly()->create();

    $validator = new FirstOrderValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El código promocional aplica solo para la primera orden del cliente'", function () {
    $customer = Customer::factory()->create();
    
    Order::factory()->create(['customer_id' => $customer->id, 'created_at' => now()->subDay()]);
    $order2 = Order::factory()->create(['customer_id' => $customer->id, 'created_at' => now()]);
    
    $promocode = Promocode::factory()->firstOrderOnly()->create();

    $validator = new FirstOrderValidator();
    expect(fn() => $validator->handle($order2, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aplica solo para la primera orden del cliente');
});
