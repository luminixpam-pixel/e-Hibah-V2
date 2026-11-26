<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    // database/seeders/DatabaseSeeder.php
    public function run(): void
    {
        // Master data dulu — dipakai seeder lain (fakultas_id di
        // AkunRoleSeeder & DosenSisterSeeder).
        $this->call(FakultasSeeder::class);
        $this->call(HibahPeriodSeeder::class);

        // Data dosen SISTER (sudah diperbaiki di Fase 0). Dijalankan sebelum
        // AkunRoleSeeder supaya kata sandi akun tes (admin123/password123)
        // yang berlaku — DosenSisterSeeder memakai email admin@gmail.com
        // yang sama untuk username admin.
        $this->call(DosenSisterSeeder::class);

        // Akun Admin Lokal (PENTING)
        \App\Models\User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@gmail.com',
                // Kata sandi polos — di-hash otomatis sekali oleh cast 'hashed' di model User.
                'password' => 'admin123',
                'role' => 'admin',
            ]
        );

        // Akun tes 1 per role (admin, pengaju, reviewer, verifikator, lppm, dekan, kepala_lppm)
        $this->call(AkunRoleSeeder::class);

        // Skema hibah (plafon & kuota masih 0 — lihat TODO di SkemaSeeder).
        $this->call(SkemaSeeder::class);

        // Jalankan Sinkronisasi LDAP
        // $this->call(LdapDosenSeeder::class);
    }
}
