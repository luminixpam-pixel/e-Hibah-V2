<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HibahPeriodSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya buat satu periode awal bila tabel masih kosong,
        // supaya seeder aman dijalankan berulang kali.
        if (DB::table('hibah_periods')->count() > 0) {
            return;
        }

        DB::table('hibah_periods')->insert([
            // Nama sementara — LPPM dapat mengubahnya lewat master data.
            'nama' => 'Periode '.now()->year,
            'start_date' => now(),
            'end_date' => now()->addMonth(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
