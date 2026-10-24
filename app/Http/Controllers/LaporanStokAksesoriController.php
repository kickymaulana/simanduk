<?php

namespace App\Http\Controllers;

use App\Models\Aksesori;
use App\Models\PergerakanStokAksesori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LaporanStokAksesoriController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'mulai' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:mulai'],
            'tipe' => ['nullable', Rule::in([
                PergerakanStokAksesori::MASUK,
                PergerakanStokAksesori::KELUAR_PACKING,
            ])],
            'aksesori_id' => ['nullable', 'integer', 'exists:aksesori,id'],
        ]);

        $mulai = $filters['mulai'] ?? now()->startOfMonth()->toDateString();
        $sampai = $filters['sampai'] ?? now()->toDateString();

        $aksesoris = Aksesori::orderBy('part_number')->get([
            'id',
            'part_number',
            'description',
            'stok',
            'is_active',
        ]);

        $riwayat = PergerakanStokAksesori::query()
            ->with([
                'aksesori:id,part_number,description',
                'packing:id,qrcode_kardus',
                'user:id,name',
            ])
            ->whereDate('created_at', '>=', $mulai)
            ->whereDate('created_at', '<=', $sampai)
            ->when($filters['tipe'] ?? null, fn ($query, $tipe) => $query->where('tipe', $tipe))
            ->when($filters['aksesori_id'] ?? null, fn ($query, $aksesoriId) => $query->where('aksesori_id', $aksesoriId))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('LaporanStokAksesori/Index', [
            'aksesoris' => $aksesoris,
            'riwayat' => $riwayat,
            'filters' => [
                'mulai' => $mulai,
                'sampai' => $sampai,
                'tipe' => $filters['tipe'] ?? null,
                'aksesori_id' => $filters['aksesori_id'] ?? null,
            ],
        ]);
    }
}
