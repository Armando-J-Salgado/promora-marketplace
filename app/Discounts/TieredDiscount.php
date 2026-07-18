<?php

namespace App\Discounts;

class TieredDiscount extends DiscountTemplate
{
    /**
     * obreescribe validate() para solo verificar subtotal > 0,
     * ya que el tipo tiered no usa $promocode->value
     * eso pertenece a la lógica de tramos.
     */
    protected function validate(): bool
    {
        return $this->order->subtotal > 0;
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
