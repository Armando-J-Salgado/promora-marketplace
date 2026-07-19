<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ValidityValidator;

it('passes when the promocode is within the valid period', function () {
    $promocode = Promocode::factory()->create(); // Default state is valid
    $validator = new ValidityValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode activation is in the future', function () {
    $promocode = Promocode::factory()->notYetActive()->create();
    $validator = new ValidityValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aún no comienza su período de canje');
});

it('throws when the promocode has expired', function () {
    $promocode = Promocode::factory()->expired()->create();
    $validator = new ValidityValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});
