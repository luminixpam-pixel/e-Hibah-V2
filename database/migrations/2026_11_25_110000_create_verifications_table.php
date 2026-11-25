<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('verifikator_id')->nullable()->constrained('users')->nullOnDelete();
            // Hasil pemeriksaan (dipakai Fase 3, story 22): Lolos / Revisi.
            $table->string('hasil');
            // Alasan pengembalian — wajib diisi bila hasil Revisi.
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
