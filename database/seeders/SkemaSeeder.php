<?php

namespace Database\Seeders;

use App\Models\Skema;
use Illuminate\Database\Seeder;

class SkemaSeeder extends Seeder
{
    public function run(): void
    {
        // DATA DUMMY — dipakai sementara sambil menunggu jawaban resmi dari LPPM.
        // JANGAN dipakai untuk validasi RAB produksi. Ganti dengan angka resmi
        // dari SK/panduan hibah internal LPPM YARSI begitu jawabannya ada.
        // Kuota = maks jumlah keikutsertaan (ketua + anggota) per dosen per kategori.
        $data = [
            ['kategori' => 'Pembinaan', 'plafon' => 25000000, 'kuota' => 2, 'deskripsi' => '[DUMMY] Skema Pembinaan — deskripsi resmi menyusul.', 'syarat' => null],
            ['kategori' => 'Kompetisi', 'plafon' => 50000000, 'kuota' => 2, 'deskripsi' => '[DUMMY] Skema Kompetisi — deskripsi resmi menyusul.', 'syarat' => null],
            ['kategori' => 'Khusus', 'plafon' => 100000000, 'kuota' => 1, 'deskripsi' => '[DUMMY] Skema Khusus — deskripsi resmi menyusul.', 'syarat' => null],
        ];

        foreach ($data as $row) {
            Skema::updateOrCreate(['kategori' => $row['kategori']], $row);
        }
    }
}
