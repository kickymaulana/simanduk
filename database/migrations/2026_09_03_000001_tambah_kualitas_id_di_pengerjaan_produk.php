<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengerjaan_produk', function (Blueprint $table) {
            $table->foreignId('kualitas_id')->nullable()->after('status_kondisi')->constrained('kualitas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengerjaan_produk', function (Blueprint $table) {
            $table->dropForeign(['kualitas_id']);
            $table->dropColumn('kualitas_id');
        });
    }
};
