<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Support\Facades\DB;

#[Signature('produk:fix-jenis')]
#[Description('Perbaiki produk berlabel Body yang pengerjaan pertamanya ada di sesi Tangki → diubah jadi Tangki')]
class FixJenisProduk extends Command
{
    public function handle(): int
    {
        $ids = DB::table('produk as p')
            ->where('p.jenis', 'Body')
            ->whereExists(function ($q) {
                $q->selectRaw('1')
                    ->from('pengerjaan_produk as pp')
                    ->join('sesi_kerja as s', 's.id', '=', 'pp.sesi_kerja_id')
                    ->whereColumn('pp.produk_id', 'p.id')
                    ->where('s.jenis', 'Tangki')
                    ->whereRaw('pp.id = (SELECT MIN(pp2.id) FROM pengerjaan_produk pp2 WHERE pp2.produk_id = pp.produk_id)');
            })
            ->pluck('p.id');

        $count = DB::table('produk')
            ->whereIn('id', $ids)
            ->update(['jenis' => 'Tangki']);

        $this->info("Produk diubah jadi Tangki: {$count}");

        return self::SUCCESS;
    }
}
