<?php

namespace App\Http\Controllers;

use App\Models\Packing;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LaporanScanPackingController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'mulai' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:mulai'],
            'jenis' => ['nullable', Rule::in(['Body', 'Tangki'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $mulai = $filters['mulai'] ?? now()->startOfMonth()->toDateString();
        $sampai = $filters['sampai'] ?? now()->toDateString();

        $packings = Packing::query()
            ->with([
                'kualitas:id,kualitas',
                'merk:id,merk',
                'warna:id,warna',
                'gudang:id,gudang',
                'user:id,name',
                'accessories.aksesori:id,part_number,description',
            ])
            ->whereDate('created_at', '>=', $mulai)
            ->whereDate('created_at', '<=', $sampai)
            ->when($filters['jenis'] ?? null, fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('qrcode_kardus', 'like', "%{$search}%"))
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Packing $packing) => [
                'id' => $packing->id,
                'qrcode_kardus' => $packing->qrcode_kardus,
                'created_at' => $packing->created_at,
                'jenis' => $packing->jenis,
                'kualitas' => $packing->kualitas?->kualitas,
                'merk' => $packing->merk?->merk,
                'warna' => $packing->warna?->warna,
                'gudang' => $packing->gudang?->gudang,
                'operator' => $packing->user?->name,
                'total_aksesori' => $packing->accessories->sum('quantity'),
                'accessories' => $packing->accessories->map(fn ($item) => [
                    'part_number' => $item->aksesori?->part_number,
                    'description' => $item->aksesori?->description,
                    'quantity' => $item->quantity,
                ])->values(),
            ]);

        return Inertia::render('LaporanScanPacking/Index', [
            'packings' => $packings,
            'filters' => [
                'mulai' => $mulai,
                'sampai' => $sampai,
                'jenis' => $filters['jenis'] ?? null,
                'search' => $filters['search'] ?? '',
            ],
        ]);
    }
}
