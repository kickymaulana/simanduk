<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Kualitas;
use App\Models\Merk;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MerkController extends Controller
{
    public function index(Request $request)
    {
        $merks = Merk::with('kualitas:id,kualitas')
            ->when($request->search, fn ($query, $search) => $query->where('merk', 'like', "%{$search}%"))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Master/Merk/Index', [
            'merks' => $merks,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Merk/Create', [
            'kualitas' => Kualitas::orderBy('kualitas')->get(['id', 'kualitas']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kualitas_id' => ['required', 'exists:kualitas,id'],
            'merk' => ['required', 'string', 'max:255', Rule::unique('merk')->where('kualitas_id', $request->kualitas_id)],
        ]);

        Merk::create($data);

        return redirect()->route('merk.index')->with('success', 'Merk berhasil ditambahkan.');
    }

    public function edit(Merk $merk)
    {
        return Inertia::render('Master/Merk/Edit', [
            'merk' => $merk,
            'kualitas' => Kualitas::orderBy('kualitas')->get(['id', 'kualitas']),
        ]);
    }

    public function update(Request $request, Merk $merk)
    {
        $data = $request->validate([
            'kualitas_id' => ['required', 'exists:kualitas,id'],
            'merk' => ['required', 'string', 'max:255', Rule::unique('merk')->where('kualitas_id', $request->kualitas_id)->ignore($merk)],
        ]);

        $merk->update($data);

        return redirect()->route('merk.index')->with('success', 'Merk berhasil diperbarui.');
    }

    public function toggleActive(Merk $merk)
    {
        $merk->update(['is_active' => ! $merk->is_active]);

        return back()->with('success', 'Status merk berhasil diperbarui.');
    }
}
