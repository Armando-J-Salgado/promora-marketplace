<?php

namespace App\Factories;

use InvalidArgumentException;
use App\Validations\PromocodeValidationHandler;
use App\Validations\ExistenceValidator;
use App\Validations\StateValidator;
use App\Validations\ValidityValidator;
use App\Validations\ElegibleCategoriesValidator;
use App\Validations\MinPurchaseValidator;
use App\Validations\FirstOrderValidator;
use App\Validations\UserUsageValidator;
use App\Validations\GlobalUsageValidator;
use App\Validations\RestrictedUsageValidator;
use App\Validations\GlobalAmountValidator;
use App\Validations\MaxDiscountValidator;

class ValidationFactory {
    public static function make (string $type, float $discount = 0): PromocodeValidationHandler {
        return match($type) {
            'existence' => new ExistenceValidator(),
            'validity' => new ValidityValidator(),
            'state' => new StateValidator(),
            'min_purchase_amount' => new MinPurchaseValidator(),
            'elegible_categories' => new ElegibleCategoriesValidator(),
            'first_order_only' => new FirstOrderValidator(),
            'user_usage_limit' => new UserUsageValidator(),
            'global_usage_limit' => new GlobalUsageValidator(),
            'restricted_usage' => new RestrictedUsageValidator(),
            'global_amount_limit' => new GlobalAmountValidator($discount),
            'max_discount_amount' => new MaxDiscountValidator($discount),
            default => throw new InvalidArgumentException('No es una validación permitida')
        };
    }
}