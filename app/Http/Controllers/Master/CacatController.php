<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Cacat;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CacatController extends Controller
{
    public function index(Request $request)
    {
        $cacats = Cacat::query()
            ->when($request->search, function ($query, $search) {
                $query->where('cacat', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Master/Cacats/Index', [
            'cacats' => $cacats,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Cacats/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cacat' => 'required|string|max:255|unique:cacat,cacat',
            'jenis' => 'required|in:Body,Tangki',
        ], [
            'cacat.required' => 'Cacat wajib diisi.',
            'cacat.unique' => 'Jenis cacat ini sudah terdaftar.',
        ]);

        Cacat::create($request->only('cacat', 'jenis'));

        return redirect()->route('cacats.index')->with('message', 'Data cacat berhasil ditambahkan.');
    }

    public function edit(Cacat $cacat)
    {
        return Inertia::render('Master/Cacats/Edit', [
            'cacat' => $cacat,
        ]);
    }

    public function update(Request $request, Cacat $cacat)
    {
        $request->validate([
            'cacat' => 'required|string|max:255|unique:cacat,cacat,'.$cacat->id,
            'jenis' => 'required|in:Body,Tangki',
        ]);

        $cacat->update($request->only('cacat', 'jenis'));

        return redirect()->route('cacats.index')->with('message', 'Data cacat berhasil diperbarui.');
    }

    public function destroy(Cacat $cacat)
    {
        $cacat->delete();

        return redirect()->route('cacats.index')->with('message', 'Data cacat berhasil dihapus.');
    }
}
