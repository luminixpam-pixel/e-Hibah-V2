<?php

namespace Database\Seeders;

use App\Models\Skema;
use Illuminate\Database\Seeder;

class SkemaSeeder extends Seeder
{
    public function run(): void
    {
        // TODO: Isi plafon & kuota dari Buku Panduan Hibah tahun berjalan.
        // Angka di bawah masih 0 (placeholder) — JANGAN dipakai untuk
        // validasi RAB sebelum diisi angka resmi dari buku panduan.
        $data = [
            ['kategori' => 'Pembinaan', 'plafon' => 0, 'kuota' => 0, 'deskripsi' => null, 'syarat' => null],
            ['kategori' => 'Kompetisi', 'plafon' => 0, 'kuota' => 0, 'deskripsi' => null, 'syarat' => null],
            ['kategori' => 'Khusus', 'plafon' => 0, 'kuota' => 0, 'deskripsi' => null, 'syarat' => null],
        ];

        foreach ($data as $row) {
            Skema::updateOrCreate(['kategori' => $row['kategori']], $row);
        }
    }
}
