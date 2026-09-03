<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cut off produksi: scan pada jam < cut_off (mis. 06:00) dianggap milik hari kalender sebelumnya.
 * Nilai jam diambil dari tabel settings (key `cut_off_jam`).
 */
class CutOff
{
    public static function jam(): int
    {
        $jam = (int) DB::table('settings')->where('key', 'cut_off_jam')->value('value');

        return ($jam >= 0 && $jam <= 23) ? $jam : 6;
    }

    /** Ekspresi SQL: timestamp digeser mundur sesuai cut off (utk di-select/group). */
    public static function expr(string $column): string
    {
        return "DATE_SUB({$column}, INTERVAL " . self::jam() . " HOUR)";
    }

    /** Rentang [start, end) bulan produksi — kolom mentah sudah digeser dulu oleh cut off. */
    public static function rangeBulan(int $bulan, int $tahun): array
    {
        $jam = self::jam();
        $start = Carbon::create($tahun, $bulan, 1, 0, 0, 0)->addHours($jam);
        $end = Carbon::create($tahun, $bulan, 1, 0, 0, 0)->addMonth()->addHours($jam);

        return [$start, $end];
    }
}
