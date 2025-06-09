<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
    Category::create(['name' => 'Music']);
    Category::create(['name' => 'Tech']);
    Category::create(['name' => 'Sports']);
    Category::create(['name'=> 'Business']);
    }
}
