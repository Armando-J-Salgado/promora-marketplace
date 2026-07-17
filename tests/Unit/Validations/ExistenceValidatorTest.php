<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ExistenceValidator;

it('passes when the promocode exists', function () {
    $promocode = Promocode::factory()->create();
    $validator = new ExistenceValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), tap(new Promocode(), fn($p) => $p->id = $promocode->id)))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode does not exist', function () {
    $validator = new ExistenceValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), tap(new Promocode(), fn($p) => $p->id = 9999)))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');
});
