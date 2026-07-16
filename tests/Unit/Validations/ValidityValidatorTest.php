<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ValidityValidator;
use Illuminate\Support\Carbon;

it('passes when within the valid period', function () {
    $promocode = new Promocode();
    $promocode->activation_date = now()->subDay();
    $promocode->expiration_date = now()->addDay();

    $validator = new ValidityValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode activation is in the future', function () {
    $promocode = new Promocode();
    $promocode->activation_date = now()->addDays(2);
    $promocode->expiration_date = now()->addDays(5);

    $validator = new ValidityValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aún no comienza su período de canje');
});

it('throws when the promocode has expired', function () {
    $promocode = new Promocode();
    $promocode->activation_date = now()->subDays(5);
    $promocode->expiration_date = now()->subDays(2);

    $validator = new ValidityValidator();
    expect(fn() => $validator->handle(new Order(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional ha caducado');
});
