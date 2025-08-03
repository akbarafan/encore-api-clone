<?php

use App\Models\ClassLocation;
use App\Models\User;
use Database\Seeders\ClassCategorySeeder;
use Database\Seeders\ClassLocationSeeder;
use Database\Seeders\ClassTimeSeeder;
use Database\Seeders\ClassTypeSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Database\Seeders\ContactTypeSeeder;
use Database\Seeders\InstructorSeeder;
use Database\Seeders\MessageActivitySeeder;
use Database\Seeders\ScheduleSeeder;
use Database\Seeders\ClassSeeder;
use Database\Seeders\SeasonSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // User example
        DB::table('users')->insert(
            [
                'id' => Str::uuid(),
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'role' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Instructor User',
                'email' => 'instructor@example.com',
                'password' => bcrypt('password'),
                'role' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Family User',
                'email' => 'family@example.com',
                'password' => bcrypt('password'),
                'role' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $users = User::all();

        // Contact types and other seeders
        $this->call([
            ContactTypeSeeder::class,
            ClassTimeSeeder::class,
            ClassLocationSeeder::class,
            ClassCategorySeeder::class,
            ClassTypeSeeder::class,
            InstructorSeeder::class,
            SeasonSeeder::class,
            ClassSeeder::class,
            MessageActivitySeeder::class,
            ScheduleSeeder::class,
        ]);

        // Class Category example
        DB::table('class_categories')->insert([
            'name' => 'Privat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Class Type example
        DB::table('class_types')->insert([
            'name' => 'Online',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Schedule example
        DB::table('schedules')->insert([
            'class_id' => 1,
            'title' => 'testing',
            'date' => today()->addDays(1),
            'start_time' => '08:00',
            'end_time' => '10:00',
            'notes' => "testtststs",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
