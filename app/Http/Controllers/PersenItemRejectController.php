<?php

namespace App\Http\Controllers;

use App\Models\PengerjaanCacat;
use App\Models\Proses;
use App\Support\CutOff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PersenItemRejectController extends Controller
{
    private const PROSES_QC_VISUAL = 'QC Visual & Dimensi';

    public function index(Request $request)
    {
        $today = now()->toDateString();
        $startDate = $request->date('mulai')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $endDate = $request->date('sampai')?->toDateString() ?? $today;
        $start = Carbon::parse($startDate)->startOfDay()->addHours(CutOff::jam());
        $end = Carbon::parse($endDate)->addDay()->startOfDay()->addHours(CutOff::jam());
        $qcProses = Proses::where('proses', self::PROSES_QC_VISUAL)->first();

        $rows = collect();
        if ($qcProses) {
            $rows = PengerjaanCacat::query()
                ->join('pengerjaan_produk', 'pengerjaan_produk.id', '=', 'pengerjaan_cacat.pengerjaan_produk_id')
                ->join('cacat', 'cacat.id', '=', 'pengerjaan_cacat.cacat_id')
                ->where('pengerjaan_produk.proses_id', $qcProses->id)
                ->whereIn('pengerjaan_produk.status_kondisi', ['Buang', 'In Proses'])
                ->where('pengerjaan_cacat.created_at', '>=', $start)
                ->where('pengerjaan_cacat.created_at', '<', $end)
                ->select('cacat.cacat as item_reject')
                ->selectRaw("SUM(CASE WHEN pengerjaan_produk.status_kondisi = 'Buang' THEN 1 ELSE 0 END) as buang")
                ->selectRaw("SUM(CASE WHEN pengerjaan_produk.status_kondisi = 'In Proses' THEN 1 ELSE 0 END) as in_proses")
                ->groupBy('cacat.id', 'cacat.cacat')
                ->orderByDesc(DB::raw('COUNT(*)'))
                ->get();
        }

        $totalBuang = (int) $rows->sum('buang');
        $totalInProses = (int) $rows->sum('in_proses');
        $rows = $rows->map(fn ($row) => [
            'item_reject' => $row->item_reject,
            'buang' => (int) $row->buang,
            'buang_persen' => $this->persen((int) $row->buang, $totalBuang),
            'in_proses' => (int) $row->in_proses,
            'in_proses_persen' => $this->persen((int) $row->in_proses, $totalInProses),
            'total' => (int) $row->buang + (int) $row->in_proses,
        ])->values();

        return Inertia::render('PersenItemReject/Index', [
            'rows' => $rows,
            'summary' => [
                'buang' => $totalBuang,
                'in_proses' => $totalInProses,
                'total' => $totalBuang + $totalInProses,
            ],
            'filter' => [
                'mulai' => $startDate,
                'sampai' => $endDate,
                'cut_off_jam' => CutOff::jam(),
            ],
        ]);
    }

    private function persen(int $jumlah, int $total): string
    {
        return $total > 0 ? round($jumlah / $total * 100, 1).'%' : '0%';
    }
}
