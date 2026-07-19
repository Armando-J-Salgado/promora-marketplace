<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();

        Service::factory()->create([
            'name' => 'Landing Page',
            'price' => 200.00,
            'category_id' => $categories->where('name', 'Desarrollo Web')->first()->id,
        ]);

        Service::factory()->create([
            'name' => 'App iOS',
            'price' => 500.00,
            'category_id' => $categories->where('name', 'Desarrollo Móvil')->first()->id,
        ]);

        Service::factory()->create([
            'name' => 'Rediseño de interfaz',
            'price' => 150.00,
            'category_id' => $categories->where('name', 'Diseño UI/UX')->first()->id,
        ]);

        Service::factory()->create([
            'name' => 'Campaña SEO',
            'price' => 300.00,
            'category_id' => $categories->where('name', 'Marketing Digital')->first()->id,
        ]);
    }
}
