<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Skema extends Model
{
    use HasFactory;

    protected $table = 'skemas';

    protected $fillable = [
        'kategori',
        'bidang',
        'plafon',
        'kuota',
        'deskripsi',
        'syarat',
    ];

    protected $casts = [
        'plafon' => 'decimal:2',
        'kuota' => 'integer',
    ];

    /**
     * Nama tampil skema: kategori + bidang, mis. "Kompetisi Kesehatan".
     * Khusus tidak punya bidang sehingga labelnya hanya "Khusus".
     */
    public function label(): string
    {
        return trim($this->kategori.' '.($this->bidang ?? ''));
    }

    /**
     * Batas ukuran tim (ketua + anggota) menurut angka resmi LPPM
     * (27 Nov 2026). Null berarti LPPM belum menetapkan batas untuk
     * kategori ini (saat ini: Pembinaan) sehingga tidak dibatasi.
     */
    public function maksUkuranTim(): ?int
    {
        return match ($this->kategori) {
            'Kompetisi' => 3,
            'Khusus' => 1,
            default => null,
        };
    }

    /**
     * Proposal yang memakai skema ini.
     */
    public function proposals()
    {
        return $this->hasMany(Proposal::class, 'skema_id');
    }
}
