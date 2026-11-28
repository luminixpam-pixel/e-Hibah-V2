<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu versi lama berkas laporan kemajuan/akhir.
 *
 * Dibuat saat laporan diunggah ulang (keputusan audit 28 Nov 2026,
 * butir 3): berkas lama tidak dihapus, melainkan disimpan di sini
 * dengan nomor versi berurutan per (proposal, jenis). Berkas versi
 * lama tetap tunduk pada aturan akses yang sama dengan berkas
 * aktifnya (lihat LaporanKemajuanController@downloadVersi).
 */
class LaporanVersiBerkas extends Model
{
    use HasFactory;

    protected $table = 'laporan_versi_berkas';

    protected $fillable = [
        'proposal_id',
        'jenis', // 'kemajuan' atau 'akhir'
        'file_path',
        'nomor_versi',
        'diunggah_oleh',
    ];

    /**
     * Proposal pemilik berkas versi ini.
     */
    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * User yang mengunggah berkas versi ini (saat itu masih aktif).
     */
    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
