<?php

use App\Logger\Logger;

beforeEach(function () {
    $reflection = new ReflectionClass(Logger::class);
    $instance = $reflection->getProperty('instance');
    $instance->setValue(null, null);
});

it('runs the demo walkthrough for all 11 validators and exits successfully', function () {
    $this->artisan('promocode:play', ['--demo' => true, '--no-pause' => true])
        ->assertExitCode(0);
});

it('prints each validator name during the demo walkthrough', function () {
    $this->artisan('promocode:play', ['--demo' => true, '--no-pause' => true])
        ->expectsOutputToContain('ExistenceValidator')
        ->expectsOutputToContain('ValidityValidator')
        ->expectsOutputToContain('StateValidator')
        ->expectsOutputToContain('ElegibleCategoriesValidator')
        ->expectsOutputToContain('MinPurchaseValidator')
        ->expectsOutputToContain('FirstOrderValidator')
        ->expectsOutputToContain('UserUsageValidator')
        ->expectsOutputToContain('GlobalUsageValidator')
        ->expectsOutputToContain('RestrictedUsageValidator')
        ->expectsOutputToContain('GlobalAmountValidator')
        ->expectsOutputToContain('MaxDiscountValidator')
        ->assertExitCode(0);
});

it('prints the semantic error code for a blocked case during the demo walkthrough', function () {
    $this->artisan('promocode:play', ['--demo' => true, '--no-pause' => true])
        ->expectsOutputToContain('code=invalid_code')
        ->expectsOutputToContain('code=expired_coupon')
        ->expectsOutputToContain('code=usage_limit_reached')
        ->expectsOutputToContain('code=restricted_usage')
        ->expectsOutputToContain('code=min_amount_required')
        ->expectsOutputToContain('code=code_already_used')
        ->expectsOutputToContain('code=maximum_discount_reached')
        ->assertExitCode(0);
});
