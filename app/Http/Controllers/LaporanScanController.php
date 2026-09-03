<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesiKerja;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Support\CutOff;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanScanController extends Controller
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

        // Daftar proses aktif (urutkan by urutan)
        $prosesList = Proses::where('is_active', true)
            ->orderBy('urutan', 'asc')
            ->get(['id', 'proses']);

        // Hari terakhir di bulan tsb
        $lastDay = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');

        // 1. Actual per (tanggal_cutoff, proses_id)
        [$start, $end] = CutOff::rangeBulan($bulan, $tahun);

        $actualRows = PengerjaanProduk::query()
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $end)
            ->when($jenisLabel, fn ($q) => $q->whereHas('sesiKerja', fn ($q2) => $q2->where('jenis', $jenisLabel)))
            ->select(
                DB::raw('DATE(' . CutOff::expr('created_at') . ') as tanggal'),
                'proses_id',
                DB::raw('COUNT(DISTINCT produk_id, proses_id) as actual')
            )
            ->groupBy('tanggal', 'proses_id')
            ->get()
            ->keyBy(function ($row) {
                return $row->tanggal . '-' . $row->proses_id;
            });

        // 2. Target per (tanggal_masuk, proses_id)
        $targetRows = SesiKerja::query()
            ->whereMonth('tanggal_masuk', $bulan)
            ->whereYear('tanggal_masuk', $tahun)
            ->whereNotNull('target')
            ->when($jenisLabel, fn ($q) => $q->where('jenis', $jenisLabel))
            ->select(
                'tanggal_masuk',
                'proses_id',
                DB::raw('sum(target) as target')
            )
            ->groupBy('tanggal_masuk', 'proses_id')
            ->get()
            ->keyBy(function ($row) {
                return $row->tanggal_masuk . '-' . $row->proses_id;
            });

        // Build rows per tanggal
        $rows = [];
        for ($day = 1; $day <= $lastDay; $day++) {
            $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
            $row = ['tanggal' => $day];
            
            foreach ($prosesList as $p) {
                $key = $tanggal . '-' . $p->id;
                $actual = $actualRows[$key]->actual ?? 0;
                $target = $targetRows[$key]->target ?? 0;
                
                $row[$p->proses] = [
                    'actual' => (int) $actual,
                    'target' => (int) $target,
                ];
            }
            $rows[] = $row;
        }

        // Summary rows
        $capaian = ['label' => 'Capaian'];
        $totalTarget = ['label' => 'Total Target'];
        $capaianPersen = ['label' => 'Capaian %'];

        foreach ($prosesList as $p) {
            $sumActual = 0;
            $sumTarget = 0;
            foreach ($rows as $r) {
                $sumActual += $r[$p->proses]['actual'];
                $sumTarget += $r[$p->proses]['target'];
            }
            $capaian[$p->proses] = $sumActual;
            $totalTarget[$p->proses] = $sumTarget;
            $capaianPersen[$p->proses] = $sumTarget > 0 ? round($sumActual / $sumTarget * 100, 2) . '%' : '0%';
        }

        // Tahun list untuk dropdown
        $minYearQuery = PengerjaanProduk::query()
            ->when($jenisLabel, fn ($q) => $q->whereHas('sesiKerja', fn ($q2) => $q2->where('jenis', $jenisLabel)));
        $minYear = (int) ($minYearQuery->min(DB::raw('YEAR(' . CutOff::expr('created_at') . ')')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('LaporanScan/Index', [
            'rows' => $rows,
            'prosesList' => $prosesList->pluck('proses')->all(),
            'summary' => [
                'capaian' => $capaian,
                'totalTarget' => $totalTarget,
                'capaianPersen' => $capaianPersen,
            ],
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