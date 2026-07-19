<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $juan = Customer::where('email', 'juan@example.com')->first();
        $maria = Customer::where('email', 'maria@example.com')->first();
        $services = Service::all();

        $order1 = Order::factory()->create(['customer_id' => $juan->id]);
        $order1->services()->attach($services->where('name', 'Landing Page')->first()->id, ['quantity' => 1]);
        $order1->getSubtotal();

        $order2 = Order::factory()->create(['customer_id' => $juan->id]);
        $order2->services()->attach($services->where('name', 'App iOS')->first()->id, ['quantity' => 1]);
        $order2->services()->attach($services->where('name', 'Campaña SEO')->first()->id, ['quantity' => 2]);
        $order2->getSubtotal();

        $order3 = Order::factory()->create(['customer_id' => $maria->id]);
        $order3->services()->attach($services->where('name', 'Rediseño de interfaz')->first()->id, ['quantity' => 3]);
        $order3->getSubtotal();

        $order4 = Order::factory()->create(['customer_id' => $maria->id]);
        $order4->services()->attach($services->where('name', 'Landing Page')->first()->id, ['quantity' => 2]);
        $order4->services()->attach($services->where('name', 'Campaña SEO')->first()->id, ['quantity' => 1]);
        $order4->services()->attach($services->where('name', 'Rediseño de interfaz')->first()->id, ['quantity' => 1]);
        $order4->getSubtotal();

        $otherCustomer = Customer::whereNotIn('email', ['juan@example.com', 'maria@example.com'])->first();
        $order5 = Order::factory()->create(['customer_id' => $otherCustomer->id]);
        $order5->services()->attach($services->where('name', 'App iOS')->first()->id, ['quantity' => 1]);
        $order5->services()->attach($services->where('name', 'Landing Page')->first()->id, ['quantity' => 3]);
        $order5->getSubtotal();
    }
}
