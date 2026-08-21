<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kualitas;
use App\Models\Produk;
use App\Models\Warna;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanKualitasController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);

        $jenis = $request->jenis;
        $jenisLabel = match ($jenis) {
            'body' => 'Body',
            'tangki' => 'Tangki',
            default => null,
        };

        $lastDay = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');

        $kualitasList = Kualitas::orderBy('id')->get(['id', 'kualitas']);
        $warnaList = Warna::orderBy('id')->get(['id', 'warna']);

        $base = fn ($col) => Produk::query()
            ->whereNotNull($col)
            ->whereMonth('updated_at', $bulan)
            ->whereYear('updated_at', $tahun)
            ->when($jenisLabel, fn ($q) => $q->where('jenis', $jenisLabel));

        $kualitasRows = $base('kualitas_id')
            ->select(DB::raw('DATE(updated_at) as tanggal'), 'kualitas_id', DB::raw('COUNT(*) as jml'))
            ->groupBy('tanggal', 'kualitas_id')
            ->get()
            ->keyBy(fn ($r) => $r->tanggal . '-' . $r->kualitas_id);

        $warnaRows = $base('warna_id')
            ->select(DB::raw('DATE(updated_at) as tanggal'), 'warna_id', DB::raw('COUNT(*) as jml'))
            ->groupBy('tanggal', 'warna_id')
            ->get()
            ->keyBy(fn ($r) => $r->tanggal . '-' . $r->warna_id);

        $rowsKualitas = [];
        $rowsWarna = [];
        $sumKualitas = $kualitasList->pluck('kualitas')->flip()->map(fn () => 0)->all();
        $sumWarna = $warnaList->pluck('warna')->flip()->map(fn () => 0)->all();

        for ($day = 1; $day <= $lastDay; $day++) {
            $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
            $rk = ['tanggal' => $day];
            $rw = ['tanggal' => $day];

            foreach ($kualitasList as $k) {
                $v = (int) ($kualitasRows[$tanggal . '-' . $k->id]->jml ?? 0);
                $rk[$k->kualitas] = $v;
                $sumKualitas[$k->kualitas] += $v;
            }
            foreach ($warnaList as $w) {
                $v = (int) ($warnaRows[$tanggal . '-' . $w->id]->jml ?? 0);
                $rw[$w->warna] = $v;
                $sumWarna[$w->warna] += $v;
            }

            $rowsKualitas[] = $rk;
            $rowsWarna[] = $rw;
        }

        $minYear = (int) (Produk::whereNotNull('kualitas_id')
            ->when($jenisLabel, fn ($q) => $q->where('jenis', $jenisLabel))
            ->min(DB::raw('YEAR(updated_at)')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('LaporanKualitas/Index', [
            'rowsKualitas' => $rowsKualitas,
            'rowsWarna' => $rowsWarna,
            'kualitasList' => $kualitasList->pluck('kualitas')->all(),
            'warnaList' => $warnaList->pluck('warna')->all(),
            'summaryKualitas' => $sumKualitas,
            'summaryWarna' => $sumWarna,
            'filter' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'jenis' => $jenisLabel,
                'daftar_bulan' => [
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ],
                'daftar_tahun' => $daftarTahun,
            ],
        ]);
    }
}
