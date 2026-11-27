<?php

namespace Database\Seeders;

use App\Models\Skema;
use Illuminate\Database\Seeder;

class SkemaSeeder extends Seeder
{
    public function run(): void
    {
        // ANGKA RESMI LPPM (diterima 27 Nov 2026, menggantikan nilai DUMMY).
        // Plafon = maksimum total RAB per proposal.
        // Kuota (jumlah proposal per skema) BELUM diberikan LPPM — JANGAN
        // dikarang. Kuota 0 berarti "Belum ditetapkan" di halaman cek
        // kuota dan pengecekan kuota dilewati (lihat checkPengajuan).
        $data = [
            ['kategori' => 'Pembinaan', 'bidang' => 'Kesehatan', 'plafon' => 15000000, 'kuota' => 0, 'deskripsi' => 'Skema Pembinaan bidang Kesehatan.', 'syarat' => null],
            ['kategori' => 'Pembinaan', 'bidang' => 'Humaniora', 'plafon' => 10000000, 'kuota' => 0, 'deskripsi' => 'Skema Pembinaan bidang Humaniora.', 'syarat' => null],
            ['kategori' => 'Kompetisi', 'bidang' => 'Kesehatan', 'plafon' => 25000000, 'kuota' => 0, 'deskripsi' => 'Skema Kompetisi bidang Kesehatan.', 'syarat' => null],
            ['kategori' => 'Kompetisi', 'bidang' => 'Humaniora', 'plafon' => 15000000, 'kuota' => 0, 'deskripsi' => 'Skema Kompetisi bidang Humaniora.', 'syarat' => null],
            ['kategori' => 'Khusus', 'bidang' => null, 'plafon' => 50000000, 'kuota' => 0, 'deskripsi' => 'Skema Khusus (hanya 1 orang sebagai ketua, tanpa anggota).', 'syarat' => null],
        ];

        foreach ($data as $row) {
            // Baris lama (sebelum ada kolom bidang) punya bidang null.
            // Pakai ulang baris itu untuk varian Kesehatan / Khusus agar
            // proposal yang sudah menunjuk ke skema lama (mis. proposal
            // EH-2026-0001 pada skema Pembinaan, dan draft Kompetisi)
            // tetap menunjuk ke baris skema yang hidup — ID-nya tidak
            // berubah, hanya plafon/bidangnya diperbarui di tempat.
            //
            // Bidang proposal lama itu sebenarnya tidak diketahui; di
            // sini dipetakan ke Kesehatan. Bila ternyata Humaniora,
            // koreksi bidangnya lewat menu Master Skema (admin/LPPM).
            $legacy = null;
            if ($row['bidang'] === 'Kesehatan' || $row['kategori'] === 'Khusus') {
                $legacy = Skema::where('kategori', $row['kategori'])->whereNull('bidang')->first();
            }

            $sudahAda = Skema::where('kategori', $row['kategori'])
                ->where('bidang', $row['bidang'])
                ->exists();

            if ($legacy && ! $sudahAda) {
                $legacy->update($row);
            } else {
                Skema::updateOrCreate(
                    ['kategori' => $row['kategori'], 'bidang' => $row['bidang']],
                    $row
                );
            }
        }
    }
}
