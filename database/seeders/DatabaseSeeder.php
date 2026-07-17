<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CustomerSeeder::class,
            ServiceSeeder::class,
            PromocodeSeeder::class,
            OrderSeeder::class,
        ]);
    }
}
