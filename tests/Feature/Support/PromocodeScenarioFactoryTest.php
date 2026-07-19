<?php

use App\Services\PromocodeValidationService;
use App\Support\Promocode\PromocodeScenarioFactory;

it('builds a blocked and an allowed scenario for each of the 11 validators', function (string $method) {
    $scenario = (new PromocodeScenarioFactory)->$method();
    $service = new PromocodeValidationService;

    expect(fn () => $service->validate($scenario['blocked']->order, $scenario['blocked']->promocode))
        ->toThrow(InvalidArgumentException::class);

    expect($service->validate($scenario['allowed']->order, $scenario['allowed']->promocode))
        ->toBeTrue();
})->with([
    'existence',
    'validity',
    'state',
    'elegibleCategories',
    'minPurchase',
    'firstOrder',
    'userUsage',
    'globalUsage',
    'restrictedUsage',
    'globalAmount',
    'maxDiscount',
]);

it('exposes all() with an entry per validator in TDR order', function () {
    $scenarios = (new PromocodeScenarioFactory)->all();

    expect(array_keys($scenarios))->toBe([
        'ExistenceValidator',
        'ValidityValidator',
        'StateValidator',
        'ElegibleCategoriesValidator',
        'MinPurchaseValidator',
        'FirstOrderValidator',
        'UserUsageValidator',
        'GlobalUsageValidator',
        'RestrictedUsageValidator',
        'GlobalAmountValidator',
        'MaxDiscountValidator',
    ]);

    foreach ($scenarios as $scenario) {
        expect($scenario)->toHaveKeys(['blocked', 'allowed']);
    }
});
