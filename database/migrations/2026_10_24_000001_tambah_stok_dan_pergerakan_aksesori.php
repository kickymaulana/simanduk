<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aksesori', function (Blueprint $table) {
            $table->unsignedBigInteger('stok')->default(0)->after('description');
        });

        Schema::create('pergerakan_stok_aksesori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aksesori_id')->constrained('aksesori')->restrictOnDelete();
            $table->foreignId('packing_id')->nullable()->constrained('packing')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('tipe', 30);
            $table->unsignedBigInteger('quantity');
            $table->unsignedBigInteger('stok_sebelum');
            $table->unsignedBigInteger('stok_sesudah');
            $table->string('keterangan')->nullable();
            $table->timestamps();
            $table->index(['aksesori_id', 'created_at']);
            $table->index(['tipe', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergerakan_stok_aksesori');

        Schema::table('aksesori', function (Blueprint $table) {
            $table->dropColumn('stok');
        });
    }
};
