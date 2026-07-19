# Spec: Refactor PromocodeEngine to use OrderableInterface

## 1. Objective
Refactor the `PromocodeEngine` and its associated components (Validation Services, Validation Handlers, Price Calculators, and Discounts) to depend exclusively on the `App\Orderable\OrderableInterface` rather than the concrete `App\Models\Order` class. This allows the engine to apply promocodes to any "orderable" entity in the future.

## 2. Structural Requirements

### Interface & Model Updates
* **`App\Orderable\OrderableInterface`**: 
  * Add the method signature: `public function getId(): int;`
* **`App\Models\Order`**: 
  * Because Eloquent models do not have a native `getId()` method (they use the dynamic `$id` property), you must explicitly implement this method on the `Order` model to satisfy the interface.
  * Add: `public function getId(): int { return $this->id; }`

### Type Hint Replacements
Replace the concrete `Order $order` dependency with `OrderableInterface $order` across the following components:
* `App\PromocodeEngine\PromocodeEngine`
* `App\Services\PromocodeValidationService`
* `App\Services\PriceCalculatorService`
* `App\Factories\DiscountFactory`
* `App\Discounts\DiscountTemplate` (and all concrete children like `TieredDiscount`, `PercentageDiscount`, `FixedDiscount`)
* `App\Validations\PromocodeValidationHandler` (and all concrete children like `MinPurchaseValidator`, `UserUsageValidator`, etc.)

## 3. Property Access Refactoring
Direct property access on the `$order` object will fail once it is cast to `OrderableInterface`. You must refactor the internals of the validators and discounts as follows:

* **ID Access**: Replace all occurrences of `$order->id` with `$order->getId()`. Be sure to check logging strings (e.g., in Validators) and comparison logic (e.g., `FirstOrderValidator`).
* **Subtotal Access**: Replace direct `$this->order->subtotal` property reads with `$this->order->getSubtotal()` inside the discount classes.
* **Customer Access via OrderContext**: `OrderableInterface` explicitly defines `getOrderContext(): OrderContext`, which exposes the customer via the `$buyerProfile` property. Do not bloat the interface with customer getters.
  * In `UserUsageValidator`: Change `$order->customer_id` to `$order->getOrderContext()->buyerProfile->id`.
  * In `RestrictedUsageValidator`: Change `$order->customer` to `$order->getOrderContext()->buyerProfile`.

## 4. Testing & Workflow Context (TDD)
* This project is strictly Test-Driven (TDD).
* Evaluate test files only if they directly mock, type-hint, or assert against the `Order` class in the context of the promocode engine. Update the typing or method usage (`$order->id` to `$order->getId()`) to align with `OrderableInterface`. Do **not** alter the core logic of the tests. 
* Do not apply formatting changes or unrelated modifications to files unless it strictly pertains to the `OrderableInterface` refactor.
* **Execution Workflow**: The local testing environment is currently unconfigured for the agent. Once the refactor is completed, the agent must stop and wait for the human developer to manually run the test suite and confirm that all tests pass.
