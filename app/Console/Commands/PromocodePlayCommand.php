<?php

namespace App\Console\Commands;

use App\Logger\Logger;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Promocode;
use App\Models\PromocodeRedemption;
use App\Models\Service;
use App\Services\PromocodeValidationService;
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

    public function handle(PromocodeValidationService $validationService, PromocodeScenarioFactory $scenarioFactory): int
    {
        if ($this->option('demo')) {
            return $this->runDemo($validationService, $scenarioFactory);
        }

        return $this->runInteractive($validationService);
    }

    private function runDemo(PromocodeValidationService $validationService, PromocodeScenarioFactory $scenarioFactory): int
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
                    $validationService->validate($target->order, $target->promocode);
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

    private function runInteractive(PromocodeValidationService $validationService): int
    {
        $this->info('=== Promocode Engine — Playground interactivo ===');

        do {
            [$order, $promocode] = $this->buildScenario();

            $startIndex = count(Logger::getInstance()->getLogs());

            try {
                $validationService->validate($order, $promocode);
                $this->info("VÁLIDO — promocode #{$promocode->id} aceptado para orden #{$order->id}");
            } catch (InvalidArgumentException $e) {
                $this->error("BLOQUEADO — {$e->getMessage()}");
            }

            $this->line('--- Logs de esta corrida ---');
            foreach (array_slice(Logger::getInstance()->getLogs(), $startIndex) as $log) {
                $this->line($log);
            }
        } while (confirm('¿Correr otro escenario?', default: false));

        return self::SUCCESS;
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
        $rules = ['validity' => true, 'state' => true];
        $eligibleCategoryId = null;

        if (in_array('min_purchase_amount', $ruleKeys, true)) {
            $rules['min_purchase_amount'] = (float) text('Monto mínimo de compra', default: '50');
        }

        if (in_array('elegible_categories', $ruleKeys, true)) {
            $eligibleCategoryId = Category::factory()->create()->id;
            $rules['elegible_categories'] = [$eligibleCategoryId];
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

        $order = Order::factory()->create(['customer_id' => $customer->id]);
        $serviceCategoryId = $eligibleCategoryId ?? Category::factory()->create()->id;
        $service = Service::factory()->create(['category_id' => $serviceCategoryId]);
        $order->services()->attach($service->id, ['quantity' => 1]);

        return [$order, $promocode];
    }
}
