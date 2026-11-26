<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase; // Tambahkan ini

class LoginTest extends TestCase
{
    use RefreshDatabase; // <--- Laravel akan otomatis menjalankan migration (migrate)

    public function test_user_bisa_login_sampai_dashboard()
    {
        // 1. Buat user secara manual (pastikan username unik)
        $user = User::create([
            'username' => 'dosen_test',
            'name' => 'Dosen Testing',
            'email' => 'test@yarsi.ac.id',
            'password' => \Illuminate\Support\Facades\Hash::make('rahasia123'),
            'role' => 'pengaju',
        ]);

        // 2. Jalankan Login
        $response = $this->post('/login', [
            'username' => 'dosen_test',
            'password' => 'rahasia123',
        ]);

        // Debugging jika masih gagal:
        if ($response->status() !== 302) {
            // Ini akan memunculkan error asli jika ada masalah di code
            $this->fail('Login gagal. Pesan error: '.session('errors')?->first('username'));
        }

        $response->assertRedirect('/dashboard/pengaju');
        $this->assertAuthenticatedAs($user);
    }
}
