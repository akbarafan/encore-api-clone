<?php

namespace Database\Seeders;

use App\Models\ClassType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $datas = [
            [
                'name' => 'Private',
            ],
            [
                'name' => 'Group',
            ],
            [
                'name' => 'Seasonal/Camps',
            ],

        ];
        foreach ($datas as $data) {
            ClassType::create([
                'name' => $data['name'],
            ]);
        }
    }
}
