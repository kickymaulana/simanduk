<?php
namespace App\Http\Controllers;

use App\Models\Departemen;
use App\Models\Proses;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StokController extends Controller
{
    /**
     * Ringkasan stok per DEPARTEMEN.
     * Satu departemen bisa punya banyak proses → dikelompokkan.
     */
    public function index()
    {
        // Hitung jumlah produk per proses_id sekali saja (kecuali yang BUANG)
        $produkPerProses = Produk::selectRaw('proses_id, count(*) as total')
            ->where('status_akhir', '!=', 'Buang')
            ->groupBy('proses_id')
            ->pluck('total', 'proses_id');

        $semuaProses = Proses::where('is_active', true)->orderBy('urutan', 'asc')->get();
        $semuaDepartemen = Departemen::orderBy('id', 'asc')->get();

        $stok = $semuaDepartemen->map(function (Departemen $d) use ($semuaProses, $produkPerProses) {
            $prosesList = $semuaProses
                ->where('departemen_id', $d->id)
                ->values()
                ->map(fn (Proses $p) => [
                    'id'           => $p->id,
                    'proses'       => $p->proses,
                    'urutan'       => $p->urutan,
                    'is_active'    => (bool) $p->is_active,
                    'total_produk' => (int) ($produkPerProses[$p->id] ?? 0),
                ]);

            return [
                'id'           => $d->id,
                'departemen'   => $d->departemen,
                'total_produk' => $prosesList->sum('total_produk'),
                'proses'       => $prosesList,
            ];
        })->filter(fn ($d) => $d['proses']->isNotEmpty())->values();

        return Inertia::render('Stok/Index', [
            'stok' => $stok,
        ]);
    }
}
