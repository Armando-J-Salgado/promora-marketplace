<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory()->create([
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
        ]);

        Customer::factory()->create([
            'name' => 'María López',
            'email' => 'maria@example.com',
        ]);

        Customer::factory(3)->create();
    }
}
