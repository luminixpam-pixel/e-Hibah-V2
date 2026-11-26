<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
// Core LDAP traits
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;

class User extends Authenticatable implements LdapAuthenticatable
{
    use HasFactory, Notifiable, AuthenticatesWithLdap, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'nidn',
        'no_telepon',
        'fakultas',
        'fakultas_id',
        'bidang_keahlian',
        'is_blacklisted',
        'program_studi',
        'jabatan',
        'role',
        'guid',   // PENTING: Kolom penyimpan ID unik LDAP
        'domain', // PENTING: Kolom penyimpan koneksi LDAP
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'is_blacklisted' => 'boolean',
];
    /*
    |--------------------------------------------------------------------------
    | LdapRecord Required Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Nama kolom di DATABASE Anda yang menyimpan unique ID dari LDAP.
     */
    public function getLdapGuidColumn(): string
    {
        return 'guid';
    }

    /**
     * Nama kolom di DATABASE Anda yang menyimpan nama koneksi LDAP.
     */
    public function getLdapDomainColumn(): string
    {
        return 'domain';
    }

    /**
     * Field login utama di sistem (digunakan oleh Auth::attempt).
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /*
    |--------------------------------------------------------------------------
    | FORMAL ROLE LABEL & RELASI
    |--------------------------------------------------------------------------
    */

    public function getRoleLabelAttribute()
    {
        return match ($this->role) {
            'admin' => 'Administrator TI',
            'reviewer' => 'Reviewer Proposal',
            'pengaju' => 'Pengaju Penelitian',
            'verifikator' => 'Verifikator Administrasi',
            'lppm' => 'Pengelola LPPM',
            'dekan' => 'Pimpinan Fakultas',
            'kepala_lppm' => 'Kepala LPPM',
            default => ucfirst($this->role),
        };
    }

        public function getLdapDiscoveryAttribute(): string
    {
        return 'uid';
    }

    // app/Models/User.php

    public function proposals()
    {
        // Asumsi: satu user bisa punya banyak pengajuan hibah
        // Dan di tabel proposals ada kolom 'user_id'
        return $this->hasMany(Proposal::class, 'user_id');
    }

    /**
     * Fakultas user (relasi baru, Fase 1). Dipakai filter dasbor dekan.
     * Kolom string `fakultas` lama tetap dipertahankan.
     */
    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_id');
    }

    /**
     * Keanggotaan user di tim proposal orang lain.
     */
    public function proposalMembers()
    {
        return $this->hasMany(ProposalMember::class, 'user_id');
    }

    // ... (Relasi tetap sama seperti kode Anda sebelumnya)
}
