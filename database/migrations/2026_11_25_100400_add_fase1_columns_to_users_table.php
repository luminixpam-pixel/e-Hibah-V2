<?php

use App\Models\Fakultas;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'fakultas_id')) {
                $table->foreignId('fakultas_id')->nullable()->constrained('fakultas')->nullOnDelete();
            }
            if (! Schema::hasColumn('users', 'bidang_keahlian')) {
                $table->string('bidang_keahlian')->nullable();
            }
            if (! Schema::hasColumn('users', 'is_blacklisted')) {
                $table->boolean('is_blacklisted')->default(false);
            }
        });

        // Backfill fakultas_id dari kolom string `fakultas` yang sudah ada,
        // supaya filter dekan (where fakultas_prodi = users.fakultas_id)
        // berfungsi. Baris yang tidak terpetakan dibiarkan null — dasbor
        // dekan tetap menampilkan semua proposal untuk user tersebut
        // (perilaku lama). Kalau tabel fakultas belum terisi (migrate
        // fresh), semua dibiarkan null; seeder yang mengisi fakultas_id.
        DB::table('users')
            ->whereNull('fakultas_id')
            ->whereNotNull('fakultas')
            ->orderBy('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $fakultasId = Fakultas::idForPenempatan($user->fakultas);
                    if ($fakultasId) {
                        DB::table('users')->where('id', $user->id)->update(['fakultas_id' => $fakultasId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'fakultas_id')) {
                $table->dropForeign(['fakultas_id']);
            }
            $table->dropColumn(['fakultas_id', 'bidang_keahlian', 'is_blacklisted']);
        });
    }
};
