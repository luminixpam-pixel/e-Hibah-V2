<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status verifikasi laporan kemajuan & laporan akhir (keputusan
     * audit 28 Nov 2026, butir 4).
     *
     * Temuan: kolom 'status_laporan' yang disebut di Proposal::$fillable
     * TIDAK PERNAH dibuat migrasinya — tidak ada di tabel mana pun.
     * Karena laporan kemajuan/akhir disimpan sebagai kolom di tabel
     * proposals (bukan tabel sendiri), status verifikasi dibuat sebagai
     * dua kolom terpisah di proposals: satu per jenis laporan.
     * Nilai: 'Menunggu Verifikasi' (bawaan saat diunggah), 'Terverifikasi',
     * 'Revisi'. Baris lama (sudah ada berkas) ikut 'Menunggu Verifikasi'
     * karena belum pernah diverifikasi.
     */
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (! Schema::hasColumn('proposals', 'status_laporan_kemajuan')) {
                $table->string('status_laporan_kemajuan')->nullable()->default('Menunggu Verifikasi');
            }
            if (! Schema::hasColumn('proposals', 'status_laporan_akhir')) {
                $table->string('status_laporan_akhir')->nullable()->default('Menunggu Verifikasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['status_laporan_kemajuan', 'status_laporan_akhir']);
        });
    }
};
