<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ScheduleSeeder extends Seeder
{
    public function run()
    {
        // Ambil semua class_id yang ada
        $classIds = DB::table('classes')->pluck('id');

        // Buat 5 schedule untuk setiap class
        foreach ($classIds as $classId) {
            for ($i = 1; $i <= 5; $i++) {
                DB::table('schedules')->insert([
                    'class_id' => $classId,
                    'title' => 'Pertemuan ' . $i,
                    'date' => now()->addDays($i),
                    'start_time' => '08:00',
                    'end_time' => '10:00',
                    'notes' => 'Catatan untuk pertemuan ' . $i,
                    'status' => 'scheduled',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
