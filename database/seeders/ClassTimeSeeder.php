<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassTimeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datas = [
            [
                'name' => 'Home School',
            ],
            [
                'name' => 'After School',
            ],  
        ];

        foreach ($datas as $data) {
            \App\Models\ClassTime::create([
                'name' => $data['name'],
            ]);
        }
    }
}
