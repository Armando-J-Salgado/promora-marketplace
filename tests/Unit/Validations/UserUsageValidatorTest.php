<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Models\Customer;
use App\Models\PromocodeRedemption;
use App\Validations\UserUsageValidator;

it('passes when there is no user usage limit defined', function () {
    $promocode = tap(new Promocode(), function($p) {
        $p->rules = [];
    });
    $order = new Order();

    $validator = new UserUsageValidator();
    expect(fn() => $validator->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('passes when customer redemptions are below the limit', function () {
    $promocode = Promocode::factory()->withUserUsageLimit(3)->create();
    $customer = Customer::factory()->create();
    
    for($i = 0; $i < 2; $i++) {
        $prevOrder = Order::factory()->create(['customer_id' => $customer->id]);
        PromocodeRedemption::factory()->create([
            'promocode_id' => $promocode->id,
            'order_id' => $prevOrder->id
        ]);
    }

    $currentOrder = Order::factory()->create(['customer_id' => $customer->id]);

    $validator = new UserUsageValidator();
    expect(fn() => $validator->handle($currentOrder, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it("throws exception when 'El usuario ha excedido el número máximo de usos permitidos para este código promocional'", function () {
    $promocode = Promocode::factory()->withUserUsageLimit(2)->create();
    $customer = Customer::factory()->create();
    
    for($i = 0; $i < 2; $i++) {
        $prevOrder = Order::factory()->create(['customer_id' => $customer->id]);
        PromocodeRedemption::factory()->create([
            'promocode_id' => $promocode->id,
            'order_id' => $prevOrder->id
        ]);
    }

    $currentOrder = Order::factory()->create(['customer_id' => $customer->id]);

    $validator = new UserUsageValidator();
    expect(fn() => $validator->handle($currentOrder, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El usuario ha excedido el número máximo de usos permitidos para este código promocional');
});
