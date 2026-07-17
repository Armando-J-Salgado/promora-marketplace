<?php

use App\Models\Order;
use App\Models\Promocode;
use App\Models\Service;
use App\Models\Customer;
use App\Models\Category;
use App\Validations\MinPurchaseValidator;
use App\Validations\UserUsageValidator;
use App\Validations\FirstOrderValidator;
use App\Validations\GlobalUsageValidator;
use App\Validations\GlobalAmountValidator;
use App\Validations\MaxDiscountValidator;
use App\Validations\RestrictedUsageValidator;
use App\Validations\ElegibleCategoriesValidator;

it('passes complete validation chain when all conditions are met', function () {
    $category = Category::factory()->create();
    $customer = Customer::factory()->create();

    $promocode = Promocode::factory()
        ->withMinPurchase(50.0)
        ->withGlobalUsageLimit(5)
        ->withUserUsageLimit(2)
        ->withGlobalAmountLimit(100.0)
        ->withMaxDiscount(20.0)
        ->withEligibleCategories([$category->id])
        ->firstOrderOnly()
        ->create();

    $promocode->allowedCustomers()->attach($customer->id);

    $service = Service::factory()->create(['price' => 100.0, 'category_id' => $category->id]);
    
    $order = Order::factory()->create(['customer_id' => $customer->id]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $validator1 = new MinPurchaseValidator();
    $validator2 = new UserUsageValidator();
    $validator3 = new FirstOrderValidator();
    $validator4 = new GlobalUsageValidator();
    $validator5 = new GlobalAmountValidator(15.0);
    $validator6 = new MaxDiscountValidator(15.0);
    $validator7 = new RestrictedUsageValidator();
    $validator8 = new ElegibleCategoriesValidator();

    $validator1->setNext($validator2)
               ->setNext($validator3)
               ->setNext($validator4)
               ->setNext($validator5)
               ->setNext($validator6)
               ->setNext($validator7)
               ->setNext($validator8);

    expect(fn() => $validator1->handle($order, $promocode))
        ->not->toThrow(InvalidArgumentException::class);
});

it('throws immediately when one validator in the chain fails', function () {
    $promocode = Promocode::factory()
        ->withMinPurchase(500.0)
        ->withUserUsageLimit(2)
        ->create();

    $order = Order::factory()->create();
    $service = Service::factory()->create(['price' => 10.0]);
    $order->services()->attach($service->id, ['quantity' => 1]);
    $order->getSubtotal();

    $validator1 = new MinPurchaseValidator();
    
    $validator2Mock = Mockery::mock(UserUsageValidator::class)->makePartial();
    $validator2Mock->shouldNotReceive('handle');
    
    $validator1->setNext($validator2Mock);

    expect(fn() => $validator1->handle($order, $promocode))
        ->toThrow(InvalidArgumentException::class, 'La orden no cumple con el subtotal mínimo necesario');
});
