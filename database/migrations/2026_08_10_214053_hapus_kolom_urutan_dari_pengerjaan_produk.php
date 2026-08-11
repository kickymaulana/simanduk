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
        // Hanya drop jika kolom benar-benar ada (di fresh install kolom ini tak pernah dibuat)
        if (Schema::hasColumn('pengerjaan_produk', 'urutan')) {
            Schema::table('pengerjaan_produk', function (Blueprint $table) {
                $table->dropColumn('urutan');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengerjaan_produk', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->nullable()->after('proses_id');
        });
    }
};