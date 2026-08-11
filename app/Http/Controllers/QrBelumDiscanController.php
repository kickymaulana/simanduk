<?php

namespace App\Http\Controllers;

use App\Models\Proses;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

class QrBelumDiscanController extends Controller
{
    /**
     * Rentang tanggal yang benar: awal hari untuk start, akhir hari untuk end.
     * Default: 7 hari terakhir hingga akhir hari ini.
     */
    private function dateRange(Request $request): array
    {
        return [
            $request->date_start
                ? Carbon::parse($request->date_start)->startOfDay()
                : now()->subDays(7)->startOfDay(),
            $request->date_end
                ? Carbon::parse($request->date_end)->endOfDay()
                : now()->endOfDay(),
        ];
    }

    private function prosesSebelumnya(?Proses $x): ?Proses
    {
        return $x ? Proses::where('urutan', '<', $x->urutan)->orderByDesc('urutan')->first() : null;
    }

    private function queryKandidat(Proses $x, Request $request)
    {
        $sebelum = $this->prosesSebelumnya($x);

        return Produk::query()
            ->where('proses_id', $sebelum?->id)
            ->where('status_akhir', '!=', 'Buang')
            ->whereDoesntHave('pengerjaan_produks', fn ($q) => $q->where('proses_id', $x->id))
            ->when($request->jenis, fn ($q) => $q->where('jenis', $request->jenis))
            ->whereBetween('updated_at', $this->dateRange($request));
    }

    public function index(Request $request)
    {
        $proses = Proses::with('departemen:id,departemen')
            ->orderBy('urutan')
            ->get()
            ->map(function (Proses $x) use ($request) {
                return [
                    'id'                  => $x->id,
                    'proses'              => $x->proses,
                    'urutan'              => $x->urutan,
                    'departemen'          => $x->departemen->departemen ?? '-',
                    'jumlah_belum_discan' => $this->queryKandidat($x, $request)->count(),
                ];
            });

        return Inertia::render('QrBelumDiscan/Index', [
            'proses'  => $proses,
            'filters' => $request->only(['jenis', 'date_start', 'date_end']),
        ]);
    }

    public function detail(Request $request, Proses $proses)
    {
        $produks = $this->queryKandidat($proses, $request)
            ->with(['proses'])
            ->when($request->search, fn ($q) => $q->where('qrcode', 'like', "%{$request->search}%"))
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('QrBelumDiscan/Detail', [
            'proses'  => $proses->load('departemen:id,departemen'),
            'produks' => $produks,
            'filters' => $request->only(['jenis', 'date_start', 'date_end', 'search']),
        ]);
    }
}