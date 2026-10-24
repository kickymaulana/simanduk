<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['tampilkan_grade_scan_packing', 'tampilkan_grade_scan_checking'] as $key) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => '1',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'tampilkan_grade_scan_packing',
            'tampilkan_grade_scan_checking',
        ])->delete();
    }
};
