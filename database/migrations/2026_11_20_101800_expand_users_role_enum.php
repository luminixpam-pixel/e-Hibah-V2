<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah 4 role baru (verifikator, lppm, dekan, kepala_lppm)
     * ke kolom role tabel users, sehingga total menjadi 7 role
     * sesuai UML (8 aktor, Dosen & Anggota Tim berbagi role pengaju):
     *
     *  admin       -> Administrator TI (IT Admin)
     *  pengaju     -> Dosen / Ketua Pengusul & Anggota Tim
     *  reviewer    -> Reviewer
     *  verifikator -> Verifikator Administrasi
     *  lppm        -> Pengelola LPPM
     *  dekan       -> Pimpinan Fakultas
     *  kepala_lppm -> Kepala LPPM
     *
     * Menggunakan DB::statement (raw SQL MySQL) karena package
     * doctrine/dbal tidak terpasang, sehingga ->change() tidak
     * bisa dipakai. MODIFY COLUMN aman dipakai baik dari kolom
     * VARCHAR maupun ENUM yang lama, karena semua nilai role
     * yang sudah ada (admin, pengaju, reviewer) termasuk dalam
     * daftar ENUM yang baru.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pengaju','reviewer','verifikator','lppm','dekan','kepala_lppm') NOT NULL DEFAULT 'pengaju'");
    }

    /**
     * Kembalikan ke 3 role awal (admin, pengaju, reviewer).
     *
     * Catatan: rollback akan GAGAL kalau sudah ada user dengan role
     * verifikator, lppm, dekan, atau kepala_lppm — ubah dulu role
     * user-user itu ke salah satu dari 3 role awal sebelum rollback.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','pengaju','reviewer') NOT NULL DEFAULT 'pengaju'");
    }
};
