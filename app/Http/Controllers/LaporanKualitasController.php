<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kualitas;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LaporanKualitasController extends Controller
{
    private const PROSES_QC_VISUAL = 'QC Visual & Dimensi';

    public function index(Request $request)
    {
        $now = now();
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);

        $lastDay = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');

        $kualitas = Kualitas::all(['id', 'kualitas']);
        $idFG = $kualitas->firstWhere('kualitas', 'FG(EXPORT)')?->id;
        $idAB = $kualitas->firstWhere('kualitas', 'AB (DYNA / RAPTOR)')?->id;
        $idSG = $kualitas->firstWhere('kualitas', 'SG (HIU)')?->id;

        $qcProses = Proses::where('proses', self::PROSES_QC_VISUAL)->first();

        $rows = [];
        $summary = ['input' => 0, 'fg' => 0, 'ab' => 0, 'sg' => 0, 'reject' => 0];

        if ($qcProses) {
            $grouped = PengerjaanProduk::query()
                ->where('pengerjaan_produk.proses_id', $qcProses->id)
                ->join('sesi_kerja', 'sesi_kerja.id', '=', 'pengerjaan_produk.sesi_kerja_id')
                ->whereNotNull('sesi_kerja.tanggal_masuk')
                ->whereMonth('sesi_kerja.tanggal_masuk', $bulan)
                ->whereYear('sesi_kerja.tanggal_masuk', $tahun)
                ->join('produk', 'produk.id', '=', 'pengerjaan_produk.produk_id')
                ->select(
                    DB::raw('DATE(sesi_kerja.tanggal_masuk) as tanggal'),
                    'produk.jenis',
                    'pengerjaan_produk.status_kondisi',
                    'produk.kualitas_id',
                    DB::raw('COUNT(DISTINCT pengerjaan_produk.produk_id) as jml')
                )
                ->groupBy('tanggal', 'produk.jenis', 'pengerjaan_produk.status_kondisi', 'produk.kualitas_id')
                ->get();

            $raw = [];
            foreach ($grouped as $g) {
                $raw[$g->tanggal][$g->jenis][$g->status_kondisi][$g->kualitas_id] = (int) $g->jml;
            }

            foreach (['Body', 'Tangki'] as $jenis) {
                for ($day = 1; $day <= $lastDay; $day++) {
                    $tanggal = sprintf('%04d-%02d-%02d', $tahun, $bulan, $day);
                    $cell = $raw[$tanggal][$jenis] ?? [];

                    $input = array_sum(array_map(fn ($s) => array_sum($s), $cell));
                    if ($input === 0) {
                        continue;
                    }

                    $fg = (int) ($cell['OK'][$idFG] ?? 0);
                    $ab = (int) ($cell['OK'][$idAB] ?? 0);
                    $sg = (int) ($cell['OK'][$idSG] ?? 0);
                    $reject = (int) array_sum($cell['Buang'] ?? []);

                    $rows[] = [
                        'tanggal' => $day,
                        'jenis' => $jenis,
                        'input' => $input,
                        'fg' => $fg,
                        'ab' => $ab,
                        'sg' => $sg,
                        'reject' => $reject,
                        'fg_persen' => $this->persen($fg, $input),
                        'ab_persen' => $this->persen($ab, $input),
                        'sg_persen' => $this->persen($sg, $input),
                        'reject_persen' => $this->persen($reject, $input),
                    ];

                    $summary['input'] += $input;
                    $summary['fg'] += $fg;
                    $summary['ab'] += $ab;
                    $summary['sg'] += $sg;
                    $summary['reject'] += $reject;
                }
            }
        }

        $summary['fg_persen'] = $this->persen($summary['fg'], $summary['input']);
        $summary['ab_persen'] = $this->persen($summary['ab'], $summary['input']);
        $summary['sg_persen'] = $this->persen($summary['sg'], $summary['input']);
        $summary['reject_persen'] = $this->persen($summary['reject'], $summary['input']);

        $minYear = (int) (PengerjaanProduk::join('sesi_kerja', 'sesi_kerja.id', '=', 'pengerjaan_produk.sesi_kerja_id')
            ->where('pengerjaan_produk.proses_id', $qcProses->id ?? 0)
            ->whereNotNull('sesi_kerja.tanggal_masuk')
            ->min(DB::raw('YEAR(sesi_kerja.tanggal_masuk)')) ?? $now->year);
        $daftarTahun = collect(range($minYear, $now->year))->reverse()->values()->all();

        return Inertia::render('LaporanKualitas/Index', [
            'rows' => $rows,
            'summary' => $summary,
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

    private function persen($angka, $total): string
    {
        return $total > 0 ? round($angka / $total * 100, 1) . '%' : '0%';
    }
}
