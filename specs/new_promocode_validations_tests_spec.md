# Promocode Validations TDD Spec Plan

## Objective
This specification defines the Test-Driven Development (TDD) plan to create unit and integration tests for the newly introduced Promocode validators and the complete validation flow. This spec should guide the implementation plan to ensure full test coverage for all validation scenarios.

## Scope
The tests will cover the following new validation handlers in `app/Validations/`:
- `FirstOrderValidator`
- `GlobalAmountValidator`
- `GlobalUsageValidator`
- `MaxDiscountValidator`
- `MinPurchaseValidator`
- `RestrictedUsageValidator`
- `UserUsageValidator`
- `ElegibleCategoriesValidator`

*Note: Existing validators (`ExistenceValidator`, `StateValidator`, `ValidityValidator`) already have tests implemented and are out of scope for this spec.*

## General Requirements
- **Testing Framework**: Pest PHP (or PHPUnit as per project conventions).
- **Test Organization**: Tests should be organized by individual Validator (Unit tests) and a final suite for the complete validation flow (Integration tests).
- **Assertions**: 
  - To fulfill the assertion in code, asserting the exception type `InvalidArgumentException` is sufficient.
  - To name the test/assertion, use the specific exception message (e.g., "it throws exception when 'El monto a descontar sobrepasa el límite del cupón'").
- **Mocks & Data**: Use logical mock values and factories to build the `Order` and `Promocode` state.

---

## Unit Test Plans (By Validator)

### 1. `FirstOrderValidator`
- **Rule**: Applies only if the user has never bought before (has no registered orders).
- **Test 1 - Success**: User has no previous orders. Validates successfully.
- **Test 2 - Failure**: User has at least 1 previous order. Throws `InvalidArgumentException` ('El código promocional aplica solo para la primera orden del cliente').

### 2. `GlobalAmountValidator`
- **Rule**: The total amount discounted historically by all redemptions of this promocode plus the current discount must not exceed the `global_amount_limit`.
- **Test 1 - Success**: Total historical discounted amount + current discount < `global_amount_limit`. Validates successfully.
- **Test 2 - Success**: Total historical discounted amount + current discount == `global_amount_limit`. Validates successfully.
- **Test 3 - Failure**: Total historical discounted amount + current discount > `global_amount_limit`. Throws `InvalidArgumentException` ('El código promocional supera su presupuesto máximo de descuentos').
- **Test 4 - Missing Rule**: Promocode lacks the `global_amount_limit` rule. Throws `InvalidArgumentException` ('El cupón no tiene configurado la cantidad límite global').

### 3. `GlobalUsageValidator`
- **Rule**: The total number of redemptions for this promocode must be strictly less than `global_usage_limit` before applying.
- **Test 1 - Success**: Current redemptions < `global_usage_limit`. Validates successfully.
- **Test 2 - Failure**: Current redemptions >= `global_usage_limit`. Throws `InvalidArgumentException` ('El código promocional ya ha superado el número máximo de canjes globales').
- **Test 3 - Missing Rule**: Promocode lacks `global_usage_limit` rule. Throws `InvalidArgumentException` ('El máximo global no ha sido definido para este cupón').

### 4. `MaxDiscountValidator`
- **Rule**: The calculated discount for the current order must not exceed `max_discount_amount`.
- **Test 1 - Success**: Current discount <= `max_discount_amount`. Validates successfully.
- **Test 2 - Failure**: Current discount > `max_discount_amount`. Throws `InvalidArgumentException` ('El monto a descontar sobrepasa el límite del cupón').
- **Test 3 - Missing Rule**: Promocode lacks `max_discount_amount` rule. Throws `InvalidArgumentException` ('El monto máximo que se puede descontar no ha sido establecido para el código promocional').

### 5. `MinPurchaseValidator`
- **Rule**: The order's subtotal must be greater than or equal to `min_purchase_amount`.
- **Test 1 - Success**: Order subtotal >= `min_purchase_amount`. Validates successfully.
- **Test 2 - Failure**: Order subtotal < `min_purchase_amount`. Throws `InvalidArgumentException` ('La orden no cumple con el subtotal mínimo necesario').
- **Test 3 - Missing Rule**: Promocode lacks `min_purchase_amount` or it is 0. Throws `InvalidArgumentException` ('El código promocional no tiene definido el mínimo').

### 6. `RestrictedUsageValidator`
- **Rule**: The customer applying the code must be explicitly listed in the promocode's allowed customers.
- **Test 1 - Success**: Customer is in the allowed customers list. Validates successfully.
- **Test 2 - Failure**: Customer is not in the allowed customers list. Throws `InvalidArgumentException` ('El código promocional no ha sido asignado a este usuario').

### 7. `UserUsageValidator`
- **Rule**: The specific user must have fewer redemptions of this promocode than `user_usage_limit`.
- **Test 1 - Success (No Limit)**: Promocode has no `user_usage_limit`. Validates successfully.
- **Test 2 - Success (Under Limit)**: Customer redemptions < `user_usage_limit`. Validates successfully.
- **Test 3 - Failure**: Customer redemptions >= `user_usage_limit`. Throws `InvalidArgumentException` ('El usuario ha excedido el número máximo de usos permitidos para este código promocional').

### 8. `ElegibleCategoriesValidator`
- **Rule**: The order must contain at least one category that matches the eligible categories directly, or matches up and down the tree (1 parent level, and immediate children).
- **Test 1 - Success (Direct Match)**: Order contains a category directly listed in `elegible_categories`.
- **Test 2 - Success (Parent Match)**: Order contains a category whose parent is listed in `elegible_categories`.
- **Test 3 - Success (Child Match)**: Order contains a category whose child is listed in `elegible_categories`.
- **Test 4 - Failure**: None of the order's categories (nor their direct parent/children) match the `elegible_categories` list. Throws `InvalidArgumentException` ('La orden no contiene ninguna categoría elegible para este código promocional').

---

## Integration Test Plan (Complete Validation Flow)

- **Objective**: Ensure that multiple validations work correctly when chained together via the Chain of Responsibility pattern. There is no strict order required, but all scenarios must be covered.
- **Test 1 - Complete Flow Success**: Setup an Order and Promocode that passes *all* validations simultaneously (is first order, meets minimum purchase, under global amount limit, valid category, etc.). Validates successfully.
- **Test 2 - Fail Fast Validation**: Setup a chain of multiple validators where the first one passes, but the second one fails. Assert that the process throws an `InvalidArgumentException` immediately upon failure and does not execute the subsequent validations in the chain.
