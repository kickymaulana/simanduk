<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AturanPenolakan;
use App\Models\Cacat;
use App\Models\Proses;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AturanPenolakanController extends Controller
{
    public function index(Request $request)
    {
        $jenis = $request->input('jenis', 'semua');
        $prosesPemeriksa = $request->integer('proses_pemeriksa') ?: null;
        $prosesBuang = $request->integer('proses_buang') ?: null;
        $prosesToleransi = $request->integer('proses_toleransi') ?: null;
        $baseQuery = AturanPenolakan::query()
            ->when($jenis !== 'semua', fn ($query) => $query->whereHas('cacat', fn ($q) => $q->where('jenis', $jenis)))
            ->when($prosesPemeriksa, fn ($query) => $query->where('proses_pemeriksa', $prosesPemeriksa))
            ->when($prosesBuang, fn ($query) => $query->where('proses_buang', $prosesBuang))
            ->when($prosesToleransi, fn ($query) => $query->where('proses_toleransi', $prosesToleransi));
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'body' => (clone $baseQuery)->whereHas('cacat', fn ($q) => $q->where('jenis', 'Body'))->count(),
            'tangki' => (clone $baseQuery)->whereHas('cacat', fn ($q) => $q->where('jenis', 'Tangki'))->count(),
            'pemeriksa' => (clone $baseQuery)->distinct('proses_pemeriksa')->count('proses_pemeriksa'),
            'buang' => (clone $baseQuery)->distinct('proses_buang')->count('proses_buang'),
            'toleransi' => (clone $baseQuery)->distinct('proses_toleransi')->count('proses_toleransi'),
        ];
        $aturanPenolakans = $baseQuery
            // Kita join tabel proses (asumsikan nama tabelnya 'proses')
            // untuk mengurutkan berdasarkan nama proses pemeriksa
            ->select('aturan_penolakan.*') // Pastikan select id agar tidak bentrok
            ->join('proses as pemeriksa', 'aturan_penolakan.proses_pemeriksa', '=', 'pemeriksa.id')
            ->with(['cacat', 'proses_toleransi', 'proses_buang', 'proses_pemeriksa'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('cacat', function ($q) use ($search) {
                    $q->where('cacat', 'like', "%{$search}%");
                });
            })
            // Urutkan berdasarkan kolom 'proses' di tabel yang di-join
            ->orderBy('pemeriksa.proses', 'asc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Master/AturanPenolakans/Index', [
            'aturanPenolakans' => $aturanPenolakans,
            'filters' => ['search' => $request->input('search', ''), 'jenis' => $jenis, 'proses_pemeriksa' => $prosesPemeriksa, 'proses_buang' => $prosesBuang, 'proses_toleransi' => $prosesToleransi],
            'proses' => Proses::orderBy('proses')->get(['id', 'proses']),
            'counts' => $counts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/AturanPenolakans/Create', [
            'cacats' => Cacat::all(),
            'proses' => Proses::orderBy('created_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cacat_id' => 'required|exists:cacat,id',
            'proses_toleransi' => 'required|exists:proses,id',
            'proses_buang' => 'required|exists:proses,id',
            'proses_pemeriksa' => 'required|exists:proses,id',
        ]);

        AturanPenolakan::create($request->all());

        return redirect()->route('aturanpenolakans.index')->with('message', 'Aturan penolakan berhasil dibuat.');
    }

    public function edit($id)
    {
        return Inertia::render('Master/AturanPenolakans/Edit', [
            'aturan' => AturanPenolakan::findOrFail($id),
            'cacats' => Cacat::all(),
            'proses' => Proses::orderBy('created_at')->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $aturan = AturanPenolakan::findOrFail($id);

        $request->validate([
            'cacat_id' => 'required|exists:cacat,id',
            'proses_toleransi' => 'required|exists:proses,id',
            'proses_buang' => 'required|exists:proses,id',
            'proses_pemeriksa' => 'required|exists:proses,id',
        ]);

        $aturan->update($request->all());

        return redirect()->route('aturanpenolakans.index')->with('message', 'Aturan berhasil diperbarui.');
    }

    public function destroy($id)
    {
        AturanPenolakan::findOrFail($id)->delete();

        return redirect()->route('aturanpenolakans.index')->with('message', 'Aturan berhasil dihapus.');
    }
}
