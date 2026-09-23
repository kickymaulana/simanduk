<?php

namespace App\Http\Controllers;

use App\Models\Kualitas;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Support\CutOff;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanKerusakanQcController extends Controller
{
    public function index(Request $request)
    {
        $bulan = (int) ($request->input('bulan') ?: now()->month);
        $tahun = (int) ($request->input('tahun') ?: now()->year);
        $prosesId = $request->integer('proses_id') ?: null;
        $kualitasId = $request->input('kualitas_id', 'belum_ditentukan');
        $proses = Proses::query()->where('is_active', true)->whereHas('departemen', fn ($q) => $q->where('departemen', 'QC'))->orderBy('urutan')->get(['id', 'proses']);
        $kualitas = Kualitas::query()->orderBy('id')->get(['id', 'kualitas']);
        if (! $proses->contains('id', $prosesId)) {
            $prosesId = $proses->first()?->id;
        }
        if ($kualitasId !== 'belum_ditentukan' && ! $kualitas->contains('id', (int) $kualitasId)) {
            $kualitasId = 'belum_ditentukan';
        }
        $range = CutOff::rangeBulan($bulan, $tahun);
        $latest = PengerjaanProduk::query()
            ->join('sesi_kerja', 'sesi_kerja.id', '=', 'pengerjaan_produk.sesi_kerja_id')
            ->select('pengerjaan_produk.produk_id', DB::raw('MAX(pengerjaan_produk.id) as id'))
            ->whereColumn('pengerjaan_produk.user_id', 'sesi_kerja.leader_id')
            ->where('pengerjaan_produk.proses_id', $prosesId)
            ->where('pengerjaan_produk.created_at', '>=', $range[0])
            ->where('pengerjaan_produk.created_at', '<', $range[1])
            ->when($kualitasId === 'belum_ditentukan', fn ($q) => $q->whereNull('pengerjaan_produk.kualitas_id'), fn ($q) => $q->where('pengerjaan_produk.kualitas_id', $kualitasId))
            ->groupBy('pengerjaan_produk.produk_id');
        $output = DB::query()->fromSub(clone $latest, 'latest_output')->count();
        $cacat = DB::table('pengerjaan_cacat')
            ->joinSub($latest, 'latest', 'latest.id', '=', 'pengerjaan_cacat.pengerjaan_produk_id')
            ->join('cacat', 'cacat.id', '=', 'pengerjaan_cacat.cacat_id')
            ->select('cacat.cacat as nama', DB::raw('DAY(DATE_SUB(pengerjaan_cacat.created_at, INTERVAL '.CutOff::jam().' HOUR)) as hari'), DB::raw('COUNT(*) as jumlah'))
            ->groupBy('cacat.id', 'cacat.cacat', DB::raw('DAY(DATE_SUB(pengerjaan_cacat.created_at, INTERVAL '.CutOff::jam().' HOUR))'))
            ->orderBy('cacat.cacat')->get();
        $days = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $rows = $cacat->groupBy('nama')->map(fn ($items, $nama) => ['nama' => $nama, 'hari' => $items->pluck('jumlah', 'hari'), 'total' => (int) $items->sum('jumlah'), 'persentase' => $output ? round($items->sum('jumlah') / $output * 100, 2) : 0])->values();

        return Inertia::render('LaporanKerusakanQc/Index', ['proses' => $proses, 'kualitas' => $kualitas, 'rows' => $rows, 'output' => $output, 'bulan' => $bulan, 'tahun' => $tahun, 'proses_id' => $prosesId, 'kualitas_id' => $kualitasId, 'days' => $days]);
    }
}
