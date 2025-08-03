<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create instructor users first
        $instructorUsers = [
            [
                'name' => 'John Smith',
                'email' => 'john.instructor@example.com',
                'password' => bcrypt('password123'),
                'role' => 2, // Instructor role
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.instructor@example.com',
                'password' => bcrypt('password123'),
                'role' => 2,
            ],
            [
                'name' => 'Michael Davis',
                'email' => 'michael.instructor@example.com',
                'password' => bcrypt('password123'),
                'role' => 2,
            ],
            [
                'name' => 'Emily Wilson',
                'email' => 'emily.instructor@example.com',
                'password' => bcrypt('password123'),
                'role' => 2,
            ],
            [
                'name' => 'David Brown',
                'email' => 'david.instructor@example.com',
                'password' => bcrypt('password123'),
                'role' => 2,
            ],
        ];

        $instructors = [
            [
                'name' => 'John Smith',
                'availability' => 'Full-Time',
                'payrate' => 50.00,
            ],
            [
                'name' => 'Sarah Johnson',
                'availability' => 'Part-Time',
                'payrate' => 45.00,
            ],
            [
                'name' => 'Michael Davis',
                'availability' => 'Full-Time',
                'payrate' => 55.00,
            ],
            [
                'name' => 'Emily Wilson',
                'availability' => 'Part-Time',
                'payrate' => 40.00,
            ],
            [
                'name' => 'David Brown',
                'availability' => 'Contract',
                'payrate' => 60.00,
            ],
        ];

        foreach ($instructorUsers as $index => $userData) {
            // Create or get user
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // Create instructor profile
            Instructor::firstOrCreate(
                ['user_id' => $user->id],
                array_merge($instructors[$index], ['user_id' => $user->id])
            );
        }
    }
}
