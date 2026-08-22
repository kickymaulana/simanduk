<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PengerjaanCacat;
use Inertia\Inertia;

class TemuanRejectQcController extends Controller
{
    public function index(Request $request)
    {
        $logs = PengerjaanCacat::query()
            ->whereHas('user_scan', fn ($q) => $q->whereHas('departemen', fn ($d) => $d->where('departemen', 'QC')))
            ->with([
                'cacat',
                'pengerjaan_produk.produk.kualitas',
                'pengerjaan_produk.produk.warna',
                'user_scan', // Pastikan relasi ini ada di model PengerjaanCacat
                'user_pj', // Pastikan relasi ini ada di model PengerjaanCacat
                'proses_pj', // Pastikan relasi ini ada di model PengerjaanCacat
                'proses_scan' // Pastikan relasi ini ada di model PengerjaanCacat
            ])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('cacat', function ($q) use ($search) {
                    $q->where('cacat', 'like', "%{$search}%");
                })->orWhereHas('pengerjaan_produk.produk', function ($q) use ($search) {
                    $q->where('qrcode', 'like', "%{$search}%"); // Sesuaikan kolom nama produk
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn ($log) => [
            // Ambil ID Produk lewat pengerjaan_produk
            'id' => $log->pengerjaan_produk->produk->id ?? null,
            'id_pengerjaan_cacat' => $log->id ?? null,

            // Data lainnya
            'id_pengerjaan_cacat' => $log->id,
            'cacat' => $log->cacat,
            'pengerjaan_produk' => $log->pengerjaan_produk,
            'user_scan' => $log->user_scan,
            'user_pj' => $log->user_pj,
            'proses_pj' => $log->proses_pj,
            'proses_scan' => $log->proses_scan,
            'created_at' => $log->created_at->translatedFormat('d M Y, H:i'),
        ]);

        return Inertia::render('TemuanRejectQc/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search'])
        ]);
    }
}
