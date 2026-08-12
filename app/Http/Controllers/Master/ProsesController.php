<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Proses;
use Inertia\Inertia;
use App\Models\Departemen;

class ProsesController extends Controller
{
    public function index(Request $request)
    {
        $proses = Proses::query()
        ->with('departemen:id,departemen')
        ->when($request->search, function ($query, $search) {
            $query->where(function($q) use ($search) { // Bungkus orWhere dalam grup agar tidak merusak urutan
                $q->where('proses', 'like', "%{$search}%")
                ->orWhereHas('departemen', function ($sub) use ($search) {
                    $sub->where('departemen', 'like', "%{$search}%");
                });
            });
        })
        ->orderBy('urutan', 'asc') // Urutkan berdasarkan kolom urutan secara absolut
        ->paginate(10)
        ->withQueryString();

        return Inertia::render('Master/Proses/Index', [
            'proses' => $proses,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Proses/Create', [
            'departemens' => Departemen::orderBy('departemen', 'asc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'departemen_id' => 'required|exists:departemen,id',
            'proses'        => 'required|string|max:255',
            'urutan'        => 'required|integer|min:1',
        ], [
            'departemen_id.required' => 'Departemen wajib dipilih.',
            'proses.required'        => 'Nama proses wajib diisi.',
            'urutan.required'        => 'Urutan wajib diisi.',
        ]);

        Proses::create([
            'departemen_id' => $request->departemen_id,
            'proses'        => $request->proses,
            'urutan'        => $request->urutan,
        ]);

        return redirect()->route('proses.index')->with('message', 'Proses berhasil ditambahkan.');
    }

    public function edit(Proses $proses)
    {
        return Inertia::render('Master/Proses/Edit', [
            'proses' => $proses,
            'departemens' => Departemen::orderBy('departemen', 'asc')->get(),
        ]);
    }

    public function update(Request $request, Proses $proses)
    {
        $request->validate([
            'departemen_id' => 'required|exists:departemen,id',
            'proses'        => 'required|string|max:255',
            'urutan'        => 'required|integer|min:1',
        ]);

        $proses->update([
            'departemen_id' => $request->departemen_id,
            'proses'        => $request->proses,
            'urutan'        => $request->urutan,
        ]);

        return redirect()->route('proses.index')->with('message', 'Data proses berhasil diperbarui.');
    }

    public function toggleActive(Proses $proses)
    {
        $proses->update([
            'is_active' => ! $proses->is_active,
        ]);

        return redirect()->route('proses.index')
            ->with('message', 'Status proses berhasil diperbarui.');
    }
}
