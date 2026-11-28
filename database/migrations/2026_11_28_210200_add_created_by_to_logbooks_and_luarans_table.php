<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pencatat pembuat entri logbook/luaran (keputusan audit 28 Nov
     * 2026, butir 6): anggota tim Diterima hanya boleh mengubah /
     * menghapus entri yang dibuatnya sendiri, sedangkan ketua boleh
     * mengelola semua entri proposalnya — jadi pembuat tiap entri
     * harus tercatat. Entri lama (created_by null) hanya bisa
     * dikelola ketua.
     */
    public function up(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            if (! Schema::hasColumn('logbooks', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('luarans', function (Blueprint $table) {
            if (! Schema::hasColumn('luarans', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('logbooks', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });

        Schema::table('luarans', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn('created_by');
        });
    }
};
