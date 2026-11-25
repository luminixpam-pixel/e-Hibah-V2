<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skemas', function (Blueprint $table) {
            $table->id();
            // Kategori skema sesuai buku panduan: Pembinaan, Kompetisi, Khusus.
            $table->enum('kategori', ['Pembinaan', 'Kompetisi', 'Khusus'])->unique();
            // Plafon dana maksimal (Rp). 0 = belum diisi dari buku panduan.
            $table->decimal('plafon', 15, 2)->default(0);
            // Kuota keikutsertaan maksimal per kategori. 0 = belum diisi dari buku panduan.
            $table->unsignedInteger('kuota')->default(0);
            $table->text('deskripsi')->nullable();
            $table->text('syarat')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skemas');
    }
};
