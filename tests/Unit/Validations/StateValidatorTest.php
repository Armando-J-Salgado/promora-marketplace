<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\StateValidator;

it('passes when the status is active', function () {
    $promocode = new Promocode();
    $promocode->status = 'active';

    $validator = new StateValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the status is draft', function () {
    $promocode = new Promocode();
    $promocode->status = 'draft';

    $validator = new StateValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the status is paused', function () {
    $promocode = new Promocode();
    $promocode->status = 'paused';

    $validator = new StateValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});

it('throws when the status is expired', function () {
    $promocode = new Promocode();
    $promocode->status = 'expired';

    $validator = new StateValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código no se encuentra activo');
});
