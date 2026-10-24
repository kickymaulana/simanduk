<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produk', function (Blueprint $table) {
            $table->foreignId('merk_id')->nullable()->after('kualitas_id')->constrained('merk')->restrictOnDelete();
        });

        Schema::table('pengerjaan_produk', function (Blueprint $table) {
            $table->foreignId('merk_id')->nullable()->after('kualitas_id')->constrained('merk')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pengerjaan_produk', function (Blueprint $table) {
            $table->dropForeign(['merk_id']);
            $table->dropColumn('merk_id');
        });

        Schema::table('produk', function (Blueprint $table) {
            $table->dropForeign(['merk_id']);
            $table->dropColumn('merk_id');
        });
    }
};
