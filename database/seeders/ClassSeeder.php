<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassSeeder extends Seeder
{
    public function run()
    {
        DB::table('classes')->insert([
            [
                'season_id' => 1,
                'class_category_id' => 1,
                'class_type_id' => 1,
                'class_time_id' => 1,
                'class_location_id' => 1,
                'name' => 'Math 101',
                'description' => 'Basic Mathematics for Beginners',
                'instructor_id' => 1,
                'cost' => 200.00,
                'is_approved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'season_id' => 1,
                'class_category_id' => 1,
                'class_type_id' => 1,
                'class_time_id' => 1,
                'class_location_id' => 1,
                'name' => 'Science 101',
                'description' => 'Introduction to Basic Science',
                'instructor_id' => 1,
                'cost' => 250.00,
                'is_approved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'season_id' => 1,
                'class_category_id' => 1,
                'class_type_id' => 1,
                'class_time_id' => 1,
                'class_location_id' => 1,
                'name' => 'English 101',
                'description' => 'English Language Fundamentals',
                'instructor_id' => 1,
                'cost' => 180.00,
                'is_approved' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
