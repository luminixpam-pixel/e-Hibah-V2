<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monev_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->text('uraian_temuan');
            $table->text('rekomendasi');
            // Status temuan (dipakai Fase 5, story 10): Open / In_Review / Closed.
            // Pakai string + validasi in:... (bukan DB enum) agar aman di SQLite saat test,
            // mengikuti gaya kolom status yang sudah ada.
            $table->string('status_temuan')->default('Open');
            // File RTL dari pengaju (PDF, maks 2 MB) — diisi saat pengaju mengirim RTL.
            $table->string('file_rtl')->nullable();
            // Bukti perbaikan dari pengaju (PDF, maks 2 MB).
            $table->string('bukti_perbaikan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monev_findings');
    }
};
