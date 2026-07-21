<?php

namespace App\Console\Commands;

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;
use App\Models\Tier;
use App\PromocodeEngine\PromocodeEngine;
use App\Services\PriceCalculatorService;
use App\Support\Promocode\PromocodeRuleInspector;
use App\Support\Promocode\PromocodeScenarioFactory;
use Illuminate\Console\Command;
use InvalidArgumentException;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class PromocodePlayCommand extends Command
{
    protected $signature = 'promocode:play
        {--demo : Corre el recorrido automático por los 11 validators (bloqueado + permitido)}
        {--no-pause : No pausar entre escenarios en modo --demo}';

    protected $description = 'Prueba en vivo el motor de códigos promocionales: arma un escenario interactivo o corre el recorrido automático por las 11 reglas de validación';

    public function __construct(
        private PriceCalculatorService $priceCalculatorService,
        private PromocodeRuleInspector $ruleInspector,
    ) {
        parent::__construct();
    }

    public function handle(PromocodeEngine $engine, PromocodeScenarioFactory $scenarioFactory): int
    {
        if ($this->option('demo')) {
            return $this->runDemo($engine, $scenarioFactory);
        }

        return $this->runInteractive($engine);
    }

    private function runDemo(PromocodeEngine $engine, PromocodeScenarioFactory $scenarioFactory): int
    {
        $this->info('=== DEMO — 11 validators x 2 corridas (bloqueado + permitido) ===');

        $rows = [];
        $allMatched = true;

        foreach ($scenarioFactory->all() as $validatorName => $scenario) {
            foreach (['blocked' => false, 'allowed' => true] as $case => $expectedValid) {
                $target = $scenario[$case];
                $startIndex = count(Logger::getInstance()->getLogs());

                $actuallyValid = true;
                $failureMessage = null;

                try {
                    $engine->validateCode($target->order, $target->promocode);
                } catch (InvalidArgumentException $e) {
                    $actuallyValid = false;
                    $failureMessage = $e->getMessage();
                }

                $matched = $actuallyValid === $expectedValid;
                $allMatched = $allMatched && $matched;

                $label = $case === 'blocked' ? 'BLOQUEADO (esperado)' : 'PERMITIDO (esperado)';
                $this->line("--- {$validatorName} | {$label} ---");
                $this->line($actuallyValid ? 'Resultado: VÁLIDO' : "Resultado: BLOQUEADO — {$failureMessage}");

                foreach (array_slice(Logger::getInstance()->getLogs(), $startIndex) as $log) {
                    $this->line("    {$log}");
                }

                $this->printBreakdown($target->order, $target->promocode);

                $rows[] = [$validatorName, $case, $matched ? 'OK' : 'MISMATCH'];

                if (! $this->option('no-pause')) {
                    pause('Presiona ENTER para continuar…');
                }
            }
        }

        $this->newLine();
        $this->table(['Validator', 'Caso', 'Resultado'], $rows);

        return $allMatched ? self::SUCCESS : self::FAILURE;
    }

    private function runInteractive(PromocodeEngine $engine): int
    {
        $this->info('=== Promocode Engine — Playground interactivo ===');

        do {
            [$order, $promocode] = $this->buildScenario();

            $startIndex = count(Logger::getInstance()->getLogs());

            try {
                $engine->validateCode($order, $promocode);
                $this->info("VÁLIDO — promocode #{$promocode->id} aceptado para orden #{$order->id}");
            } catch (InvalidArgumentException $e) {
                $this->error("BLOQUEADO — {$e->getMessage()}");
            }

            $this->line('--- Logs de esta corrida ---');
            foreach (array_slice(Logger::getInstance()->getLogs(), $startIndex) as $log) {
                $this->line($log);
            }

            $this->printBreakdown($order, $promocode);
        } while (confirm('¿Correr otro escenario?', default: false));

        return self::SUCCESS;
    }

    /**
     * Imprime subtotal, descuento, precio final y valor real vs. threshold de cada regla numérica activa.
     * Se calcula el precio de forma independiente al motor (que solo devuelve bool), así que si la orden
     * bloqueó en Fase 1 esto muestra el descuento/precio hipotético — marcado como tal — a propósito.
     */
    private function printBreakdown(Order $order, Promocode $promocode): void
    {
        $subtotal = $order->getSubtotal();

        $this->line('--- Desglose de precio ---');
        $this->line("Subtotal: {$subtotal}");
        $this->line("Tipo de código: {$promocode->type} | valor configurado: {$promocode->value}");

        $discountAmount = null;

        try {
            $finalPrice = $this->priceCalculatorService->calculatePrice($order, $promocode);
            $discountAmount = round($subtotal - $finalPrice, 2);
            $this->line("Descuento aplicado (hipotético si la orden bloqueó): {$discountAmount}");
            $this->line("Precio final (hipotético si la orden bloqueó): {$finalPrice}");
        } catch (InvalidArgumentException $e) {
            $this->line("No se pudo calcular el precio final — {$e->getMessage()}");
        }

        if ($promocode->type === 'tiered') {
            $historicalOrders = $this->ruleInspector->historicalOrderCount($order->customer, $order->getId());
            $tier = $this->ruleInspector->matchedTier($promocode, $historicalOrders);

            $this->line("Órdenes históricas del cliente (no canceladas/borrador): {$historicalOrders}");
            $this->line($tier
                ? "Tramo aplicado: minimum_orders={$tier->minimum_orders} | discount_value={$tier->discount_value}%"
                : 'Tramo aplicado: ninguno (0% de descuento)');
        }

        $rules = $promocode->rules ?? [];

        if (isset($rules['min_purchase_amount'])) {
            $this->line("min_purchase_amount — subtotal real: {$subtotal} | umbral configurado: {$rules['min_purchase_amount']}");
        }

        if (isset($rules['user_usage_limit'])) {
            $count = $this->ruleInspector->userUsageCount($promocode, $order->customer);
            $this->line("user_usage_limit — usos previos de este cliente: {$count} | límite configurado: {$rules['user_usage_limit']}");
        }

        if (isset($rules['global_usage_limit'])) {
            $count = $this->ruleInspector->globalUsageCount($promocode);
            $this->line("global_usage_limit — usos globales previos: {$count} | límite configurado: {$rules['global_usage_limit']}");
        }

        if (isset($rules['global_amount_limit'])) {
            $redeemed = $this->ruleInspector->globalAmountRedeemed($promocode);
            $line = "global_amount_limit — redimido previo: {$redeemed}";
            if ($discountAmount !== null) {
                $line .= ' | previo + esta orden: '.($redeemed + $discountAmount);
            }
            $line .= " | límite configurado: {$rules['global_amount_limit']}";
            $this->line($line);
        }

        if (isset($rules['max_discount_amount'])) {
            $shown = $discountAmount ?? 'N/D';
            $this->line("max_discount_amount — descuento calculado (ya con cap aplicado si correspondía): {$shown} | máximo configurado: {$rules['max_discount_amount']}");
        }
    }

    /**
     * @return list<string>
     */
    private function chooseRules(): array
    {
        $ruleOptions = [
            'min_purchase_amount' => 'Monto mínimo de compra',
            'elegible_categories' => 'Categorías elegibles',
            'first_order_only' => 'Solo primera orden',
            'user_usage_limit' => 'Límite de uso por usuario',
            'global_usage_limit' => 'Límite de uso global',
            'global_amount_limit' => 'Límite de monto global',
            'restricted_usage' => 'Uso restringido a clientes específicos',
            'max_discount_amount' => 'Descuento máximo',
        ];

        $selected = [];

        while (true) {
            $menu = [];
            foreach ($ruleOptions as $key => $label) {
                $mark = in_array($key, $selected, true) ? '[x]' : '[ ]';
                $menu[$key] = "{$mark} {$label}";
            }
            $menu['__continue'] = '>> Aceptar y continuar con las reglas seleccionadas';

            $choice = select('Reglas configurables a activar (elige una para activarla/desactivarla)', $menu);

            if ($choice === '__continue') {
                break;
            }

            $selected = in_array($choice, $selected, true)
                ? array_values(array_diff($selected, [$choice]))
                : [...$selected, $choice];
        }

        return $selected;
    }

    /**
     * @return array{0: list<array{0: Service, 1: int}>, 1: list<Category>}
     */
    private function buildServicesAndCategories(): array
    {
        $serviceCount = max(1, (int) text('¿Cuántos servicios tendrá esta orden?', default: '1'));

        $categoriesCreated = [];
        $servicesToAttach = [];

        for ($i = 1; $i <= $serviceCount; $i++) {
            $price = (float) text("Precio del servicio #{$i}", default: '50');
            $quantity = max(1, (int) text("Cantidad del servicio #{$i}", default: '1'));
            $category = $this->chooseOrCreateCategory($categoriesCreated, $i);

            if (! in_array($category->id, array_map(fn (Category $c) => $c->id, $categoriesCreated), true)) {
                $categoriesCreated[] = $category;
            }

            $service = Service::factory()->create(['price' => $price, 'category_id' => $category->id]);
            $servicesToAttach[] = [$service, $quantity];
        }

        return [$servicesToAttach, $categoriesCreated];
    }

    /**
     * @param  list<Category>  $categoriesCreated
     */
    private function chooseOrCreateCategory(array $categoriesCreated, int $serviceIndex): Category
    {
        if ($categoriesCreated === []) {
            return Category::factory()->create();
        }

        $menu = ['__new' => 'Categoría nueva sin relación'];
        foreach ($categoriesCreated as $category) {
            $menu["reuse:{$category->id}"] = "Reusar categoría #{$category->id} ({$category->name})";
        }
        $menu['__child'] = 'Categoría nueva, hija de una ya creada';
        $menu['__parent'] = 'Categoría nueva, padre de una ya creada (reparenta la existente)';

        $choice = select("Categoría del servicio #{$serviceIndex}", $menu);

        if ($choice === '__new') {
            return Category::factory()->create();
        }

        if (str_starts_with($choice, 'reuse:')) {
            $id = (int) substr($choice, strlen('reuse:'));

            return $this->findCategory($categoriesCreated, $id);
        }

        if ($choice === '__child') {
            $parent = $this->pickCategory($categoriesCreated, 'Categoría padre');

            return Category::factory()->withParent($parent)->create();
        }

        // __parent: la nueva categoría pasa a ser el padre de una ya existente.
        $existing = $this->pickCategory($categoriesCreated, 'Categoría que pasará a ser hija');
        $newParent = Category::factory()->create();
        $existing->category_id = $newParent->id;
        $existing->save();

        return $newParent;
    }

    /**
     * @param  list<Category>  $categories
     */
    private function pickCategory(array $categories, string $label): Category
    {
        $menu = [];
        foreach ($categories as $category) {
            $menu[(string) $category->id] = "#{$category->id} ({$category->name})";
        }

        return $this->findCategory($categories, (int) select($label, $menu));
    }

    /**
     * @param  list<Category>  $categories
     */
    private function findCategory(array $categories, int $id): Category
    {
        foreach ($categories as $category) {
            if ($category->id === $id) {
                return $category;
            }
        }

        throw new InvalidArgumentException("Categoría #{$id} no encontrada entre las creadas en este escenario");
    }

    /**
     * @param  list<Category>  $categories
     * @return list<int>
     */
    private function chooseEligibleCategories(array $categories): array
    {
        $selected = [];

        while (true) {
            $menu = [];
            foreach ($categories as $category) {
                $mark = in_array($category->id, $selected, true) ? '[x]' : '[ ]';
                $menu[(string) $category->id] = "{$mark} {$category->name} (#{$category->id})";
            }
            $menu['__continue'] = '>> Aceptar y continuar con las categorías seleccionadas';

            $choice = select('Categorías elegibles para este código (elige una para activarla/desactivarla)', $menu);

            if ($choice === '__continue') {
                break;
            }

            $categoryId = (int) $choice;
            $selected = in_array($categoryId, $selected, true)
                ? array_values(array_diff($selected, [$categoryId]))
                : [...$selected, $categoryId];
        }

        return $selected;
    }

    /**
     * @return array{0: Order, 1: Promocode}
     */
    private function buildScenario(): array
    {
        $type = select('Tipo de código', ['fixed', 'percent', 'tiered'], default: 'fixed');

        $ruleKeys = $this->chooseRules();

        $stateOverride = null;
        if (confirm('¿Simular un estado no estándar (pausado / caducado / aún no vigente)?', default: false)) {
            $stateOverride = select('Estado a simular', ['paused', 'expired', 'notYetActive']);
        }

        $customer = Customer::factory()->create();
        [$servicesToAttach, $categoriesCreated] = $this->buildServicesAndCategories();

        $rules = ['validity' => true, 'state' => true];

        if (in_array('min_purchase_amount', $ruleKeys, true)) {
            $rules['min_purchase_amount'] = (float) text('Monto mínimo de compra', default: '50');
        }

        if (in_array('elegible_categories', $ruleKeys, true)) {
            $rules['elegible_categories'] = $this->chooseEligibleCategories($categoriesCreated);
        }

        if (in_array('first_order_only', $ruleKeys, true)) {
            $rules['first_order_only'] = true;
        }

        if (in_array('user_usage_limit', $ruleKeys, true)) {
            $rules['user_usage_limit'] = (int) text('Límite de uso por usuario', default: '2');
        }

        if (in_array('global_usage_limit', $ruleKeys, true)) {
            $rules['global_usage_limit'] = (int) text('Límite de uso global', default: '5');
        }

        if (in_array('global_amount_limit', $ruleKeys, true)) {
            $rules['global_amount_limit'] = (float) text('Límite de monto global', default: '100');
        }

        if (in_array('restricted_usage', $ruleKeys, true)) {
            $rules['restricted_usage'] = true;
        }

        if (in_array('max_discount_amount', $ruleKeys, true)) {
            $rules['max_discount_amount'] = (float) text('Descuento máximo', default: '20');
        }

        $tierDefs = [];
        $tieredHistoricalOrders = 0;

        if ($type === 'tiered') {
            $tierCount = max(1, (int) text('¿Cuántos tramos (tiers) tendrá este código?', default: '2'));

            for ($i = 1; $i <= $tierCount; $i++) {
                $tierDefs[] = [
                    'minimum_orders' => (int) text("Tramo #{$i} — mínimo de órdenes históricas", default: (string) (($i - 1) * 3)),
                    'discount_value' => (float) text("Tramo #{$i} — porcentaje de descuento", default: '10'),
                ];
            }

            $tieredHistoricalOrders = (int) text('Órdenes históricas del cliente (no canceladas/borrador) a simular para este escenario', default: '0');
        }

        $promocodeFactory = Promocode::factory()->state(['type' => $type, 'rules' => $rules]);

        $promocodeFactory = match ($stateOverride) {
            'paused' => $promocodeFactory->paused(),
            'expired' => $promocodeFactory->expired(),
            'notYetActive' => $promocodeFactory->notYetActive(),
            default => $promocodeFactory,
        };

        $promocode = $promocodeFactory->create();

        if (in_array('restricted_usage', $ruleKeys, true)
            && confirm('¿Asignar el código al cliente actual? (No = forzar bloqueo)', default: true)) {
            $promocode->allowedCustomers()->attach($customer->id);
        }

        if ($type === 'tiered') {
            foreach ($tierDefs as $tierDef) {
                Tier::factory()->create([
                    'promocode_id' => $promocode->id,
                    'minimum_orders' => $tierDef['minimum_orders'],
                    'discount_value' => $tierDef['discount_value'],
                ]);
            }
        }

        if (in_array('first_order_only', $ruleKeys, true) || in_array('user_usage_limit', $ruleKeys, true)) {
            $priorOrders = (int) text('Órdenes previas del cliente a simular', default: '0');
            for ($i = 0; $i < $priorOrders; $i++) {
                $prevOrder = Order::factory()->create(['customer_id' => $customer->id]);
                if (in_array('user_usage_limit', $ruleKeys, true)
                    && confirm('¿La orden previa #'.($i + 1).' ya redimió este código?', default: false)) {
                    PromocodeRedemption::factory()->create([
                        'promocode_id' => $promocode->id,
                        'order_id' => $prevOrder->id,
                    ]);
                }
            }
        }

        if (in_array('global_usage_limit', $ruleKeys, true) || in_array('global_amount_limit', $ruleKeys, true)) {
            $priorRedemptions = (int) text('Redenciones globales previas a simular', default: '0');
            for ($i = 0; $i < $priorRedemptions; $i++) {
                PromocodeRedemption::factory()->create([
                    'promocode_id' => $promocode->id,
                    'discount_amount' => (float) text('Monto de la redención previa #'.($i + 1), default: '10'),
                ]);
            }
        }

        if ($type === 'tiered' && $tieredHistoricalOrders > 0) {
            for ($i = 0; $i < $tieredHistoricalOrders; $i++) {
                Order::factory()->create(['customer_id' => $customer->id, 'status' => 'pending']);
            }
        }

        $order = Order::factory()->create(['customer_id' => $customer->id]);
        foreach ($servicesToAttach as [$service, $quantity]) {
            $order->services()->attach($service->id, ['quantity' => $quantity]);
        }

        return [$order, $promocode];
    }
}
