<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Oven;
use App\Models\Produk;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OvenController extends Controller
{
    public function index(Request $request)
    {
        $ovens = Oven::query()
            ->when($request->search, fn ($q) => $q->where('oven', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Master/Ovens/Index', [
            'ovens' => $ovens,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Ovens/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'oven' => 'required|string|max:255|unique:oven,oven',
        ], [
            'oven.required' => 'Nama oven wajib diisi.',
            'oven.unique' => 'Nama oven ini sudah terdaftar.',
        ]);

        Oven::create($request->only('oven'));

        return redirect()->route('ovens.index')->with('message', 'Data oven berhasil ditambahkan.');
    }

    public function edit(Oven $oven)
    {
        return Inertia::render('Master/Ovens/Edit', [
            'oven' => $oven
        ]);
    }

    public function update(Request $request, Oven $oven)
    {
        $request->validate([
            'oven' => 'required|string|max:255|unique:oven,oven,' . $oven->id,
        ], [
            'oven.required' => 'Nama oven wajib diisi.',
            'oven.unique' => 'Nama oven ini sudah terdaftar.',
        ]);

        $oven->update($request->only('oven'));

        return redirect()->route('ovens.index')->with('message', 'Data oven berhasil diperbarui.');
    }

    public function destroy(Oven $oven)
    {
        if (Produk::where('oven_id', $oven->id)->exists()) {
            return redirect()->back()->with('error', 'Oven tidak bisa dihapus karena sudah dipakai pada data produk.');
        }

        $oven->delete();

        return redirect()->route('ovens.index')->with('message', 'Data oven berhasil dihapus.');
    }
}
