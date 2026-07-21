<?php

namespace App\Support\Promocode;

use App\Models\Customer;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Tier;

class PromocodeRuleInspector
{
    public function userUsageCount(Promocode $promocode, Customer $customer): int
    {
        return PromocodeRedemption::where('promocode_id', $promocode->id)
            ->whereHas('order', fn ($query) => $query->where('customer_id', $customer->id))
            ->count();
    }

    public function globalUsageCount(Promocode $promocode): int
    {
        return PromocodeRedemption::where('promocode_id', $promocode->id)->count();
    }

    public function globalAmountRedeemed(Promocode $promocode): float
    {
        return (float) PromocodeRedemption::where('promocode_id', $promocode->id)->sum('discount_amount');
    }

    public function historicalOrderCount(Customer $customer, ?int $excludeOrderId): int
    {
        return $customer->orders()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->when($excludeOrderId !== null, fn ($query) => $query->where('id', '!=', $excludeOrderId))
            ->count();
    }

    public function matchedTier(Promocode $promocode, int $historicalOrderCount): ?Tier
    {
        return $promocode->tiers()
            ->where('minimum_orders', '<=', $historicalOrderCount)
            ->orderByDesc('minimum_orders')
            ->first();
    }
}
