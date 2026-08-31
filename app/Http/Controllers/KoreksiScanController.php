<?php

namespace App\Http\Controllers;

use App\Models\PengerjaanCacat;
use App\Models\PengerjaanProduk;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KoreksiScanController extends Controller
{
    public function index()
    {
        return Inertia::render('KoreksiScan/Index');
    }

    public function cari(Request $request)
    {
        $request->validate(['qr' => 'required|string']);

        $produk = Produk::with([
            'proses',
            'pengerjaan_produks.proses',
            'pengerjaan_produks.user',
            'pengerjaan_produks.pengerjaan_cacats.cacat',
        ])->where('qrcode', strtoupper(trim($request->qr)))->first();

        if (! $produk) {
            return response()->json(['message' => "Produk {$request->qr} tidak ditemukan!"], 404);
        }

        return response()->json($this->payload($produk));
    }

    public function batalkan(Request $request, Produk $produk)
    {
        $last = $produk->pengerjaan_produks()->latest('id')->first();

        if (! $last) {
            return response()->json(['message' => 'Produk ini belum punya histori scan.'], 422);
        }

        if ($produk->pengerjaan_produks()->count() <= 1) {
            return response()->json(['message' => 'Hanya bisa batalkan scan terakhir. Minimal harus ada 2 histori scan.'], 422);
        }

        try {
            DB::transaction(function () use ($produk, $last) {
                $batch = PengerjaanProduk::where('produk_id', $produk->id)
                    ->where('sesi_kerja_id', $last->sesi_kerja_id)
                    ->where('proses_id', $last->proses_id)
                    ->where('created_at', $last->created_at)
                    ->pluck('id');

                PengerjaanCacat::whereIn('pengerjaan_produk_id', $batch)->delete();

                PengerjaanProduk::whereIn('id', $batch)->delete();

                $prev = $produk->pengerjaan_produks()->latest('id')->first();

                $produk->update([
                    'status_akhir' => $prev ? $prev->status_kondisi : 'OK',
                    'proses_id'    => $prev ? $prev->proses_id : null,
                    'sudah_scan'   => $prev ? 'Sudah' : 'Belum',
                    'kualitas_id'  => null,
                    'warna_id'     => null,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membatalkan scan: ' . $e->getMessage()], 500);
        }

        return response()->json($this->payload($produk->fresh()));
    }

    private function payload(Produk $produk): array
    {
        $produk->load([
            'proses',
            'pengerjaan_produks.proses',
            'pengerjaan_produks.user',
            'pengerjaan_produks.pengerjaan_cacats.cacat',
        ]);

        return ['produk' => $produk];
    }
}