<?php

namespace Database\Seeders;

use App\Models\ContactType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contactTypes = [
            ['name' => 'Cell Phone'],
            ['name' => 'Mom Cell'],
            ['name' => 'Dad Cell'],
            ['name' => 'Work Cell'],
            ['name' => 'Dad Work Cell'],
            ['name' => 'Mom Work Cell'],
            ['name' => 'Home Phone'],
        ];

        foreach ($contactTypes as $contactType) {
            ContactType::firstOrCreate($contactType);
        }
    }
}
