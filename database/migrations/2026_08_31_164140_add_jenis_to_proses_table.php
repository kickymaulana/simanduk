<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proses', function (Blueprint $table) {
            $table->enum('jenis', ['Body', 'Tangki'])->nullable()->after('urutan');
        });

        // Proses packing khusus jenis: 18 = Packing Body, 22 = Packing Tangki
        DB::table('proses')->where('id', 18)->update(['jenis' => 'Body']);
        DB::table('proses')->where('id', 22)->update(['jenis' => 'Tangki']);
    }

    public function down(): void
    {
        Schema::table('proses', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};