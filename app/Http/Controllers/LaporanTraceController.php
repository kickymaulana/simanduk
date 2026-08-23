<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Produk;
use Inertia\Inertia;

class LaporanTraceController extends Controller
{
    private const QC_VISUAL = 'QC Visual & Dimensi';
    private const SOLAR = 'Solar';
    private const CASTING = 'Casting';

    private function queryProduk(Request $request)
    {
        return Produk::query()
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
            ->when(in_array($request->jenis, ['Body', 'Tangki']), fn ($q) => $q->where('jenis', $request->jenis))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();
    }

    private function transformProduk(Produk $produk): array
    {
        $ops = [];
        $tglQcVisual = null;
        $waktuSolar = null;
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
            foreach ($pp->pengerjaan_cacats as $pc) {
                if ($pc->cacat?->cacat) {
                    $rejects[$pc->cacat->cacat] = true;
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
                'susun' => $produk->oven?->oven,
                'bongkar' => null,
                'temp' => null,
            ],
        ];
    }

    public function operator(Request $request)
    {
        $produks = $this->queryProduk($request)->through(fn ($p) => $this->transformProduk($p));

        return Inertia::render('LaporanTrace/Operator', [
            'produks' => $produks,
            'filters' => $request->only(['search', 'jenis']),
        ]);
    }

    public function mesin(Request $request)
    {
        $produks = $this->queryProduk($request)->through(fn ($p) => $this->transformProduk($p));

        return Inertia::render('LaporanTrace/Mesin', [
            'produks' => $produks,
            'filters' => $request->only(['search', 'jenis']),
        ]);
    }
}
