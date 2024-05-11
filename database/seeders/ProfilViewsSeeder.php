<?php

namespace Database\Seeders;

use App\Models\ProfileViews;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfilViewsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ProfileViews::factory()->count(50)->create();
    }
}
