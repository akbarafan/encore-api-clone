<?php

namespace Database\Seeders;

use App\Models\ClassCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datas = [
            [
                'name' => 'Art'
            ],
            [
                'name' => 'Film'
            ],
            [
                'name' => 'Music'
            ],
            [
                'name' => 'Theater'
            ],
        ];
        foreach ($datas as $data) {
           ClassCategory::create($data);
        }
    }
}
