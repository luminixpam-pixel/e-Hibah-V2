<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    use HasFactory;

    protected $table = 'proposals';

    /**
     * Kolom yang boleh diisi massal. PERHATIAN: 'status', 'user_id',
     * 'file_path', 'review_deadline', dan 'biaya' ada di sini karena
     * dipakai kode internal (ProposalController@store/update,
     * assignReviewer, keputusan) — BUKAN karena boleh diisi dari
     * request. store()/update() membangun array datanya secara
     * eksplisit dari field yang diizinkan dan tidak pernah memakai
     * $request->all(), sehingga kiriman status/user_id/file_path dari
     * client tidak berpengaruh (diuji KeamananProposalTest).
     * Jangan tambahkan pemakaian $request->all()/fill() di controller
     * proposal tanpa menghapus field sensitif dari daftar ini dulu.
     */
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
        'status_laporan_kemajuan',
        'status_laporan_akhir',
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
     * Apakah user ini anggota tim proposal dengan status Diterima?
     * Dipakai otorisasi pasca-hibah (keputusan audit 28 Nov 2026,
     * butir 6): anggota Diterima boleh mengunggah laporan/logbook/
     * luaran; anggota Menunggu/Ditolak tidak. Ketua (user_id
     * proposal) bukan "anggota" di sini — cek isOwner terpisah.
     */
    public function isAnggotaDiterima(int $userId): bool
    {
        return $this->members()
            ->where('user_id', $userId)
            ->where('status_konfirmasi', 'Diterima')
            ->exists();
    }

    /**
     * Riwayat versi berkas laporan kemajuan/akhir (One to Many,
     * butir 3: berkas lama disimpan, bukan dihapus).
     */
    public function laporanVersi()
    {
        return $this->hasMany(LaporanVersiBerkas::class, 'proposal_id');
    }

    /**
     * Riwayat verifikasi administrasi (One to Many, Fase 3).
     */
    public function verifications()
    {
        return $this->hasMany(Verification::class, 'proposal_id');
    }

    /**
     * Entri logbook pasca-hibah (One to Many, Fase 5, story 9).
     */
    public function logbooks()
    {
        return $this->hasMany(Logbook::class, 'proposal_id');
    }

    /**
     * Luaran pasca-hibah (One to Many, Fase 5, story 9).
     */
    public function luarans()
    {
        return $this->hasMany(Luaran::class, 'proposal_id');
    }

    /**
     * Temuan Monev + RTL (One to Many, Fase 5, story 10).
     */
    public function monevFindings()
    {
        return $this->hasMany(MonevFinding::class, 'proposal_id');
    }

    /**
     * Accessor untuk mempermudah pemanggilan format Rupiah di Blade
     * Contoh penggunaan: $proposal->biaya_rupiah
     */
    public function getBiayaRupiahAttribute()
    {
        return 'Rp '.number_format($this->biaya, 0, ',', '.');
    }
}
