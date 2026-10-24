<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aksesori', function (Blueprint $table) {
            $table->string('part_number')->nullable(false)->change();
            $table->string('description')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('aksesori', function (Blueprint $table) {
            $table->string('part_number')->nullable()->change();
            $table->string('description')->nullable()->change();
        });
    }
};
