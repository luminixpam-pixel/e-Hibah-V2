<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fakultas extends Model
{
    protected $table = 'fakultas';
    protected $fillable = ['nama_fakultas', 'kode_fakultas'];

    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'fakultas_prodi');
    }

    /**
     * User yang terdaftar di fakultas ini (lewat users.fakultas_id).
     */
    public function users()
    {
        return $this->hasMany(User::class, 'fakultas_id');
    }

    /**
     * Petakan string penempatan/fakultas lama (kolom users.fakultas) ke id
     * fakultas. Dipakai migrasi backfill Fase 1 dan seeder dosen.
     *
     * Urutan penting: kata kunci yang lebih spesifik dicek dulu
     * ('Kedokteran Gigi' sebelum 'Kedokteran', 'Dokter Gigi' sebelum
     * 'Dokter'), karena string yang satu mengandung yang lain.
     *
     * Mengembalikan null kalau tidak terpetakan — pemanggil membiarkan
     * fakultas_id tetap null (dasbor dekan lalu menampilkan semua
     * proposal, seperti perilaku lama). Nilai yang belum terpetakan:
     * 'S2 - Administrasi Rumah Sakit' (fakultasnya belum dipastikan).
     * Asumsi pemetaan (mohon dikonfirmasi tim): 'Perpustakaan dan Sains
     * Informasi' dan 'Sains Biomedis' dimasukkan ke Fakultas Teknologi
     * Informasi dan Fakultas Kedokteran.
     */
    public static function idForPenempatan(?string $penempatan): ?int
    {
        if (! $penempatan) {
            return null;
        }

        // 1. Nama fakultas persis (mis. 'Fakultas Teknologi Informasi').
        $exact = static::where('nama_fakultas', $penempatan)->value('id');
        if ($exact) {
            return (int) $exact;
        }

        // 2. Kata kunci program studi -> nama fakultas.
        $map = [
            'Kedokteran Gigi' => 'Fakultas Kedokteran Gigi',
            'Dokter Gigi' => 'Fakultas Kedokteran Gigi',
            'Kedokteran' => 'Fakultas Kedokteran',
            'Dokter' => 'Fakultas Kedokteran',
            'Sains Biomedis' => 'Fakultas Kedokteran',
            'Teknik Informatika' => 'Fakultas Teknologi Informasi',
            'Perpustakaan' => 'Fakultas Teknologi Informasi',
            'Psikologi' => 'Fakultas Psikologi',
            'Ilmu Hukum' => 'Fakultas Hukum',
            'Kenotariatan' => 'Fakultas Hukum',
            'Manajemen' => 'Fakultas Ekonomi dan Bisnis',
            'Akuntansi' => 'Fakultas Ekonomi dan Bisnis',
        ];

        foreach ($map as $keyword => $namaFakultas) {
            if (str_contains($penempatan, $keyword)) {
                $id = static::where('nama_fakultas', $namaFakultas)->value('id');
                if ($id) {
                    return (int) $id;
                }
            }
        }

        return null;
    }
}
