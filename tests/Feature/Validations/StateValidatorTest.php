<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\StateValidator;

it('passes when the status is active', function () {
    $promocode = Promocode::factory()->create(); // Default state is active
    $validator = new StateValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the status is paused', function () {
    $promocode = Promocode::factory()->paused()->create();
    $validator = new StateValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});
