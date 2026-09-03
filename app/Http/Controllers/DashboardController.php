<?php

namespace App\Http\Controllers;

use App\Models\Cacat;
use App\Models\PengerjaanProduk;
use App\Models\PengerjaanCacat;
use App\Support\CutOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    private const REJECT = ['Buang', 'In Proses'];

    private function prosesQcIds(): array
    {
        return DB::table('proses')
            ->where('departemen_id', function ($q) {
                $q->select('id')->from('departemen')->where('departemen', 'QC');
            })
            ->pluck('id')
            ->toArray();
    }

    public function index(Request $request)
    {
        $now = now();
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);
        $prosesQcIds = $this->prosesQcIds();

        // --- Filter Waktu (cut off produksi) ---
        [$start, $end] = CutOff::rangeBulan($bulan, $tahun);
        $monthScope = fn ($q) => $q->where('created_at', '>=', $start)->where('created_at', '<', $end);

        // 1. Reject Summary Bulanan (exclude proses QC)
        $totalBulan = (clone $monthScope(PengerjaanProduk::query()))->whereNotIn('proses_id', $prosesQcIds)->count();
        $ok = (clone $monthScope(PengerjaanProduk::query()))->whereNotIn('proses_id', $prosesQcIds)->where('status_kondisi', 'OK')->count();
        $buang = (clone $monthScope(PengerjaanProduk::query()))->whereNotIn('proses_id', $prosesQcIds)->where('status_kondisi', 'Buang')->count();
        $inProses = (clone $monthScope(PengerjaanProduk::query()))->whereNotIn('proses_id', $prosesQcIds)->where('status_kondisi', 'In Proses')->count();
        $rejectTotal = $buang + $inProses;
        $persenReject = $totalBulan ? round($rejectTotal / $totalBulan * 100, 1) : 0;

        $rejectSummary = [
            'total' => $totalBulan,
            'ok' => $ok,
            'buang' => $buang,
            'in_proses' => $inProses,
            'reject' => $rejectTotal,
            'persen_reject' => $persenReject,
        ];

        // 1b. Pareto Top 10 Jenis Reject (exclude proses QC)
        $paretoRows = PengerjaanCacat::query()
            ->join('pengerjaan_produk', 'pengerjaan_cacat.pengerjaan_produk_id', '=', 'pengerjaan_produk.id')
            ->whereIn('pengerjaan_produk.status_kondisi', self::REJECT)
            ->where('pengerjaan_produk.created_at', '>=', $start)
            ->where('pengerjaan_produk.created_at', '<', $end)
            ->whereNotIn('pengerjaan_produk.proses_id', $prosesQcIds)
            ->select('pengerjaan_cacat.cacat_id', DB::raw('count(*) as total'))
            ->groupBy('pengerjaan_cacat.cacat_id')
            ->orderByDesc('total')
            ->take(10)
            ->get();

        $cacatMap = Cacat::whereIn('id', $paretoRows->pluck('cacat_id')->toArray())
            ->pluck('cacat', 'id');
        $denominator = max($rejectTotal, 1);
        $cumulative = 0;
        $paretoCacat = $paretoRows->map(function ($row) use ($cacatMap, $denominator, &$cumulative) {
            $cumulative += $row->total;
            return [
                'nama' => $cacatMap[$row->cacat_id] ?? 'Lainnya',
                'total' => $row->total,
                'persen' => round($row->total / $denominator * 100, 1),
                'kumulatif' => round($cumulative / $denominator * 100, 1),
            ];
        })->values()->all();

        // 2. Top Operator Reject (exclude proses QC)
        $topOperatorReject = (clone $monthScope(PengerjaanProduk::query()))
            ->with('user.departemen')
            ->select('user_id', DB::raw('count(*) as total_reject'))
            ->whereIn('status_kondisi', self::REJECT)
            ->whereNotIn('proses_id', $prosesQcIds)
            ->groupBy('user_id')
            ->orderByDesc('total_reject')
            ->take(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->user?->name ?? 'Unknown',
                    'departemen' => $item->user?->departemen?->departemen ?? '-',
                    'total' => $item->total_reject,
                ];
            })->values()->all();

        // 3. Total Output (Trend 12 Bulan Tahun Terpilih) - TETAP SEMUA PROSES
        $jam = CutOff::jam();
        $trendStart = now()->create($tahun, 1, 1, 0, 0, 0)->addHours($jam);
        $trendEnd = now()->create($tahun + 1, 1, 1, 0, 0, 0)->addHours($jam);

        $trendRows = (clone PengerjaanProduk::query())
            ->where('created_at', '>=', $trendStart)
            ->where('created_at', '<', $trendEnd)
            ->select(DB::raw('MONTH(' . CutOff::expr('created_at') . ') as m'), DB::raw('count(*) as total'))
            ->groupBy(DB::raw('MONTH(' . CutOff::expr('created_at') . ')'))
            ->get()
            ->pluck('total', 'm')
            ->toArray();

        $outputTrend = collect(range(1, 12))->map(function ($m) use ($trendRows, $bulan) {
            return [
                'bulan' => self::BULAN[$m],
                'total' => $trendRows[$m] ?? 0,
                'aktif' => $m === (int) $bulan,
            ];
        })->values()->all();

        // 4. % Reject per Departemen (exclude departemen QC)
        $rejectByDepartemen = DB::table('pengerjaan_produk')
            ->join('users', 'users.id', '=', 'pengerjaan_produk.user_id')
            ->leftJoin('departemen', 'departemen.id', '=', 'users.departemen_id')
            ->where('pengerjaan_produk.created_at', '>=', $start)
            ->where('pengerjaan_produk.created_at', '<', $end)
            ->whereNotIn('pengerjaan_produk.proses_id', $prosesQcIds)
            ->where(function ($q) {
                $q->whereNull('departemen.id')
                  ->orWhere('departemen.departemen', '!=', 'QC');
            })
            ->select(
                DB::raw('COALESCE(departemen.departemen, "Tanpa Departemen") as nama'),
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN pengerjaan_produk.status_kondisi IN ('Buang','In Proses') THEN 1 ELSE 0 END) as reject")
            )
            ->groupBy('departemen.departemen')
            ->orderByDesc('reject')
            ->get()
            ->map(function ($row) {
                $total = (int) $row->total;
                $reject = (int) $row->reject;
                return [
                    'nama' => $row->nama,
                    'total' => $total,
                    'reject' => $reject,
                    'persen' => $total ? round($reject / $total * 100, 1) : 0,
                ];
            })->values()->all();

        // 4b. % Reject per Operator (exclude proses QC)
        $opRows = (clone $monthScope(PengerjaanProduk::query()))
            ->with('user.departemen')
            ->select(
                'user_id',
                DB::raw('count(*) as total'),
                DB::raw("SUM(CASE WHEN status_kondisi IN ('Buang','In Proses') THEN 1 ELSE 0 END) as reject")
            )
            ->whereNotIn('proses_id', $prosesQcIds)
            ->groupBy('user_id')
            ->having('total', '>', 0)
            ->get();

        $rejectByOperator = $opRows->map(function ($item) {
            $total = (int) $item->total;
            $reject = (int) $item->reject;
            return [
                'name' => $item->user?->name ?? 'Unknown',
                'departemen' => $item->user?->departemen?->departemen ?? '-',
                'total' => $total,
                'reject' => $reject,
                'persen' => $total ? round($reject / $total * 100, 1) : 0,
            ];
        })->sortByDesc('persen')->values()->all();

        // Filter options
        $minYear = (int) (PengerjaanProduk::min(DB::raw('YEAR(' . CutOff::expr('created_at') . ')')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('Dashboard/Index', [
            'filter' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'daftar_bulan' => collect(self::BULAN)->map(fn ($label, $v) => [
                    'value' => $v, 'label' => $label,
                ])->values()->all(),
                'daftar_tahun' => $daftarTahun,
            ],
            'reports' => [
                'rejectSummary' => $rejectSummary,
                'paretoCacat' => $paretoCacat,
                'topOperatorReject' => $topOperatorReject,
                'outputTrend' => $outputTrend,
                'rejectByDepartemen' => $rejectByDepartemen,
                'rejectByOperator' => $rejectByOperator,
            ],
        ]);
    }
}
