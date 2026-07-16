<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Services\PromocodeValidationService;

it('returns true when the validation chain passes in the database', function () {
    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true]
    ]);
    
    $order = Order::factory()->create();
    
    $service = new PromocodeValidationService();
    expect($service->validate($order, $promocode))->toBeTrue();
});

it('propagates the exception when state validation fails in the database', function () {
    $promocode = Promocode::factory()->paused()->create([
        'rules' => ['validity' => true, 'state' => true]
    ]);
    
    $order = Order::factory()->create();
    
    $service = new PromocodeValidationService();
    expect(fn() => $service->validate($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('propagates the exception when validity validation fails in the database', function () {
    $promocode = Promocode::factory()->expired()->create([
        'rules' => ['validity' => true, 'state' => true]
    ]);
    
    $order = Order::factory()->create();
    
    $service = new PromocodeValidationService();
    expect(fn() => $service->validate($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});
