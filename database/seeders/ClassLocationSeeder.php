<?php

namespace Database\Seeders;

use App\Models\ClassLocation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClassLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $datas = [
            [
                'name' =>  'Eagle',
                'address' => '123 Eagle St, Cityville',
            ],
            [
                'name' =>  'Meridian',
                'address' => '456 Meridian Ave, Townsville',
            ]
        ];
        foreach($datas as $data){
                ClassLocation::create([
                    'city' => $data['name'],
                    'address' => $data['address'],
                ]);
        }
    }
}
