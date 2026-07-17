# Implementation Plan: New Promocode Validators — TDD Test Suite

## Overview

This plan guides the implementation of unit and feature tests for the 8 newly added Promocode validators. It follows the Chain of Responsibility pattern already established in the codebase and mirrors the test conventions set by the existing `ExistenceValidator`, `ValidityValidator`, and `StateValidator` tests.

> [!NOTE]
> All production bugs identified during research have been fixed by the user:
> - `Category` model relationships now use `category_id` as the FK, matching the migration.
> - `Order::getOrderContext()` now collects `$service->category->id` instead of `$service->id`.
> - `UserUsageValidator` now queries customer usage via `whereHas('order', fn($q) => $q->where('customer_id', ...))`, joining through the `Order` relationship instead of querying a non-existent `customer_id` column directly on `promocode_redemptions`.

---

## Proposed Changes

### Step 1 — Fill Incomplete Factories

These factories are currently empty and are required for Feature tests to run.

#### [MODIFY] [`CategoryFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/CategoryFactory.php)
Provide `name` and a nullable `parent_id`. Add a `withParent()` named state for hierarchy tests.

```php
public function definition(): array
{
    return [
        'name'      => fake()->word(),
        'parent_id' => null,
    ];
}

public function withParent(Category $parent): static
{
    return $this->state(['parent_id' => $parent->id]);
}
```

#### [MODIFY] [`ServiceFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/ServiceFactory.php)
Provide `name`, `price`, and a `category_id` foreign key.

```php
public function definition(): array
{
    return [
        'name'        => fake()->words(2, true),
        'price'       => fake()->randomFloat(2, 10, 200),
        'category_id' => Category::factory(),
    ];
}
```

#### [MODIFY] [`PromocodeRedemptionFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeRedemptionFactory.php)
Provide all required foreign keys and `discount_amount`.

```php
public function definition(): array
{
    return [
        'discount_amount' => fake()->randomFloat(2, 5, 100),
        'promocode_id'    => Promocode::factory(),
        'order_id'        => Order::factory(),
        'customer_id'     => Customer::factory(),
    ];
}
```

#### [MODIFY] [`PromocodeFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeFactory.php)
Add named states for the new validators' rules. The default state remains unchanged.

```php
// New states to add:

public function withMinPurchase(float $amount): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['min_purchase_amount' => $amount])]);
}

public function withGlobalUsageLimit(int $limit): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['global_usage_limit' => $limit])]);
}

public function withUserUsageLimit(int $limit): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['user_usage_limit' => $limit])]);
}

public function withGlobalAmountLimit(float $limit): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['global_amount_limit' => $limit])]);
}

public function withMaxDiscount(float $max): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['max_discount_amount' => $max])]);
}

public function withEligibleCategories(array $categoryIds): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['elegible_categories' => $categoryIds])]);
}

public function firstOrderOnly(): static
{
    return $this->state(fn($a) => ['rules' => array_merge($a['rules'], ['first_order_only' => true])]);
}
```

---

### Step 3 — Unit Tests

Unit tests instantiate the validator directly and pass plain `new` model instances (not persisted). DB-hitting queries must be avoided by setting up state via in-memory relationships or mocking.

**Convention** (from existing tests):
- Use `new Order()` / `new Promocode()` and set properties directly.
- Assert using `expect(fn() => ...)->toThrow(InvalidArgumentException::class)`.
- Test names use the exact exception message string.

---

#### [NEW] `tests/Unit/Validations/MinPurchaseValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when subtotal meets the minimum` | `$order->subtotal = 100`, `$promocode->rules = ['min_purchase_amount' => 50]` | No exception |
| 2 | `throws 'La orden no cumple con el subtotal mínimo necesario'` | `$order->subtotal = 30`, `$promocode->rules = ['min_purchase_amount' => 50]` | `InvalidArgumentException` |
| 3 | `throws 'El código promocional no tiene definido el mínimo'` | `$promocode->rules = ['min_purchase_amount' => 0]` | `InvalidArgumentException` |

> [!NOTE]
> `MinPurchaseValidator` calls `$order->getSubtotal()`, which queries DB services. In the unit test, mock `Order` or set `$order->subtotal` directly and override `getSubtotal()` to return the value. Alternatively, if the validator is refactored to read from `$order->subtotal` directly, this is no longer needed. This is a **potential refactor candidate** to flag.

---

#### [NEW] `tests/Unit/Validations/FirstOrderValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when the customer has no previous orders` | `OrderContext::currentOrders = []` | No exception |
| 2 | `throws 'El código promocional aplica solo para la primera orden del cliente'` | `OrderContext::currentOrders = [<other order>]` (different `id` from current order) | `InvalidArgumentException` |

> [!NOTE]
> `FirstOrderValidator` relies on `$order->getOrderContext()`. In the unit test, mock `Order` to return a controlled `OrderContext` instance.

---

#### [NEW] `tests/Unit/Validations/GlobalUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when redemption count is below the limit` | Mock `PromocodeRedemption::where()->count()` returns `2`, `rules = ['global_usage_limit' => 5]` | No exception |
| 2 | `throws 'El código promocional ya ha superado el número máximo de canjes globales'` | Mock count returns `5`, limit is `5` | `InvalidArgumentException` |
| 3 | `throws 'El máximo global no ha sido definido para este cupón'` | `$promocode->rules = []` (no `global_usage_limit` key) | `InvalidArgumentException` |

---

#### [NEW] `tests/Unit/Validations/UserUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when there is no user usage limit defined` | `$promocode->rules = []` (no `user_usage_limit`) | No exception |
| 2 | `passes when customer redemptions are below the limit` | Mock count returns `1`, limit is `3` | No exception |
| 3 | `throws 'El usuario ha excedido el número máximo de usos permitidos para este código promocional'` | Mock count returns `3`, limit is `3` | `InvalidArgumentException` |

---

#### [NEW] `tests/Unit/Validations/GlobalAmountValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when total discounted plus current discount is below the limit` | Mock sum returns `50.0`, discount is `30.0`, limit is `100.0` | No exception |
| 2 | `passes when total discounted plus current discount equals the limit exactly` | Mock sum returns `70.0`, discount is `30.0`, limit is `100.0` | No exception |
| 3 | `throws 'El código promocional supera su presupuesto máximo de descuentos'` | Mock sum returns `80.0`, discount is `30.0`, limit is `100.0` | `InvalidArgumentException` |
| 4 | `throws 'El cupón no tiene configurado la cantidad límite global'` | `$promocode->rules = []` (no `global_amount_limit`) | `InvalidArgumentException` |

---

#### [NEW] `tests/Unit/Validations/MaxDiscountValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when discount is below the max` | discount = `40.0`, `rules = ['max_discount_amount' => 50.0]` | No exception |
| 2 | `passes when discount equals the max exactly` | discount = `50.0`, limit = `50.0` | No exception |
| 3 | `throws 'El monto a descontar sobrepasa el límite del cupón'` | discount = `60.0`, limit = `50.0` | `InvalidArgumentException` |
| 4 | `throws 'El monto máximo que se puede descontar no ha sido establecido para el código promocional'` | `$promocode->rules = []` | `InvalidArgumentException` |

---

#### [NEW] `tests/Unit/Validations/RestrictedUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when customer is in the allowed customers list` | Mock `$promocode->allowedCustomers()->where(...)->exists()` returns `true` | No exception |
| 2 | `throws 'El código promocional no ha sido asignado a este usuario'` | Mock returns `false` | `InvalidArgumentException` |

---

#### [NEW] `tests/Unit/Validations/ElegibleCategoriesValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when order contains a directly listed eligible category` | `categoriesId = [10]`, eligible = `[10]`, category has no parent/children | No exception |
| 2 | `passes when order contains a child of an eligible category` | `categoriesId = [20]` (child), eligible = `[10]` (parent), category 10 has child 20 | No exception |
| 3 | `passes when order contains the parent of an eligible category` | `categoriesId = [10]` (parent), eligible = `[20]` (child), category 20 has parent 10 | No exception |
| 4 | `throws 'La orden no contiene ninguna categoría elegible para este código promocional'` | `categoriesId = [99]`, eligible = `[10]`, no hierarchical overlap | `InvalidArgumentException` |

> [!NOTE]
> `ElegibleCategoriesValidator` calls `Category::with(...)->find()`. In unit tests, mock the `Category` model facade to return controlled `Category` instances with `parentCategory` and `childCategories` set as Eloquent collections.

---

### Step 4 — Feature Tests

Feature tests use real database state via factories and `RefreshDatabase` (already configured in `Pest.php`). The goal is to confirm the validators work against actual DB queries.

---

#### [NEW] `tests/Feature/Validations/MinPurchaseValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when order subtotal meets the minimum` | `Promocode::factory()->withMinPurchase(50)`, `Order::factory()` with services totalling ≥ 50 | No exception |
| 2 | `throws 'La orden no cumple con el subtotal mínimo necesario'` | min = `500`, services total only `100` | `InvalidArgumentException` |
| 3 | `throws 'El código promocional no tiene definido el mínimo'` | `rules = ['min_purchase_amount' => 0]` | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/FirstOrderValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when the customer has no previous orders` | New `Customer`, new `Order` (no other orders for this customer) | No exception |
| 2 | `throws 'El código promocional aplica solo para la primera orden del cliente'` | Customer has 2 orders in DB; current order is the 2nd (latest `id`) | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/GlobalUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when global redemptions are below the limit` | `withGlobalUsageLimit(5)`, create 3 `PromocodeRedemption` records for this promocode | No exception |
| 2 | `throws 'El código promocional ya ha superado el número máximo de canjes globales'` | Limit = `3`, create 3 redemptions | `InvalidArgumentException` |
| 3 | `throws 'El máximo global no ha sido definido para este cupón'` | `$promocode->rules = []` | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/UserUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when there is no user usage limit defined` | Promocode with no `user_usage_limit` in rules | No exception |
| 2 | `passes when customer redemptions are below the limit` | Limit = `3`, create 2 redemptions for same customer | No exception |
| 3 | `throws 'El usuario ha excedido el número máximo de usos permitidos para este código promocional'` | Limit = `2`, create 2 redemptions for same customer | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/GlobalAmountValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when cumulative discount is below the limit` | Limit = `100.0`, existing redemptions sum to `50.0`, current discount = `30.0` | No exception |
| 2 | `passes when cumulative discount equals the limit exactly` | Sum = `70.0`, current = `30.0`, limit = `100.0` | No exception |
| 3 | `throws 'El código promocional supera su presupuesto máximo de descuentos'` | Sum = `80.0`, current = `30.0`, limit = `100.0` | `InvalidArgumentException` |
| 4 | `throws 'El cupón no tiene configurado la cantidad límite global'` | No `global_amount_limit` rule | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/MaxDiscountValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when discount is below the max` | `withMaxDiscount(50.0)`, current discount = `40.0` | No exception |
| 2 | `throws 'El monto a descontar sobrepasa el límite del cupón'` | Max = `50.0`, current = `60.0` | `InvalidArgumentException` |
| 3 | `throws 'El monto máximo que se puede descontar no ha sido establecido para el código promocional'` | No `max_discount_amount` rule | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/RestrictedUsageValidatorTest.php`

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when customer is in the allowed customers list` | Attach customer to `$promocode->allowedCustomers()` pivot | No exception |
| 2 | `throws 'El código promocional no ha sido asignado a este usuario'` | Customer is not in the pivot table | `InvalidArgumentException` |

---

#### [NEW] `tests/Feature/Validations/ElegibleCategoriesValidatorTest.php`

Build category hierarchies using `CategoryFactory` with `withParent()` state. Attach services to orders via the `service_order` pivot.

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes when order contains a directly listed eligible category` | Order service → Category A; eligible = `[A.id]` | No exception |
| 2 | `passes when order contains a child of an eligible category` | Order service → Category B (child of A); eligible = `[A.id]` | No exception |
| 3 | `passes when order contains the parent of an eligible category` | Order service → Category A (parent of B); eligible = `[B.id]` | No exception |
| 4 | `throws 'La orden no contiene ninguna categoría elegible para este código promocional'` | Order service → Category C (unrelated); eligible = `[A.id]` | `InvalidArgumentException` |

---

### Step 5 — Integration Test (Full Chain)

#### [NEW] `tests/Feature/Validations/PromocodeValidationChainTest.php`

Tests the Chain of Responsibility with multiple validators chained via `setNext()`.

| # | Test Name | Setup | Expected |
|---|---|---|---|
| 1 | `passes complete validation chain when all conditions are met` | First-order customer, subtotal meets min, within global limits, correct category, within user limit, discount under max | No exception thrown through the entire chain |
| 2 | `throws immediately when one validator in the chain fails` | Chain: `MinPurchaseValidator → UserUsageValidator`. Subtotal fails but mock confirms `UserUsageValidator::handle` is **never called**. | `InvalidArgumentException` from `MinPurchaseValidator` |

---

## File Summary

| File | Action |
|---|---|
| [`CategoryFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/CategoryFactory.php) | **MODIFY** — fill `definition()` + add `withParent()` state |
| [`ServiceFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/ServiceFactory.php) | **MODIFY** — fill `definition()` with `name`, `price`, `category_id` |
| [`PromocodeRedemptionFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeRedemptionFactory.php) | **MODIFY** — fill `definition()` with all FKs + `discount_amount` |
| [`PromocodeFactory.php`](file:///c:/Users/arjsa/Herd/promora-marketplace/database/factories/PromocodeFactory.php) | **MODIFY** — add named states for each new validator's rules |
| `tests/Unit/Validations/MinPurchaseValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/FirstOrderValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/GlobalUsageValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/UserUsageValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/GlobalAmountValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/MaxDiscountValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/RestrictedUsageValidatorTest.php` | **NEW** |
| `tests/Unit/Validations/ElegibleCategoriesValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/MinPurchaseValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/FirstOrderValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/GlobalUsageValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/UserUsageValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/GlobalAmountValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/MaxDiscountValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/RestrictedUsageValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/ElegibleCategoriesValidatorTest.php` | **NEW** |
| `tests/Feature/Validations/PromocodeValidationChainTest.php` | **NEW** |

---

## Verification Plan

### Automated Tests

```bash
# Run full suite
php artisan test --compact

# Run only new unit validator tests
php artisan test --compact --filter=MinPurchaseValidatorTest
php artisan test --compact --filter=FirstOrderValidatorTest
php artisan test --compact --filter=GlobalUsageValidatorTest
php artisan test --compact --filter=UserUsageValidatorTest
php artisan test --compact --filter=GlobalAmountValidatorTest
php artisan test --compact --filter=MaxDiscountValidatorTest
php artisan test --compact --filter=RestrictedUsageValidatorTest
php artisan test --compact --filter=ElegibleCategoriesValidatorTest

# Run integration chain test
php artisan test --compact --filter=PromocodeValidationChainTest
```

All tests must pass with no failures. `RefreshDatabase` guarantees no test leaves state in the DB.
