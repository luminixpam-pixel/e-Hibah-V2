<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Proposal extends Model
{
    use HasFactory;

    protected $table = 'proposals';

    protected $fillable = [
        'nama_ketua',
        'anggota',
        'biaya',
        'judul',
        'file_path',
        'status',
        'periode',
        'fakultas_prodi', // Ini akan menyimpan ID dari tabel fakultas
        'user_id',
        'pengusul',
        'review_deadline',
        'file_laporan',
        'keterangan',
        'status_pendanaan',
        'laporan_kemajuan',
        'laporan_akhir',
        'file_laporan_akhir',
        'keterangan_akhir',
        'status_laporan',
        'no_pengajuan',
        'skema_id',
        'periode_id',
        'dana_disetujui',
    ];

    /**
     * Casting data agar otomatis dikonversi saat diakses
     */
    protected $casts = [
        'review_deadline' => 'datetime',
        'anggota' => 'array', // Mengonversi JSON di database menjadi Array PHP secara otomatis
        'biaya' => 'integer', // Memastikan biaya diproses sebagai angka
        'dana_disetujui' => 'decimal:2',
    ];

    /**
     * Relasi ke Tabel Fakultas
     * Menghubungkan kolom fakultas_prodi ke id di tabel fakultas
     */
    public function fakultas()
    {
        return $this->belongsTo(Fakultas::class, 'fakultas_prodi');
    }

    /**
     * Relasi ke Pengaju (User)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke Reviewers (Many to Many)
     */
    public function reviewers()
    {
        return $this->belongsToMany(User::class, 'proposal_reviewers', 'proposal_id', 'reviewer_id');
    }

    /**
     * Relasi ke Tabel Reviews (One to Many)
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'proposal_id');
    }

    /**
     * Relasi ke Skema hibah yang dipilih saat pengajuan.
     */
    public function skema()
    {
        return $this->belongsTo(Skema::class, 'skema_id');
    }

    /**
     * Relasi ke Periode hibah pengajuan ini.
     */
    public function periode()
    {
        return $this->belongsTo(HibahPeriod::class, 'periode_id');
    }

    /**
     * Relasi ke Item RAB (One to Many).
     */
    public function rabItems()
    {
        return $this->hasMany(RabItem::class, 'proposal_id');
    }

    /**
     * Relasi ke Anggota Tim (One to Many).
     */
    public function members()
    {
        return $this->hasMany(ProposalMember::class, 'proposal_id');
    }

    /**
     * Riwayat verifikasi administrasi (One to Many, Fase 3).
     */
    public function verifications()
    {
        return $this->hasMany(Verification::class, 'proposal_id');
    }

    /**
     * Accessor untuk mempermudah pemanggilan format Rupiah di Blade
     * Contoh penggunaan: $proposal->biaya_rupiah
     */
    public function getBiayaRupiahAttribute()
    {
        return 'Rp ' . number_format($this->biaya, 0, ',', '.');
    }
}
