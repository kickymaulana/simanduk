<?php

namespace App\Http\Controllers;

use App\Models\PengerjaanProduk;
use App\Models\Produk;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LaporanTraceController extends Controller
{
    private const QC_VISUAL = 'QC Visual & Dimensi';

    private const SOLAR = 'Solar';

    private const CASTING = 'Casting';

    private const OVEN_SUSUN = 'Oven Susun';

    private const OVEN_BONGKAR = 'Oven Bongkar';

    private function queryProduk(Request $request, bool $urutQcVisual = false)
    {
        $query = Produk::query()
            ->with([
                'kualitas:id,kualitas',
                'warna:id,warna',
                'oven:id,oven',
                'pengerjaan_produks' => fn ($q) => $q->with([
                    'sesiKerja.leader:id,name',
                    'sesiKerja.shift:id,shift',
                    'proses:id,proses',
                    'pengerjaan_cacats.cacat:id,cacat',
                ])->latest('id'),
            ])
            ->when($request->search, fn ($q, $s) => $q->where('qrcode', 'like', "%{$s}%"))
            ->when(in_array($request->jenis, ['Body', 'Tangki']), fn ($q) => $q->where('jenis', $request->jenis));

        if ($urutQcVisual) {
            $filters = $request->validate([
                'qc_order' => ['nullable', 'in:asc,desc'],
                'qc_dari' => ['nullable', 'date_format:Y-m-d'],
                'qc_sampai' => ['nullable', 'date_format:Y-m-d', ...($request->filled('qc_dari') ? ['after_or_equal:qc_dari'] : [])],
            ]);
            $tanggalQc = PengerjaanProduk::query()
                ->select('pengerjaan_produk.created_at')
                ->whereColumn('pengerjaan_produk.produk_id', 'produk.id')
                ->whereHas('proses', fn ($q) => $q->where('proses', self::QC_VISUAL))
                ->latest('pengerjaan_produk.id')
                ->limit(1);

            $query->addSelect(['tanggal_qc_visual' => clone $tanggalQc]);

            if (! empty($filters['qc_dari'])) {
                $query->where(clone $tanggalQc, '>=', Carbon::parse($filters['qc_dari'])->startOfDay());
            }
            if (! empty($filters['qc_sampai'])) {
                $query->where(clone $tanggalQc, '<', Carbon::parse($filters['qc_sampai'])->addDay()->startOfDay());
            }

            $query->orderByRaw('tanggal_qc_visual IS NULL')
                ->orderBy('tanggal_qc_visual', $filters['qc_order'] ?? 'desc')
                ->orderByDesc('produk.id');
        } else {
            $query->latest('updated_at');
        }

        return $query->paginate(15)->withQueryString();
    }

    private function transformProduk(Produk $produk): array
    {
        $ops = [];
        $tglQcVisual = null;
        $waktuSolar = null;
        $waktuOvenSusun = null;
        $waktuOvenBongkar = null;
        $shiftCasting = null;
        $rejects = [];

        foreach ($produk->pengerjaan_produks as $pp) {
            $prosesName = $pp->proses?->proses;
            $leaderName = $pp->sesiKerja?->leader?->name;

            if ($prosesName && $leaderName) {
                $ops[$prosesName] = $leaderName;
            }
            if ($prosesName === self::QC_VISUAL && ! $tglQcVisual) {
                $tglQcVisual = $pp->created_at;
            }
            if ($prosesName === self::SOLAR && ! $waktuSolar) {
                $waktuSolar = $pp->created_at;
            }
            if ($prosesName === self::CASTING && ! $shiftCasting) {
                $shiftCasting = $pp->sesiKerja?->shift?->shift;
            }
            if ($prosesName === self::OVEN_SUSUN && ! $waktuOvenSusun) {
                $waktuOvenSusun = $pp->created_at;
            }
            if ($prosesName === self::OVEN_BONGKAR && ! $waktuOvenBongkar) {
                $waktuOvenBongkar = $pp->created_at;
            }
            // Item reject hanya diambil dari proses QC Visual & Dimensi
            // dan hanya yang berstatus Buang (final), bukan toleransi (In Proses).
            if ($prosesName === self::QC_VISUAL && $pp->status_kondisi === 'Buang') {
                foreach ($pp->pengerjaan_cacats as $pc) {
                    if ($pc->cacat?->cacat) {
                        $rejects[$pc->cacat->cacat] = true;
                    }
                }
            }
        }

        $packingProses = $produk->jenis === 'Tangki' ? 'Packing Tangki Closet' : 'Packing Body Closet';

        $operatorCols = [
            'Opr Casting' => 'Casting',
            'Opr Rework Casting' => 'Rework Casting',
            'Opr Solar' => 'Solar',
            'Opr Spray' => 'Spray',
            'Opr QC Pre Oven' => 'QC Pre Oven',
            'Opr Oven Susun' => 'Oven Susun',
            'Opr Oven Bongkar' => 'Oven Bongkar',
            'Opr QC Visual' => self::QC_VISUAL,
            'Opr QC Bilas' => 'QC Bilas & Kebocoran',
            'Opr Packing' => $packingProses,
        ];

        $operator = [];
        foreach ($operatorCols as $label => $prosesName) {
            $operator[$label] = $ops[$prosesName] ?? null;
        }

        return [
            'id' => $produk->id,
            'qrcode' => $produk->qrcode,
            'jenis' => $produk->jenis,
            'grade' => $produk->kualitas?->kualitas,
            'item_reject' => array_keys($rejects),
            'tgl_qc_visual' => $tglQcVisual?->translatedFormat('d M Y, H:i'),
            'operator' => $operator,
            'casting' => [
                'mesin' => $produk->nomor_mesin,
                'mould' => $produk->nomor_mould,
                'slip' => $produk->asal_slip,
                'shift' => $shiftCasting,
            ],
            'solar' => ['waktu' => $waktuSolar?->translatedFormat('d M Y, H:i')],
            'spray' => [
                'mesin' => $produk->nomor_mesin,
                'warna' => $produk->warna?->warna,
                'visco' => null,
            ],
            'oven' => [
                'susun' => $waktuOvenSusun?->translatedFormat('d M, H:i'),
                'bongkar' => $waktuOvenBongkar?->translatedFormat('d M, H:i'),
                'temp' => null,
            ],
        ];
    }

    public function operator(Request $request)
    {
        $produks = $this->queryProduk($request, true)->through(fn ($p) => $this->transformProduk($p));

        return Inertia::render('LaporanTrace/Operator', [
            'produks' => $produks,
            'filters' => array_merge(['qc_order' => 'desc'], $request->only(['search', 'jenis', 'qc_order', 'qc_dari', 'qc_sampai'])),
        ]);
    }

    public function mesin(Request $request)
    {
        $produks = $this->queryProduk($request, true)->through(fn ($p) => $this->transformProduk($p));

        return Inertia::render('LaporanTrace/Mesin', [
            'produks' => $produks,
            'filters' => array_merge(['qc_order' => 'desc'], $request->only(['search', 'jenis', 'qc_order', 'qc_dari', 'qc_sampai'])),
        ]);
    }
}
