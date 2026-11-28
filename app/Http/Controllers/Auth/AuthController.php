<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\PesanValidasi;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Batas percobaan login: 5 kali gagal per menit per username+IP.
     *
     * PENTING: limiter bernama 'login' di config/fortify.php dan
     * FortifyServiceProvider TIDAK melindungi jalur ini — login yang
     * hidup adalah route POST /login kustom di bawah (bukan Fortify
     * AttemptToAuthenticate), dan route itu tidak memakai middleware
     * throttle. Tanpa pembatasan di sini, login bisa di-brute-force
     * tanpa batas. Pembatasan dilakukan di controller agar tetap
     * aktif apa pun middleware route-nya.
     */
    private const MAKS_PERCOBAAN_LOGIN = 5;

    private const DETIK_BLOKIR_LOGIN = 60;

    private function kunciThrottleLogin(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('username')).'|'.$request->ip());
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ], PesanValidasi::pesan([
            'username' => 'Username',
            'password' => 'Kata sandi',
        ]));

        // Throttle dicek SETELAH validasi: request tanpa kredensial
        // (field kosong) bukan percobaan login dan tidak dihitung.
        $kunci = $this->kunciThrottleLogin($request);

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN_LOGIN)) {
            $detik = RateLimiter::availableIn($kunci);

            return back()->withErrors([
                'login' => "Terlalu banyak percobaan login. Silakan coba lagi dalam {$detik} detik.",
            ])->withInput($request->only('username'));
        }

        // 1. Cek Login Lokal (Admin / User yang sudah di-override)
        $userLokal = User::where('username', $request->username)->first();
        if ($userLokal && $userLokal->password && Hash::check($request->password, $userLokal->password)) {
            RateLimiter::clear($kunci);
            Auth::login($userLokal);
            $request->session()->regenerate();

            return redirect()->intended($this->dashboardUrlFor($userLokal));
        }

        // 2. Cek Login LDAP
        try {
            if (Auth::attempt(['uid' => $request->username, 'password' => $request->password])) {
                $user = Auth::user();

                if (! $user->role) {
                    $user->role = 'pengaju';
                    $user->save();
                }

                RateLimiter::clear($kunci);
                $request->session()->regenerate();

                return redirect()->intended($this->dashboardUrlFor($user));
            }

            // LDAP merespon, tapi username/password salah
            RateLimiter::hit($kunci, self::DETIK_BLOKIR_LOGIN);

            return back()->withErrors([
                'login' => 'Username atau kata sandi salah. Periksa kembali dan coba lagi.',
            ])->withInput($request->only('username'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('LDAP login error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            // Server LDAP tidak merespon. Tetap dihitung sebagai
            // percobaan gagal: tanpa ini, penyerang bisa membanjiri
            // jalur login lewat akun LDAP saat server LDAP mati dan
            // tidak pernah kena throttle (login lokal yang gagal pun
            // jatuh ke cabang ini). Pengguna sah tidak dirugikan:
            // kata sandi lokal yang benar sudah lolos di langkah 1
            // sebelum LDAP dicoba.
            RateLimiter::hit($kunci, self::DETIK_BLOKIR_LOGIN);

            return back()->withErrors([
                'ldap' => 'Server LDAP tidak merespon. Silakan coba lagi nanti atau hubungi pihak YARSI.',
            ])->withInput($request->only('username'));
        }
    }

    /**
     * URL dashboard sesuai role user.
     * Dipakai setelah login berhasil (redirect()->intended() memakai
     * URL ini sebagai tujuan default kalau tidak ada intended URL).
     */
    protected function dashboardUrlFor($user): string
    {
        return match ($user->role) {
            'admin' => route('dashboard.admin'),
            'pengaju' => route('dashboard.pengaju'),
            'reviewer' => route('dashboard.reviewer'),
            'verifikator' => route('dashboard.verifikator'),
            'lppm' => route('dashboard.lppm'),
            'dekan' => route('dashboard.dekan'),
            'kepala_lppm' => route('dashboard.kepala-lppm'),
            default => route('dashboard.pengaju'),
        };
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Hanya admin atau pemilik akun yang boleh mengubah profil ini.
        $currentUser = auth()->user();
        if ($currentUser->role !== 'admin' && $currentUser->id !== $user->id) {
            return back()->with('error', 'Akses ditolak.');
        }

        // Validasi input
        $request->validate([
            'email' => 'required|email|unique:users,email,'.$user->id,
            'nidn' => 'nullable|string|max:50',
            'fakultas' => 'nullable|string|max:255',
        ], PesanValidasi::pesan([
            'email' => 'Email',
            'nidn' => 'NIDN',
            'fakultas' => 'Fakultas',
        ]));

        // Update data
        $user->update([
            'email' => $request->email,
            'nidn' => $request->nidn,
            'fakultas' => $request->fakultas,
        ]);

        return redirect()->back()->with('success', 'Profil berhasil diperbarui!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
