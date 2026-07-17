<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Services\PromocodeValidationService;
use App\Validations\ExistenceValidator;
use App\Validations\StateValidator;
use App\Validations\ValidationFactory;
use App\Validations\ValidityValidator;

it('returns true when the validation chain passes', function () {
    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true],
        'activation_date' => now()->subDay(),
        'expiration_date' => now()->addDay(),
        'status' => 'active'
    ]);

    $service = new PromocodeValidationService();
    
    expect($service->validate(Order::factory()->create(), $promocode))->toBeTrue();
});

it('propagates the exception when the validation chain fails', function () {
    $promocode = Promocode::factory()->create([
        'rules' => ['validity' => true, 'state' => true],
        'activation_date' => now()->addDays(2),
        'expiration_date' => now()->addDays(5),
        'status' => 'active'
    ]);

    $service = new PromocodeValidationService();
    
    expect(fn() => $service->validate(Order::factory()->create(), $promocode))
        ->toThrow(InvalidArgumentException::class, 'El código promocional aún no comienza su período de canje');
});
