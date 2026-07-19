# Implementation Plan: Refactor PromocodeEngine to OrderableInterface

## Overview

This plan guides the implementation of the refactor described in `specs/orderable_refactor_spec.md`. The goal is to decouple the `PromocodeEngine` and all its dependents from the concrete `App\Models\Order` class so they program against the `App\Orderable\OrderableInterface` contract instead.

The work is ordered from the deepest dependency upward (interface → model → abstract classes → concrete classes → services → engine → tests) to ensure each layer compiles before the layers that depend on it are changed.

---

## Step 1 — Extend the Interface

**File:** `app/Orderable/OrderableInterface.php`

Add `getId()` to the existing two method signatures:

```php
public function getId(): int;
public function getSubtotal(): float;
public function getOrderContext(): OrderContext;
```

No other changes to this file.

---

## Step 2 — Implement `getId()` on the Order Model

**File:** `app/Models/Order.php`

Eloquent models expose `$id` as a dynamic property, not a method. Because `Order` implements `OrderableInterface`, it must now satisfy the new contract. Add the following method to the class body:

```php
public function getId(): int
{
    return $this->id;
}
```

Do not change any other part of `Order`. The existing `getSubtotal()` and `getOrderContext()` methods already satisfy the interface.

---

## Step 3 — Update the Abstract Validation Handler

**File:** `app/Validations/PromocodeValidationHandler.php`

- Change the `use App\Models\Order;` import to `use App\Orderable\OrderableInterface;`.
- Change the `handle()` signature from `Order $order` to `OrderableInterface $order` in both the method declaration and the recursive `$this->next->handle(...)` call.

This change cascades automatically to all child validators because they all override `handle()`.

---

## Step 4 — Update Every Concrete Validator

For each file listed below, apply **both** of the following changes unless noted otherwise:

1. Replace `use App\Models\Order;` with `use App\Orderable\OrderableInterface;`.
2. Change the `handle(Order $order, ...)` signature to `handle(OrderableInterface $order, ...)`.
3. Apply any additional internal changes described per file.

### `app/Validations/ExistenceValidator.php`
Type hint change only (steps 1 & 2). No internal property access to fix.

### `app/Validations/StateValidator.php`
Type hint change only (steps 1 & 2).

### `app/Validations/ValidityValidator.php`
Type hint change only (steps 1 & 2).

### `app/Validations/GlobalUsageValidator.php`
Type hint change only (steps 1 & 2).

### `app/Validations/MaxDiscountValidator.php`
Type hint change only (steps 1 & 2).

### `app/Validations/GlobalAmountValidator.php`
Type hint change only (steps 1 & 2).

### `app/Validations/MinPurchaseValidator.php`
Steps 1 & 2. The internal `$order->getSubtotal()` call (line 20) is already using the method from the interface — no further change needed there.

### `app/Validations/ElegibleCategoriesValidator.php`
Steps 1 & 2. The existing `$order->getOrderContext()->categoriesId` access (line 15) already uses the interface contract — no further change needed there.

### `app/Validations/FirstOrderValidator.php`
Steps 1 & 2. Additionally:
- Line 26: Change `$order->id === $firstOrder->id` to `$order->getId() === $firstOrder->id`.
  - `$firstOrder` is a plain `Order` object from `OrderContext::$currentOrders` (an array of Eloquent models), so `$firstOrder->id` is fine to keep as is.

### `app/Validations/UserUsageValidator.php`
Steps 1 & 2. Additionally:
- Line 27: Change `$order->customer_id` to `$order->getOrderContext()->buyerProfile->id`.

### `app/Validations/RestrictedUsageValidator.php`
Steps 1 & 2. Additionally:
- Line 14: Change `$order->customer` to `$order->getOrderContext()->buyerProfile`.

---

## Step 5 — Update the Abstract Discount Template

**File:** `app/Discounts/DiscountTemplate.php`

- Remove `use App\Models\Order;`, add `use App\Orderable\OrderableInterface;`.
- Change the `protected Order $order;` property declaration to `protected OrderableInterface $order;`.
- Change the constructor signature from `Order $order` to `OrderableInterface $order`.
- Lines 26 & 32: Change `$this->order->subtotal` to `$this->order->getSubtotal()`.
  - Line 24 calls `$this->order->getSubtotal()` (already a method call) — leave it unchanged.

The internal `validate()` method calls `$validator->handle($this->order, ...)` — once the handler signature accepts `OrderableInterface`, this passes naturally since `$this->order` now is typed as `OrderableInterface`.

---

## Step 6 — Update Concrete Discount Classes

Each concrete discount class extends `DiscountTemplate`. Their constructors are inherited — only internal `applyDiscount()` logic needs to change.

### `app/Discounts/FixedDiscount.php`
- Line 12: Change `$this->order->subtotal` to `$this->order->getSubtotal()`.

### `app/Discounts/PercentageDiscount.php`
- Line 12: Change `$this->order->subtotal` to `$this->order->getSubtotal()`.

### `app/Discounts/TieredDiscount.php`
- Line 22: Change `$this->order->id` to `$this->order->getId()`.
- Line 35: Change `$this->order->subtotal` to `$this->order->getSubtotal()`.
- The existing `$this->order->getOrderContext()` call on line 17 is already correct — no change needed.

### `app/Discounts/DefaultDiscount.php`
Check if it accesses any direct properties. If it only delegates to `applyDiscount()` and returns `0.0`, no internal changes are required beyond what is inherited.

---

## Step 7 — Update the Discount Factory

**File:** `app/Factories/DiscountFactory.php`

- Remove `use App\Models\Order;`, add `use App\Orderable\OrderableInterface;`.
- Change the `make(Promocode $promocode, Order $order)` signature to `make(Promocode $promocode, OrderableInterface $order)`.

The internal `match` body passes `$order` to the concrete discount constructors — those now accept `OrderableInterface` (from Step 5), so no further change is needed.

---

## Step 8 — Update the Validation Service

**File:** `app/Services/PromocodeValidationService.php`

- Remove `use App\Models\Order;`, add `use App\Orderable\OrderableInterface;`.
- Change `validate(Order $order, ...)` to `validate(OrderableInterface $order, ...)`.

The call to `$firstHandler->handle($order, $promocode)` on line 32 passes naturally — `$order` now satisfies `OrderableInterface`.

---

## Step 9 — Update the Price Calculator Service

**File:** `app/Services/PriceCalculatorService.php`

- Remove `use App\Models\Order;`, add `use App\Orderable\OrderableInterface;`.
- Change `calculatePrice(Order $order, ...)` to `calculatePrice(OrderableInterface $order, ...)`.

The internal `$order->getSubtotal()` call on line 13 is already using the interface method — no further change needed. The `DiscountFactory::make(...)` call on line 14 also passes naturally after Step 7.

---

## Step 10 — Update the PromocodeEngine

**File:** `app/PromocodeEngine/PromocodeEngine.php`

- Remove `use App\Models\Order;`, add `use App\Orderable\OrderableInterface;`.
- Change `validateCode(Order $order, ...)` to `validateCode(OrderableInterface $order, ...)`.
- Line 24: Change `$order->id` to `$order->getId()` in the log string.
- Line 30: Change `$order->id` to `$order->getId()` in the log string.

---

## Step 11 — Evaluate & Update Tests

> **Rule:** Only update tests that directly break due to a type-hint mismatch or a method call that no longer exists. Do not change test logic, assertions, or expectations.

### Tests using `Order::factory()->create()` or `new Order`
These tests pass a real `Order` instance to the engine and its components. Because `Order` now implements `OrderableInterface` (with `getId()` added in Step 2), these tests **will continue to work without any changes**. The `use App\Models\Order;` import can stay — the tests still construct real `Order` objects to pass to the engine.

### Tests using `Mockery::mock(Order::class)->makePartial()`

These mocks require attention because the SUT now calls `$order->getId()`, which the mock must respond to.

#### `tests/Unit/Validations/MinPurchaseValidatorTest.php`
The `handle()` method calls `$order->getId()` only in log strings. The mock is set up with `makePartial()`, but since `Order::getId()` is a real concrete method added in Step 2, Mockery's partial mock will delegate to it automatically when `$order->id` is not explicitly set (returning `0`). However, to keep assertions explicit and deterministic, **add** `$order->shouldReceive('getId')->andReturn($order->id)` immediately after each `Mockery::mock(...)` line (all 3 test cases). Do not change any assertion or expectation logic.

#### `tests/Unit/Validations/FirstOrderValidatorTest.php`
- **Test 1** ("passes when the customer has no previous orders"): The mock calls `$order->getId()` via log string. Add `$order->shouldReceive('getId')->andReturn(0)` after line 17. Do not change assertions.
- **Test 2** ("throws exception..."): The mock has `$order->id = 2` set directly. The `FirstOrderValidator` now calls `$order->getId()` for comparison. Add `$order->shouldReceive('getId')->andReturn(2)` after line 32. The `$previousOrder->id` access remains unchanged — `$previousOrder` is a plain `new Order` with `->id = 1` set directly, which is fine.

#### `tests/Unit/Discount/FixedDiscountTest.php`
The mock already stubs `shouldReceive('getSubtotal')`. After Step 6 refactors `FixedDiscount::applyDiscount()` to use `$this->order->getSubtotal()`, the `->subtotal = x` direct assignments on the mock (lines 17, 28, 39, 51) are no longer needed by the SUT code. **Remove** the `$this->order->subtotal = x` lines from all four test cases and rely solely on the `shouldReceive('getSubtotal')->andReturn(x)` stubs that already exist. Do not change any other logic or assertions.

### All other test files
All remaining test files (`DiscountTemplateTest`, `PercentageDiscountTest`, `DefaultDiscountTest`, `DiscountFactoryTest`, `PromocodeEngineTest`, `PromocodeValidationServiceTest`, and all Feature tests) pass real `Order::factory()->create()` instances. Because `Order` now satisfies `OrderableInterface`, these **require no changes**.

---

## Verification Workflow

> ⚠️ The local test environment is not configured for the agent to run tests.

Once all the above changes are complete, the agent must **stop and wait** for the developer to run the full test suite manually:

```bash
php artisan test --compact
```

The developer will confirm whether all tests pass before the plan is considered complete. Do not apply formatting or other unrelated changes while waiting.
