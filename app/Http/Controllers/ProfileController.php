<?php

namespace App\Http\Controllers;

use App\Helpers\PesanValidasi;
use App\Models\Fakultas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('profile.show', compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();
        $list_fakultas = Fakultas::all();

        return view('profile.edit', compact('user', 'list_fakultas'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Field diisi satu per satu secara eksplisit — 'role' dan
        // 'is_blacklisted' (yang ada di User::$fillable) TIDAK pernah
        // diambil dari request, jadi user biasa tidak bisa menyusupi
        // role admin atau menghapus blacklist-nya sendiri lewat
        // request yang dimanipulasi. Jangan ganti pola ini dengan
        // $user->update($request->all()) / $request->validated()
        // tanpa daftar field yang diizinkan.
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8',
            'nidn' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'fakultas' => 'nullable|string|max:255',
            'prodi' => 'nullable|string|max:100',
            'jabatan' => 'nullable|string|max:100',
        ], PesanValidasi::pesan([
            'name' => 'Nama',
            'email' => 'Email',
            'password' => 'Kata sandi',
            'nidn' => 'NIDN',
            'phone' => 'Nomor telepon',
            'fakultas' => 'Fakultas',
            'prodi' => 'Program studi',
            'jabatan' => 'Jabatan',
        ]));

        $user->name = $request->name;
        $user->email = $request->email;
        $user->nidn = $request->nidn;
        $user->phone = $request->phone;
        $user->fakultas = $request->fakultas;
        $user->prodi = $request->prodi;
        $user->jabatan = $request->jabatan;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        /** @var \App\Models\User $user */
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profil berhasil diperbarui!');
    }
}
