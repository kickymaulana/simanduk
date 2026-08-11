<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SesiKerja;
use App\Models\Shift;
use Inertia\Inertia;
use App\Models\User;
use App\Models\MasterDepartemen;
use App\Models\Proses;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;

class SesiKerjaController extends Controller
{
    public function index(Request $request)
    {

        $sesikerjas = SesiKerja::query()
            // Syarat utama: Harus milik user yang sedang login
            ->where('leader_id', auth()->id())
            ->with(['leader', 'sesi_kerja_members.user', 'shift', 'proses'])
            ->withCount(['pengerjaan_produks as total_pengerjaan' => function ($query) {
                $query->select(DB::raw('count(distinct produk_id, proses_id)'));
            }])
            ->when($request->search, function ($query, $search) {
                // Kita bungkus WHERE pencarian di dalam grup agar tidak merusak leader_id
                $query->where(function ($q) use ($search) {
                    $q->whereHas('leader', function ($sub) use ($search) {
                        $sub->where('name', 'like', "%{$search}%");
                    })
                    ->orWhere('jenis', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('SesiKerjas/Index', [
            'sesikerjas' => $sesikerjas,
            'filters' => $request->only(['search']),
            'sesi_kerja_id' => session('sesi_kerja_id'),
        ]);
    }



    public function create()
    {
        $user = auth()->user();
        return Inertia::render('SesiKerjas/Create', [
            'shifts' => Shift::all(['id', 'shift']),
            // Ambil proses hanya yang sesuai departemen user yang login & masih aktif
            'prosesList' => Proses::where('departemen_id', $user->departemen_id)
            ->where('is_active', true)
            ->orderBy('urutan', 'asc')
            ->get(['id', 'proses']),
            'users' => User::where('id', '!=', auth()->id())->get(['id', 'name'])
        ]);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'proses_id' => 'required|exists:proses,id',
            'jenis' => 'required|in:Body,Tangki',
            'user_ids'  => 'nullable|array', // ID anggota yang dipilih
            'user_ids.*'=> 'exists:users,id'
        ]);
        $validated['leader_id'] = Auth::id();


        // Gunakan Transaction agar jika simpan member gagal, sesi_kerja juga batal (aman)
        \DB::transaction(function () use ($validated) {
            $sesi = SesiKerja::create([
                'leader_id' => $validated['leader_id'],
                'shift_id'  => $validated['shift_id'],
                'proses_id' => $validated['proses_id'],
                'jenis' => $validated['jenis'],
            ]);

            if (!empty($validated['user_ids'])) {
                foreach ($validated['user_ids'] as $userId) {
                    $sesi->sesi_kerja_members()->create([
                        'user_id' => $userId
                    ]);
                }
            }
        });


        return redirect()->route('sesikerjas.index')
            ->with('message', 'Sesi kerja berhasil dicatat.');
    }


    public function show(SesiKerja $sesikerja)
    {
        $sesikerja->load([
            'leader',
            'shift', // Tambahkan relasi shift
            'sesi_kerja_members.user',
            'pengerjaan_produks' => function($query) {
                $query->with(['produk', 'proses'])->latest();
            }
        ]);

        $pengerjaanRows = $sesikerja->pengerjaan_produks;

        // Filter Unik (Per Produk & Per Proses)
        $allUnique = $pengerjaanRows->unique(function ($item) {
            return $item['produk_id'].'-'.$item['proses_id'];
        })->values();

        $pengerjaanLimit = $allUnique->take(12);

        $stats = [
            'total_scan'      => $allUnique->count(),
            'total_ok'        => $allUnique->where('status_kondisi', 'OK')->count(),
            'total_in_proses' => $allUnique->where('status_kondisi', 'In Proses')->count(),
            'total_reject'    => $allUnique->where('status_kondisi', 'Buang')->count(),
        ];

        return Inertia::render('SesiKerjas/Show', [
            'sesikerja' => $sesikerja,
            'stats'     => $stats,
            'pengerjaan_unik' => $pengerjaanLimit
        ]);
    }


    public function riwayat_scan(Request $request, SesiKerja $sesikerja)
    {
        // 1. Ambil query dasar dari relasi
        $query = $sesikerja->pengerjaan_produks()
            ->with(['produk', 'proses']);

        // 2. Filter Search (berdasarkan QR Code di tabel produk)
        if ($request->search) {
            $query->whereHas('produk', function ($q) use ($request) {
                $q->where('qrcode', 'like', "%{$request->search}%");
            });
        }

        // 3. Logika Unique: Ambil hanya scan terakhir per produk per proses
        // Kita gunakan subquery untuk mendapatkan ID terbaru saja
        $riwayat = $query->whereIn('id', function ($subQuery) use ($sesikerja) {
            $subQuery->selectRaw('MAX(id)')
                ->from('pengerjaan_produk') // Pastikan ini nama tabel PengerjaanProduk kamu
                ->where('sesi_kerja_id', $sesikerja->id)
                ->groupBy('produk_id', 'proses_id');
        })
        ->latest()
        ->paginate(12)
        ->withQueryString();

        return Inertia::render('SesiKerjas/RiwayatScan', [
            'sesikerja' => $sesikerja->load('leader'),
            'riwayat'   => $riwayat,
            'filters'   => $request->only(['search'])
        ]);
    }

    public function edit(SesiKerja $sesikerja)
    {
        $user = auth()->user();
        // 1. Ambil departemen_id dari user yang sedang login
        $departemenId = auth()->user()->departemen_id;

        // 2. Load members agar checkbox di Vue terisi
        $sesikerja->load('sesi_kerja_members');

        return Inertia::render('SesiKerjas/Edit', [
            'sesikerja' => $sesikerja,
            'shifts' => Shift::all(['id', 'shift']),
            'prosesList' => Proses::where('departemen_id', $user->departemen_id)
            ->where('is_active', true)
            ->orderBy('urutan', 'asc')
            ->get(['id', 'proses']),
            'users' => User::query()
                ->where('id', '!=', auth()->id()) // Kecuali diri sendiri
                ->where('departemen_id', $departemenId) // Harus satu departemen
                ->get(['id', 'name'])
        ]);
    }

    public function update(Request $request, SesiKerja $sesikerja)
    {
        $validated = $request->validate([
            'shift_id' => 'required|exists:shift,id',
            'proses_id' => 'required|exists:proses,id',
            'jenis' => 'required|in:Body,Tangki',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        DB::transaction(function () use ($validated, $sesikerja) {
            // Update data utama
            $sesikerja->update([
                'shift_id' => $validated['shift_id'],
                'proses_id' => $validated['proses_id'],
                'jenis' => $validated['jenis'],
            ]);

            // Update Member: Hapus yang lama, masukkan yang baru
            // (Ini cara paling aman kalau tidak pakai relation sync)
            $sesikerja->sesi_kerja_members()->delete();

            if (!empty($validated['user_ids'])) {
                foreach ($validated['user_ids'] as $userId) {
                    $sesikerja->sesi_kerja_members()->create([
                        'user_id' => $userId
                    ]);
                }
            }
        });

        return redirect()->route('sesikerjas.index')
            ->with('message', 'Sesi kerja dan tim berhasil diperbarui.');
    }

    public function aktifkan(SesiKerja $sesikerja)
    {
        session(['sesi_kerja_id' => $sesikerja->id]);

        return Redirect::route('sesikerjas.index')
            ->with('message', "Sesi {$sesikerja->jenis} diaktifkan.");
    }

    public function nonaktif()
    {
        session()->forget('sesi_kerja_id');

        return Redirect::route('sesikerjas.index')
            ->with('message', 'Sesi kerja dinonaktifkan.');
    }

    public function destroy(SesiKerja $sesikerja)
    {
        // Cek apakah sudah ada produk yang dikerjakan di sesi ini
        $adaPengerjaan = $sesikerja->pengerjaan_produks()->exists();

        if ($adaPengerjaan) {
            return back()->withErrors([
                'error' => 'Sesi tidak bisa dihapus karena sudah ada data pengerjaan produk di dalamnya!'
            ]);
        }

        DB::transaction(function () use ($sesikerja) {
            // Hapus member dulu (karena foreign key)
            $sesikerja->sesi_kerja_members()->delete();
            // Baru hapus sesi
            $sesikerja->delete();
        });

        return redirect()->route('sesikerjas.index')
            ->with('message', 'Sesi kerja berhasil dihapus.');
    }
}
