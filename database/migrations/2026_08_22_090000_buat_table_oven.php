<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oven', function (Blueprint $table) {
            $table->id();
            $table->string('oven')->unique();
            $table->timestamps();
        });

        DB::table('oven')->insert([
            ['oven' => 'Oven 7'],
            ['oven' => 'Oven 8'],
            ['oven' => 'Oven 9'],
            ['oven' => 'Roll Kiln'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('oven');
    }
};