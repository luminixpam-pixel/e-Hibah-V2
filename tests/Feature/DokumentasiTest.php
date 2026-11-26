<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function f6nBuatUser(string $role, array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => $role], $attrs));
}

test('user yang login bisa membuka halaman dokumentasi teknis', function () {
    $pengaju = f6nBuatUser('pengaju');

    $this->actingAs($pengaju)->get(route('dokumentasi'))
        ->assertOk()
        ->assertSee('Dokumentasi Teknis E-Hibah')
        ->assertSee('Gambaran Sistem')
        ->assertSee('Peran Pengguna')
        ->assertSee('Alur Utama')
        ->assertSee('Cara Menjalankan Test')
        ->assertSee('Catatan Penting')
        ->assertSee('Auth::id()');
});

test('guest diarahkan ke login saat membuka halaman dokumentasi', function () {
    $this->get(route('dokumentasi'))->assertRedirect(route('login'));
});

test('halaman dokumentasi tidak memuat kata sandi atau akun tes', function () {
    $admin = f6nBuatUser('admin');

    // Sengaja tidak menulis nilai kredensial apa pun di test ini:
    // cukup pastikan halaman tidak memuat label/istilah kredensial.
    $this->actingAs($admin)->get(route('dokumentasi'))
        ->assertOk()
        ->assertDontSee('kata sandi')
        ->assertDontSee('password');
});
