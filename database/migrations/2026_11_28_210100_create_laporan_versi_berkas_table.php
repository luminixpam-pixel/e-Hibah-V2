<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat versi berkas laporan (keputusan audit 28 Nov 2026,
     * butir 3): saat laporan kemajuan/akhir diunggah ulang, berkas
     * lama TIDAK lagi dihapus — dipindah ke tabel ini sebagai versi
     * bernomor, dengan pengunggah dan waktunya.
     */
    public function up(): void
    {
        Schema::create('laporan_versi_berkas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('jenis'); // 'kemajuan' atau 'akhir'
            $table->string('file_path');
            $table->unsignedInteger('nomor_versi');
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['proposal_id', 'jenis', 'nomor_versi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_versi_berkas');
    }
};
