<?php

namespace App\Discounts;

class TieredDiscount extends DiscountTemplate
{
    /**
     * Sobreescribe validate() para verificar subtotal > 0.
     * Si el subtotal es 0, no aplica descuento.
     * Luego delega a la validación base (global_amount_limit y max_discount_amount).
     */
    protected function validate(float $discount, float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        return parent::validate($discount, $subtotal);
    }

    /**
     * descuento = subtotal × (porcentaje_del_tramo / 100)
     *
     * Selecciona el tramo más alto cuyo min_orders no supere
     * el conteo de órdenes históricas del comprador (excluye canceladas,
     * en borrador y las órdenes actualmente en proceso).
     */
    protected function applyDiscount(): float
    {
        $context = $this->order->getOrderContext();

        $historicalOrderCount = $context->buyerProfile
            ->orders()
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->where('id', '!=', $this->order->id)
            ->count();

        $tier = $this->promocode
            ->tiers()
            ->where('minimum_orders', '<=', $historicalOrderCount)
            ->orderByDesc('minimum_orders')
            ->first();

        if ($tier === null) {
            return 0.0;
        }

        return $this->order->subtotal * ($tier->discount_value / 100);
    }
}
