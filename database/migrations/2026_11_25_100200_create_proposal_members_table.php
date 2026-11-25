<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Peran anggota dalam tim: Ketua / Anggota.
            $table->string('peran')->default('Anggota');
            // Status konfirmasi keanggotaan (dipakai Fase 2, story 14): Menunggu / Diterima / Ditolak.
            $table->string('status_konfirmasi')->default('Menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_members');
    }
};
