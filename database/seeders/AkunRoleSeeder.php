<?php

namespace Database\Seeders;

use App\Models\Fakultas;
use App\Models\User;
use Illuminate\Database\Seeder;

class AkunRoleSeeder extends Seeder
{
    /**
     * Membuat 1 akun tes untuk setiap role (7 role) + 1 reviewer kedua.
     * Reviewer kedua dibutuhkan karena penugasan plotting wajib 2 reviewer
     * berbeda (assignReviewer menolak reviewer 1 = reviewer 2), sementara
     * sebelumnya hanya ada 1 akun reviewer di sistem.
     * Aman dijalankan berulang kali (updateOrCreate berdasarkan username).
     * Password default semua akun (kecuali admin): password123
     */
    public function run(): void
    {
        $akun = [
            [
                'username' => 'admin',
                'name' => 'Administrator TI',
                'email' => 'admin@gmail.com',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            [
                'username' => 'pengaju',
                'name' => 'Dosen Pengaju',
                'email' => 'pengaju@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'pengaju',
                'fakultas' => 'Fakultas Teknologi Informasi',
                'nidn' => '00000001',
                'program_studi' => 'Teknik Informatika',
                'jabatan' => 'Dosen',
            ],
            [
                'username' => 'reviewer',
                'name' => 'Reviewer Proposal',
                'email' => 'reviewer@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'reviewer',
            ],
            [
                'username' => 'reviewer2',
                'name' => 'Reviewer Proposal 2',
                'email' => 'reviewer2@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'reviewer',
            ],
            [
                'username' => 'verifikator',
                'name' => 'Verifikator Administrasi',
                'email' => 'verifikator@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'verifikator',
            ],
            [
                'username' => 'lppm',
                'name' => 'Pengelola LPPM',
                'email' => 'lppm@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'lppm',
            ],
            [
                'username' => 'dekan',
                'name' => 'Dekan FTI',
                'email' => 'dekan@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'dekan',
                'fakultas' => 'Fakultas Teknologi Informasi',
            ],
            [
                'username' => 'kepala_lppm',
                'name' => 'Kepala LPPM',
                'email' => 'kepala.lppm@yarsi.ac.id',
                'password' => 'password123',
                'role' => 'kepala_lppm',
            ],
        ];

        foreach ($akun as $data) {
            $password = $data['password'];
            unset($data['password']);

            // Isi fakultas_id dari string fakultas (butuh FakultasSeeder
            // jalan dulu — sudah dipanggil di DatabaseSeeder).
            if (! empty($data['fakultas'])) {
                $data['fakultas_id'] = Fakultas::idForPenempatan($data['fakultas']);
            }

            User::updateOrCreate(
                ['username' => $data['username']],
                // Kata sandi polos — di-hash otomatis sekali oleh cast 'hashed' di model User.
                array_merge($data, ['password' => $password])
            );
        }
    }
}
