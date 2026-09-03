<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use App\Models\Proses;
use App\Support\CutOff;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanProdukBuangController extends Controller
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

        $prosesList = Proses::where('is_active', true)
            ->orderBy('urutan', 'asc')
            ->get(['id', 'proses']);

        $lastDay = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');

        [$start, $end] = CutOff::rangeBulan($bulan, $tahun);

        $buangRows = Produk::query()
            ->where('status_akhir', 'Buang')
            ->where('updated_at', '>=', $start)
            ->where('updated_at', '<', $end)
            ->when($jenisLabel, fn ($q) => $q->where('jenis', $jenisLabel))
            ->select(DB::raw('DATE(' . CutOff::expr('updated_at') . ') as tanggal'), 'proses_id', DB::raw('COUNT(*) as jml'))
            ->groupBy('tanggal', 'proses_id')
            ->get()
            ->keyBy(fn ($r) => $r->tanggal . '-' . $r->proses_id);

        $rows = [];
        for ($day = 1; $day <= $lastDay; $day++) {
            $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
            $row = ['tanggal' => $day];

            foreach ($prosesList as $p) {
                $row[$p->proses] = (int) ($buangRows[$tanggal . '-' . $p->id]->jml ?? 0);
            }
            $rows[] = $row;
        }

        $total = ['label' => 'Total'];
        foreach ($prosesList as $p) {
            $sum = 0;
            foreach ($rows as $r) {
                $sum += $r[$p->proses];
            }
            $total[$p->proses] = $sum;
        }

        $minYear = (int) (Produk::where('status_akhir', 'Buang')
            ->when($jenisLabel, fn ($q) => $q->where('jenis', $jenisLabel))
            ->min(DB::raw('YEAR(' . CutOff::expr('updated_at') . ')')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('LaporanProdukBuang/Index', [
            'rows' => $rows,
            'prosesList' => $prosesList->pluck('proses')->all(),
            'total' => $total,
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
