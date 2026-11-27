<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pecah 3 kategori skema menjadi 5 baris berdasarkan angka resmi LPPM
     * (27 Nov 2026): Pembinaan dan Kompetisi dibedakan per bidang
     * (Kesehatan/Humaniora) dengan plafon masing-masing; Khusus tetap
     * satu baris tanpa bidang.
     *
     * Unik kategori tunggal diganti unik gabungan (kategori, bidang)
     * supaya dua bidang dalam satu kategori bisa berdampingan.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('skemas', 'bidang')) {
            Schema::table('skemas', function (Blueprint $table) {
                $table->enum('bidang', ['Kesehatan', 'Humaniora'])->nullable()->after('kategori');
            });
        }

        Schema::table('skemas', function (Blueprint $table) {
            $table->dropUnique(['kategori']);
            $table->unique(['kategori', 'bidang']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Unik kategori tunggal hanya bisa dikembalikan bila tidak ada dua
     * baris yang berbagi kategori yang sama (mis. Pembinaan Kesehatan
     * dan Pembinaan Humaniora) — hapus/gabungkan dulu baris duplikatnya.
     */
    public function down(): void
    {
        Schema::table('skemas', function (Blueprint $table) {
            $table->dropUnique(['kategori', 'bidang']);
        });

        if (Schema::hasColumn('skemas', 'bidang')) {
            Schema::table('skemas', function (Blueprint $table) {
                $table->dropColumn('bidang');
            });
        }

        $duplikatKategori = DB::table('skemas')
            ->select('kategori')
            ->groupBy('kategori')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $duplikatKategori) {
            Schema::table('skemas', function (Blueprint $table) {
                $table->unique('kategori');
            });
        }
    }
};
