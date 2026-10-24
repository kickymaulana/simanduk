<?php

namespace App\Http\Controllers;

use App\Models\Aksesori;
use App\Models\PergerakanStokAksesori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StokAksesoriController extends Controller
{
    public function index()
    {
        return Inertia::render('StokAksesori/Index', [
            'aksesoris' => Aksesori::orderBy('part_number')->get([
                'id',
                'part_number',
                'description',
                'stok',
                'is_active',
            ]),
            'riwayat' => PergerakanStokAksesori::with([
                'aksesori:id,part_number,description',
                'packing:id,qrcode_kardus',
                'user:id,name',
            ])
                ->latest('id')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(Request $request, Aksesori $aksesori)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'quantity.required' => 'Quantity stok masuk wajib diisi.',
            'quantity.integer' => 'Quantity stok masuk harus berupa bilangan bulat.',
            'quantity.min' => 'Quantity stok masuk minimal 1.',
            'keterangan.string' => 'Keterangan harus berupa teks.',
            'keterangan.max' => 'Keterangan maksimal 255 karakter.',
        ]);

        DB::transaction(function () use ($aksesori, $data, $request) {
            $aksesori = Aksesori::whereKey($aksesori->id)->lockForUpdate()->firstOrFail();
            $stokSebelum = $aksesori->stok;
            $stokSesudah = $stokSebelum + $data['quantity'];

            $aksesori->update(['stok' => $stokSesudah]);
            $aksesori->pergerakanStok()->create([
                'user_id' => $request->user()->id,
                'tipe' => PergerakanStokAksesori::MASUK,
                'quantity' => $data['quantity'],
                'stok_sebelum' => $stokSebelum,
                'stok_sesudah' => $stokSesudah,
                'keterangan' => $data['keterangan'] ?? null,
            ]);
        });

        return back()->with('success', 'Stok aksesori berhasil ditambahkan.');
    }
}
