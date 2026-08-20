<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Support\Facades\DB;

#[Signature('sesi:backfill-tanggal-masuk')]
#[Description('Isi tanggal_masuk kosong pada sesi_kerja dari tanggal scan pertama (atau created_at sesi jika tanpa scan)')]
class BackfillTanggalMasuk extends Command
{
    public function handle(): int
    {
        $multi = 0;
        $updated = DB::transaction(function () use (&$multi) {
            $sesi = DB::table('sesi_kerja')
                ->whereNull('tanggal_masuk')
                ->pluck('id');

            $scanDates = DB::table('pengerjaan_produk')
                ->whereIn('sesi_kerja_id', $sesi)
                ->select('sesi_kerja_id', DB::raw('MIN(DATE(created_at)) as min_tgl'), DB::raw('COUNT(DISTINCT DATE(created_at)) as jml_hari'))
                ->groupBy('sesi_kerja_id')
                ->get()
                ->keyBy('sesi_kerja_id');

            $multi = $scanDates->filter(fn ($r) => $r->jml_hari > 1)->count();

            $count = 0;
            foreach ($sesi as $id) {
                $tanggal = isset($scanDates[$id])
                    ? $scanDates[$id]->min_tgl
                    : substr((string) DB::table('sesi_kerja')->where('id', $id)->value('created_at'), 0, 10);

                if ($tanggal) {
                    DB::table('sesi_kerja')->where('id', $id)->update(['tanggal_masuk' => $tanggal]);
                    $count++;
                }
            }

            return $count;
        });

        $this->info("Sesi di-backfill: {$updated}");
        $this->warn("Sesi multi-hari (perlu cek manual): {$multi}");

        return self::SUCCESS;
    }
}
