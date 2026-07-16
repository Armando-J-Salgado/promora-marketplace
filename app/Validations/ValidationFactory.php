<?php

namespace App\Validations;

use InvalidArgumentException;
use App\Validations\ExistenceValidator;
use App\Validations\StateValidator;
use App\Validations\ValidityValidator;

class ValidationFactory {
    public static function make (string $type): PromocodeValidationHandler {
        return match($type) {
            'existence' => new ExistenceValidator(),
            'validity' => new ValidityValidator(),
            'state' => new StateValidator(),
            // 'minimum_purchase' => new MinPurchaseValidator(),
            // 'elegible_categories' => new ElegibleCategoriesValidator(),
            // 'first_order' => new FirstOrderValidator(),
            // 'user_usage' => new UserUsageValidator(),
            // 'global_usage' => new GlobalUsageValidator(),
            // 'restricted_usage' => new RestrictedUsageValidator(),
            // 'global_amount' => new RestrictedUsageValidator(),
            // 'max_amount' => new MaxAmountValidator(),
            default => throw new InvalidArgumentException('No es una validación permitida')
        };
    }
}