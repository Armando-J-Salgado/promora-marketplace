<?php

namespace App\Validations;

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Order;
use App\Models\Promocode;
use InvalidArgumentException;

class ElegibleCategoriesValidator extends PromocodeValidationHandler
{
    public function handle(Order $order, Promocode $promocode): void
    {
        $categories = $order->getOrderContext()->categoriesId;

        $categoriesSet = array_fill_keys($categories, true);

        $eligibleCategories = $promocode->rules['elegible_categories'] ?? [];

        $found = false;
        foreach ($eligibleCategories as $catId) {
            $category = Category::with(['parentCategory', 'childCategories'])->find($catId);
            if (! $category) {
                continue;
            }

            // Se asume solo puede tener 1 padre
            $idsToCheck = [$category->id];
            if ($category->parentCategory) {
                $idsToCheck[] = $category->parentCategory->id;
            }
            foreach ($category->childCategories as $child) {
                $idsToCheck[] = $child->id;
            }

            // Buscar coincidencia en O(1)
            foreach ($idsToCheck as $id) {
                if (isset($categoriesSet[$id])) {
                    $found = true;
                    break 2; // salir de ambos foreach
                }
            }
        }

        if (! $found) {
            Logger::getInstance()->log("[FAIL] ElegibleCategoriesValidator | code=invalid_code | promocode=#{$promocode->id} | order=#{$order->id} | La orden no contiene ninguna categoría elegible para este código promocional");
            throw new InvalidArgumentException(
                'La orden no contiene ninguna categoría elegible para este código promocional'
            );
        }

        Logger::getInstance()->log("[PASS] ElegibleCategoriesValidator | promocode=#{$promocode->id} | order=#{$order->id} | regla superada");
        parent::handle($order, $promocode);
    }
}
