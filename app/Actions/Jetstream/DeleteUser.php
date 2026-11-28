<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     *
     * Keputusan audit 28 Nov 2026 (butir 14): ADMIN TIDAK BOLEH
     * menghapus akunnya sendiri. Penolakan dilempar sebagai
     * ValidationException berkunci 'password' supaya Livewire
     * (Jetstream DeleteUserForm) menampilkannya di modal konfirmasi
     * hapus akun, pada slot <x-input-error for="password"> yang
     * sudah ada — melempar exception biasa akan menjadi halaman
     * error 500 dan pesannya tidak sampai ke user. User non-admin
     * tetap bisa menghapus akunnya sendiri seperti biasa.
     *
     * Ini satu-satunya jalur penghapusan akun user di aplikasi:
     * tidak ada controller lain yang menghapus User (AdminController
     * hanya mengubah data/role, tanpa method hapus user).
     */
    public function delete(User $user): void
    {
        if ($user->role === 'admin') {
            throw ValidationException::withMessages([
                'password' => 'Akun admin tidak dapat dihapus. Penghapusan akun hanya berlaku untuk user non-admin.',
            ]);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
