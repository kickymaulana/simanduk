<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProdukController extends Controller
{
    public function fixJenis(Request $request, Produk $produk)
    {
        $validated = $request->validate([
            'jenis' => 'required|in:Body,Tangki',
        ]);

        $produk->update(['jenis' => $validated['jenis']]);

        return back()->with('success', "Jenis produk {$produk->qrcode} diubah menjadi {$validated['jenis']}.");
    }
    public function show($id)
    {
        $produk = Produk::with([
            'pengerjaan_produks' => function($query) {
                $query->with(['proses', 'user', 'pengerjaan_cacats.cacat'])
                    ->orderBy('created_at', 'desc'); // History terbaru di atas
            },
            'proses'
        ])->findOrFail($id);

        return Inertia::render('Produk/Show', [
            'produk' => $produk,
            'backUrl' => url()->previous() !== url()->current()
                     ? url()->previous()
                     : route('produk.index'),
        ]);
    }


    public function dataprodukindex(Request $request)
    {
        $search = $request->search;

        $query = Produk::query()
            ->with(['proses'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                // 1. Cari berdasarkan QR Code Produk (Scan Langsung)
                $q->where('qrcode', 'like', "%{$search}%")
                // 2. Cari berdasarkan Nama Produk
                ->orWhere('nama', 'like', "%{$search}%")
                // 3. Cari berdasarkan nama proses
                ->orWhereHas('proses', function ($tq) use ($search) {
                    $tq->where('proses', 'like', "%{$search}%");
                });
            });
        }

        $produks = $query->paginate(15)->withQueryString();

        return Inertia::render('Produk/Index', [
            'produks' => $produks,
            'filters' => $request->only(['search']),
        ]);
    }

}