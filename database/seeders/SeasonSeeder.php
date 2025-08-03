<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeasonSeeder extends Seeder
{
    public function run()
    {
        DB::table('seasons')->insert([
            'id' => 1,
            'name' => 'Summer 2025',
            'start_date' => today(),
            'end_date' => today()->addMonths(3),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
