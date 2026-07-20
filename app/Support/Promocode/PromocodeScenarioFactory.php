<?php

namespace App\Support\Promocode;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;

class PromocodeScenarioFactory
{
    /**
     * @return array<string, array{blocked: PromocodeScenario, allowed: PromocodeScenario}>
     */
    public function all(): array
    {
        return [
            'ExistenceValidator' => $this->existence(),
            'ValidityValidator' => $this->validity(),
            'StateValidator' => $this->state(),
            'ElegibleCategoriesValidator' => $this->elegibleCategories(),
            'MinPurchaseValidator' => $this->minPurchase(),
            'FirstOrderValidator' => $this->firstOrder(),
            'UserUsageValidator' => $this->userUsage(),
            'GlobalUsageValidator' => $this->globalUsage(),
            'RestrictedUsageValidator' => $this->restrictedUsage(),
            'GlobalAmountValidator' => $this->globalAmount(),
            'MaxDiscountValidator' => $this->maxDiscount(),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function existence(): array
    {
        $nonExistentId = (Promocode::max('id') ?? 0) + 9999;
        $blockedPromocode = new Promocode;
        $blockedPromocode->id = $nonExistentId;
        $blockedPromocode->rules = [];

        return [
            'blocked' => new PromocodeScenario(Order::factory()->create(), $blockedPromocode),
            'allowed' => new PromocodeScenario(Order::factory()->create(), Promocode::factory()->create()),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function validity(): array
    {
        return [
            'blocked' => new PromocodeScenario(Order::factory()->create(), Promocode::factory()->expired()->create()),
            'allowed' => new PromocodeScenario(Order::factory()->create(), Promocode::factory()->create()),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function state(): array
    {
        return [
            'blocked' => new PromocodeScenario(Order::factory()->create(), Promocode::factory()->paused()->create()),
            'allowed' => new PromocodeScenario(Order::factory()->create(), Promocode::factory()->create()),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function elegibleCategories(): array
    {
        $eligibleCategory = Category::factory()->create();
        $unrelatedCategory = Category::factory()->create();
        $promocode = Promocode::factory()->withEligibleCategories([$eligibleCategory->id])->create();

        $blockedOrder = Order::factory()->create();
        $blockedOrder->services()->attach(
            Service::factory()->create(['category_id' => $unrelatedCategory->id])->id,
            ['quantity' => 1]
        );

        $allowedOrder = Order::factory()->create();
        $allowedOrder->services()->attach(
            Service::factory()->create(['category_id' => $eligibleCategory->id])->id,
            ['quantity' => 1]
        );

        return [
            'blocked' => new PromocodeScenario($blockedOrder, $promocode),
            'allowed' => new PromocodeScenario($allowedOrder, $promocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function minPurchase(): array
    {
        $blockedPromocode = Promocode::factory()->withMinPurchase(500)->create();
        $blockedOrder = Order::factory()->create();
        $blockedOrder->services()->attach(
            Service::factory()->create(['price' => 10])->id,
            ['quantity' => 1]
        );

        $allowedPromocode = Promocode::factory()->withMinPurchase(50)->create();
        $allowedOrder = Order::factory()->create();
        $allowedOrder->services()->attach(
            Service::factory()->create(['price' => 100])->id,
            ['quantity' => 1]
        );

        return [
            'blocked' => new PromocodeScenario($blockedOrder, $blockedPromocode),
            'allowed' => new PromocodeScenario($allowedOrder, $allowedPromocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function firstOrder(): array
    {
        $promocode = Promocode::factory()->firstOrderOnly()->create();

        $blockedCustomer = Customer::factory()->create();
        Order::factory()->create(['customer_id' => $blockedCustomer->id, 'created_at' => now()->subDay()]);
        $blockedOrder = Order::factory()->create(['customer_id' => $blockedCustomer->id]);

        $allowedCustomer = Customer::factory()->create();
        $allowedOrder = Order::factory()->create(['customer_id' => $allowedCustomer->id]);

        return [
            'blocked' => new PromocodeScenario($blockedOrder, $promocode),
            'allowed' => new PromocodeScenario($allowedOrder, $promocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function userUsage(): array
    {
        $promocode = Promocode::factory()->withUserUsageLimit(1)->create();

        $blockedCustomer = Customer::factory()->create();
        $priorOrder = Order::factory()->create(['customer_id' => $blockedCustomer->id]);
        PromocodeRedemption::factory()->create([
            'promocode_id' => $promocode->id,
            'order_id' => $priorOrder->id,
        ]);
        $blockedOrder = Order::factory()->create(['customer_id' => $blockedCustomer->id]);

        $allowedCustomer = Customer::factory()->create();
        $allowedOrder = Order::factory()->create(['customer_id' => $allowedCustomer->id]);

        return [
            'blocked' => new PromocodeScenario($blockedOrder, $promocode),
            'allowed' => new PromocodeScenario($allowedOrder, $promocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function globalUsage(): array
    {
        $blockedPromocode = Promocode::factory()->withGlobalUsageLimit(1)->create();
        PromocodeRedemption::factory()->create(['promocode_id' => $blockedPromocode->id]);

        $allowedPromocode = Promocode::factory()->withGlobalUsageLimit(2)->create();
        PromocodeRedemption::factory()->create(['promocode_id' => $allowedPromocode->id]);

        return [
            'blocked' => new PromocodeScenario(Order::factory()->create(), $blockedPromocode),
            'allowed' => new PromocodeScenario(Order::factory()->create(), $allowedPromocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function restrictedUsage(): array
    {
        $promocode = Promocode::factory()->restrictedUsage()->create();

        $blockedCustomer = Customer::factory()->create();
        $blockedOrder = Order::factory()->create(['customer_id' => $blockedCustomer->id]);

        $allowedCustomer = Customer::factory()->create();
        $allowedOrder = Order::factory()->create(['customer_id' => $allowedCustomer->id]);
        $promocode->allowedCustomers()->attach($allowedCustomer->id);

        return [
            'blocked' => new PromocodeScenario($blockedOrder, $promocode),
            'allowed' => new PromocodeScenario($allowedOrder, $promocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function globalAmount(): array
    {
        $blockedPromocode = Promocode::factory()->withGlobalAmountLimit(50)->create();
        PromocodeRedemption::factory()->create(['promocode_id' => $blockedPromocode->id, 'discount_amount' => 100]);

        $allowedPromocode = Promocode::factory()->withGlobalAmountLimit(50)->fixed(5)->create();
        PromocodeRedemption::factory()->create(['promocode_id' => $allowedPromocode->id, 'discount_amount' => 10]);

        return [
            'blocked' => new PromocodeScenario($this->orderWithService(), $blockedPromocode),
            'allowed' => new PromocodeScenario($this->orderWithService(), $allowedPromocode),
        ];
    }

    /**
     * @return array{blocked: PromocodeScenario, allowed: PromocodeScenario}
     */
    public function maxDiscount(): array
    {
        $blockedPromocode = Promocode::factory()->state(fn (array $attributes) => [
            'rules' => array_merge($attributes['rules'] ?? [], ['max_discount_amount' => null]),
        ])->create();

        $allowedPromocode = Promocode::factory()->withMaxDiscount(20)->fixed(10)->create();

        return [
            'blocked' => new PromocodeScenario($this->orderWithService(), $blockedPromocode),
            'allowed' => new PromocodeScenario($this->orderWithService(), $allowedPromocode),
        ];
    }

    private function orderWithService(): Order
    {
        $order = Order::factory()->create();
        $order->services()->attach(Service::factory()->create(['price' => 100])->id, ['quantity' => 1]);

        return $order;
    }
}
