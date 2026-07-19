<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Promocode;
use Illuminate\Database\Seeder;

class PromocodeSeeder extends Seeder
{
    public function run(): void
    {
        $webCategory = Category::where('name', 'Desarrollo Web')->first();
        $juan = Customer::where('email', 'juan@example.com')->first();

        Promocode::factory()->percent(15.0)->create([
            'rules' => ['validity' => true, 'state' => true],
        ]);

        Promocode::factory()->fixed(25.0)->create([
            'rules' => ['validity' => true, 'state' => true],
        ]);

        Promocode::factory()->percent(20.0)->withMinPurchase(100.0)->create();

        Promocode::factory()->fixed(50.0)
            ->withEligibleCategories([$webCategory->id])
            ->create();

        Promocode::factory()->percent(10.0)
            ->withGlobalUsageLimit(3)
            ->withUserUsageLimit(1)
            ->create();

        Promocode::factory()->firstOrderOnly()->percent(30.0)->create();

        $restricted = Promocode::factory()->create([
            'type' => 'percent',
            'value' => 25.0,
            'rules' => ['validity' => true, 'state' => true, 'restricted_usage' => true],
        ]);
        $restricted->allowedCustomers()->attach($juan->id);

        Promocode::factory()->expired()->create([
            'rules' => ['validity' => true, 'state' => true],
        ]);

        Promocode::factory()->paused()->create([
            'rules' => ['validity' => true, 'state' => true],
        ]);
    }
}
