<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aksesori', function (Blueprint $table) {
            $table->string('part_number')->nullable()->after('id');
            $table->string('description')->nullable()->after('part_number');
        });

        DB::table('aksesori')
            ->select(['id', 'aksesori'])
            ->orderBy('id')
            ->eachById(function (object $aksesori): void {
                DB::table('aksesori')
                    ->where('id', $aksesori->id)
                    ->update([
                        'part_number' => $aksesori->aksesori,
                        'description' => $aksesori->aksesori,
                    ]);
            });

        Schema::table('aksesori', function (Blueprint $table) {
            $table->unique('part_number');
        });
    }

    public function down(): void
    {
        Schema::table('aksesori', function (Blueprint $table) {
            $table->dropUnique(['part_number']);
            $table->dropColumn(['part_number', 'description']);
        });
    }
};
