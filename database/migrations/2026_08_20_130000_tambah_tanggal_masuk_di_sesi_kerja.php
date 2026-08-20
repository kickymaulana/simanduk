<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sesi_kerja', function (Blueprint $table) {
            $table->date('tanggal_masuk')->nullable()->after('proses_id');
        });
    }

    public function down(): void
    {
        Schema::table('sesi_kerja', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk');
        });
    }
};