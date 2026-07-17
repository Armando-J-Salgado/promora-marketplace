<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $desarrollo = Category::factory()->create(['name' => 'Desarrollo']);
        Category::factory()->withParent($desarrollo)->create(['name' => 'Desarrollo Web']);
        Category::factory()->withParent($desarrollo)->create(['name' => 'Desarrollo Móvil']);

        $diseno = Category::factory()->create(['name' => 'Diseño']);
        Category::factory()->withParent($diseno)->create(['name' => 'Diseño UI/UX']);

        Category::factory()->create(['name' => 'Marketing Digital']);
    }
}
