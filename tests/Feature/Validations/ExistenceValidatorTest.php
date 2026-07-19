<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Validations\ExistenceValidator;

it('passes when the promocode exists in the database', function () {
    $promocode = Promocode::factory()->create();
    $validator = new ExistenceValidator();
    
    expect(fn() => $validator->handle(Order::factory()->create(), $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws when the promocode does not exist in the database', function () {
    $promocode = Promocode::factory()->create();
    $id = $promocode->id;
    $promocode->delete();

    $validator = new ExistenceValidator();
    
    // Create a new instance without persisting, but with the deleted ID
    $stalePromocode = new Promocode();
    $stalePromocode->id = $id;

    expect(fn() => $validator->handle(Order::factory()->create(), $stalePromocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional no existe');
});
