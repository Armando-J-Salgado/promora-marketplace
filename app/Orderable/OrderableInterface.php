<?php

namespace App\Orderable;

interface OrderableInterface
{
    public function getId(): ?int;

    public function getSubtotal(): float;

    public function getOrderContext(): OrderContext;
}
