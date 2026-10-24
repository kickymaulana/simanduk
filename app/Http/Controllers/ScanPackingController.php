<?php

namespace App\Http\Controllers;

use App\Models\Aksesori;
use App\Models\Gudang;
use App\Models\Merk;
use App\Models\Packing;
use App\Models\PergerakanStokAksesori;
use App\Models\Warna;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ScanPackingController extends Controller
{
    public function index()
    {
        return Inertia::render('ScanPacking/Index', [
            'merks' => Merk::with('kualitas:id,kualitas')
                ->where('is_active', true)
                ->orderBy('merk')
                ->get(['id', 'kualitas_id', 'merk']),
            'warnas' => Warna::orderBy('warna')->get(['id', 'warna']),
            'gudangs' => Gudang::where('is_active', true)->orderBy('gudang')->get(['id', 'gudang']),
            'aksesoris' => Aksesori::where('is_active', true)
                ->orderBy('part_number')
                ->get(['id', 'part_number', 'description', 'stok']),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['qrcode_kardus' => strtoupper(trim((string) $request->qrcode_kardus))]);

        $data = $request->validate([
            'qrcode_kardus' => ['required', 'string', 'max:255', 'unique:packing,qrcode_kardus'],
            'jenis' => ['required', Rule::in(['Body', 'Tangki'])],
            'merk_id' => ['required', Rule::exists('merk', 'id')->where('is_active', true)],
            'warna_id' => ['required', 'exists:warna,id'],
            'gudang_id' => ['required', Rule::exists('gudang', 'id')->where('is_active', true)],
            'accessories' => ['required', 'array', 'min:1'],
            'accessories.*.aksesori_id' => ['required', 'distinct', Rule::exists('aksesori', 'id')->where('is_active', true)],
            'accessories.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'qrcode_kardus.unique' => 'QR kardus sudah pernah dipindai.',
            'merk_id.exists' => 'Merk tidak aktif atau tidak valid.',
            'accessories.required' => 'Pilih minimal satu aksesori.',
            'accessories.min' => 'Pilih minimal satu aksesori.',
            'accessories.*.aksesori_id.distinct' => 'Aksesori tidak boleh dipilih lebih dari satu kali.',
        ]);

        $merk = Merk::whereKey($data['merk_id'])
            ->where('is_active', true)
            ->firstOrFail();

        try {
            $packing = DB::transaction(function () use ($data, $request, $merk) {
                $quantities = collect($data['accessories'])->keyBy('aksesori_id');
                $aksesoris = Aksesori::whereIn('id', $quantities->keys())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($quantities as $aksesoriId => $selection) {
                    $aksesori = $aksesoris->get($aksesoriId);
                    $quantity = (int) $selection['quantity'];

                    if (! $aksesori || $aksesori->stok < $quantity) {
                        $partNumber = $aksesori?->part_number ?? "ID {$aksesoriId}";
                        $stok = $aksesori?->stok ?? 0;

                        throw ValidationException::withMessages([
                            'accessories' => "Stok aksesori {$partNumber} tidak cukup. Tersedia: {$stok}, kebutuhan: {$quantity}.",
                        ]);
                    }
                }

                $packing = Packing::create([
                    'qrcode_kardus' => $data['qrcode_kardus'],
                    'jenis' => $data['jenis'],
                    'kualitas_id' => $merk->kualitas_id,
                    'merk_id' => $merk->id,
                    'warna_id' => $data['warna_id'],
                    'gudang_id' => $data['gudang_id'],
                    'user_id' => $request->user()->id,
                ]);

                $packing->accessories()->createMany($data['accessories']);

                foreach ($quantities as $aksesoriId => $selection) {
                    $aksesori = $aksesoris->get($aksesoriId);
                    $quantity = (int) $selection['quantity'];
                    $stokSebelum = $aksesori->stok;
                    $stokSesudah = $stokSebelum - $quantity;

                    $aksesori->update(['stok' => $stokSesudah]);
                    $aksesori->pergerakanStok()->create([
                        'packing_id' => $packing->id,
                        'user_id' => $request->user()->id,
                        'tipe' => PergerakanStokAksesori::KELUAR_PACKING,
                        'quantity' => $quantity,
                        'stok_sebelum' => $stokSebelum,
                        'stok_sesudah' => $stokSesudah,
                        'keterangan' => "Packing {$packing->qrcode_kardus}",
                    ]);
                }

                return $packing->load([
                    'kualitas:id,kualitas',
                    'merk:id,merk',
                    'warna:id,warna',
                    'gudang:id,gudang',
                    'accessories.aksesori:id,part_number,description',
                ]);
            });
        } catch (QueryException $exception) {
            if (Packing::where('qrcode_kardus', $data['qrcode_kardus'])->exists()) {
                return back()->withErrors(['qrcode_kardus' => 'QR kardus sudah pernah dipindai.'])->withInput();
            }

            report($exception);

            return back()->withErrors(['error' => 'Data packing gagal disimpan. Silakan coba lagi.'])->withInput();
        }

        return redirect()->route('scan.packing.index')->with([
            'success' => 'Packing berhasil disimpan.',
            'scan_qr' => $packing->qrcode_kardus,
            'scan_mode' => 'Packing Berhasil',
            'scan_detail' => [
                'jenis' => $packing->jenis,
                'kualitas' => $packing->kualitas?->kualitas,
                'merk' => $packing->merk?->merk,
                'warna' => $packing->warna?->warna,
                'gudang' => $packing->gudang?->gudang,
                'total_aksesori' => $packing->accessories->sum('quantity'),
                'accessories' => $packing->accessories->map(fn ($item) => [
                    'part_number' => $item->aksesori?->part_number,
                    'description' => $item->aksesori?->description,
                    'quantity' => $item->quantity,
                ])->values()->all(),
            ],
        ]);
    }
}
