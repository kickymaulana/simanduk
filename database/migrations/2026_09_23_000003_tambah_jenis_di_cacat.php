<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cacat', function (Blueprint $table) {
            $table->enum('jenis', ['Body', 'Tangki'])->default('Body')->after('cacat');
        });
    }

    public function down(): void
    {
        Schema::table('cacat', function (Blueprint $table) {
            $table->dropColumn('jenis');
        });
    }
};
