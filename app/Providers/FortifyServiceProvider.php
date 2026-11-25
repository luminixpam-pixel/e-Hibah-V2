<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Http\Requests\LoginRequest; // Tambahkan ini


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Beritahu Fortify secara global bahwa kita menggunakan 'username'
        Fortify::username('username');

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // 2. FIX UTAMA: Paksa validasi Fortify untuk menggunakan 'username', bukan 'email'
        $this->app->bind(LoginRequest::class, function ($app) {
            return new class extends LoginRequest {
                public function rules(): array {
                    return [
                        'username' => 'required|string',
                        'password' => 'required|string',
                    ];
                }
            };
        });

        // 3. JALUR LOGIN (Fase 1): DULU di sini ada Fortify::authenticateUsing()
        // dengan logika login LDAP sendiri. Blok itu DIHAPUS karena:
        //  - Provider ini TIDAK terdaftar di bootstrap/providers.php, jadi
        //    boot() tidak berjalan dan closure itu tidak pernah dipakai.
        //  - Login yang hidup hanya SATU jalur: routes/web.php (POST /login)
        //    -> Auth\AuthController (cek lokal dulu, lalu LDAP via
        //    Auth::attempt). Jangan menambahkan authenticateUsing() lagi.
        //  - Closure lama juga mengandung bug: updateOrCreate memakai kolom
        //    npm_nik/tipe_user yang tidak ada di tabel users, dan ada jalur
        //    hardcoded untuk username 'admin_utama'.

        RateLimiter::for('login', function (Request $request) {
            // Gunakan username untuk pembatasan login (throttling)
            $throttleKey = Str::transliterate(Str::lower($request->username).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
