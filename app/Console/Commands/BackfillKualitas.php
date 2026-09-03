<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Support\Facades\DB;

#[Signature('pengerjaan:backfill-kualitas')]
#[Description('Isi kualitas_id kosong pada pengerjaan_produk dari produk.kualitas_id (snapshot lama; data baru terisi otomatis saat scan)')]
class BackfillKualitas extends Command
{
    public function handle(): int
    {
        $count = 0;

        $pengerjaan = DB::table('pengerjaan_produk')
            ->join('produk', 'produk.id', '=', 'pengerjaan_produk.produk_id')
            ->whereNull('pengerjaan_produk.kualitas_id')
            ->whereNotNull('produk.kualitas_id')
            ->select('pengerjaan_produk.id', 'produk.kualitas_id');

        foreach ($pengerjaan->get() as $row) {
            DB::table('pengerjaan_produk')->where('id', $row->id)->update(['kualitas_id' => $row->kualitas_id]);
            $count++;
        }

        $this->info("Pengerjaan di-backfill kualitas: {$count}");

        return self::SUCCESS;
    }
}
