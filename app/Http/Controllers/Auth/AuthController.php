<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // 1. Cek Login Lokal (Admin / User yang sudah di-override)
        $userLokal = User::where('username', $request->username)->first();
        if ($userLokal && $userLokal->password && Hash::check($request->password, $userLokal->password)) {
            Auth::login($userLokal);
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        // 2. Cek Login LDAP
        try {
            if (Auth::attempt(['uid' => $request->username, 'password' => $request->password])) {
                $user = Auth::user();

                if (!$user->role) {
                    $user->role = 'pengaju';
                    $user->save();
                }

                $request->session()->regenerate();
                return redirect()->intended('/dashboard');
            }

            // LDAP merespon, tapi username/password salah
            return back()->withErrors([
                'login' => 'Username atau kata sandi salah. Periksa kembali dan coba lagi.',
            ])->withInput($request->only('username'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('LDAP login error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Server LDAP tidak merespon
            return back()->withErrors([
                'ldap' => 'Server LDAP tidak merespon. Silakan coba lagi nanti atau hubungi pihak YARSI.',
            ])->withInput($request->only('username'));
        }
    }
public function adminUpdate(Request $request, $id)
{
    $user = User::findOrFail($id);
    $currentUser = auth()->user();

    // 1. Validasi: Admin bisa edit semua orang, User hanya bisa edit dirinya sendiri
    if ($currentUser->role !== 'admin' && $currentUser->id !== $user->id) {
        return back()->with('error', 'Akses ditolak.');
    }

    if ($currentUser->role === 'admin') {
        // --- LOGIKA ADMIN ---
        $request->validate([
            'email'    => 'required|email|unique:users,email,' . $id,
            'password' => 'nullable|min:6',
        ]);

        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }
    } else {
        // --- LOGIKA USER (Pengaju/Reviewer) ---
        $request->validate([
            'email'    => 'required|email|unique:users,email,' . $id,
            'nidn'     => 'required|string|max:20',
            'fakultas' => 'required|string',
        ]);

        $user->email = $request->email;
        $user->nidn = $request->nidn;
        $user->fakultas = $request->fakultas;
    }

    $user->save();
    return back()->with('success', 'Profil berhasil diperbarui!');
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    // Validasi input
    $request->validate([
        'email' => 'required|email|unique:users,email,' . $user->id,
        'nidn' => 'nullable|string',
        'fakultas' => 'nullable|string',
    ]);

    // Update data
    $user->update([
        'email' => $request->email,
        'nidn' => $request->nidn,
        'fakultas' => $request->fakultas,
    ]);

    return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
}
  public function logout(Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

}