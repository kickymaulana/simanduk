<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesiKerja;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanScanPerbulanController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);

        // Daftar proses aktif (urutkan by urutan)
        $prosesList = Proses::where('is_active', true)
            ->orderBy('urutan', 'asc')
            ->get(['id', 'proses']);

        // Hari terakhir di bulan tsb
        $lastDay = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');

        // 1. Actual per (tanggal, proses_id)
        $actualRows = PengerjaanProduk::query()
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                'proses_id',
                DB::raw('count(*) as actual')
            )
            ->groupBy('tanggal', 'proses_id')
            ->get()
            ->keyBy(function ($row) {
                return $row->tanggal . '-' . $row->proses_id;
            });

        // 2. Target per (tanggal, proses_id)
        $targetRows = SesiKerja::query()
            ->whereMonth('created_at', $bulan)
            ->whereYear('created_at', $tahun)
            ->whereNotNull('target')
            ->select(
                DB::raw('DATE(created_at) as tanggal'),
                'proses_id',
                DB::raw('sum(target) as target')
            )
            ->groupBy('tanggal', 'proses_id')
            ->get()
            ->keyBy(function ($row) {
                return $row->tanggal . '-' . $row->proses_id;
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
        $minYear = (int) (PengerjaanProduk::min(DB::raw('YEAR(created_at)')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('LaporanScanPerbulan/Index', [
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