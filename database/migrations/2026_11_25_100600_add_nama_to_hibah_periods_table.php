<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hibah_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('hibah_periods', 'nama')) {
                $table->string('nama')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('hibah_periods', function (Blueprint $table) {
            $table->dropColumn(['nama']);
        });
    }
};
