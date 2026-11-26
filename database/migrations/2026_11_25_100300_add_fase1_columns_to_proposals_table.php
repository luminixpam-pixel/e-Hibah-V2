<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (! Schema::hasColumn('proposals', 'no_pengajuan')) {
                $table->string('no_pengajuan')->nullable()->unique();
            }
            if (! Schema::hasColumn('proposals', 'skema_id')) {
                $table->foreignId('skema_id')->nullable()->constrained('skemas')->nullOnDelete();
            }
            if (! Schema::hasColumn('proposals', 'periode_id')) {
                $table->foreignId('periode_id')->nullable()->constrained('hibah_periods')->nullOnDelete();
            }
            if (! Schema::hasColumn('proposals', 'dana_disetujui')) {
                $table->decimal('dana_disetujui', 15, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            if (Schema::hasColumn('proposals', 'skema_id')) {
                $table->dropForeign(['skema_id']);
            }
            if (Schema::hasColumn('proposals', 'periode_id')) {
                $table->dropForeign(['periode_id']);
            }
            $table->dropColumn(['no_pengajuan', 'skema_id', 'periode_id', 'dana_disetujui']);
        });
    }
};
