<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // CEK DULU: kalau kolom belum ada, baru ditambahkan.
        // Form isi-review mengirim field `status` (disetujui/ditolak)
        // yang selama ini diabaikan submitReview() karena kolomnya
        // tidak ada (hanya tercantum di Review::$fillable).
        if (!Schema::hasColumn('reviews', 'status')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->string('status')->nullable()->after('rekomendasi_biaya');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // CEK DULU: kalau kolom ada, baru dihapus
        if (Schema::hasColumn('reviews', 'status')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
