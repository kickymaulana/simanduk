<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class GudangController extends Controller
{
    public function index(Request $request)
    {
        $gudangs = Gudang::when($request->search, fn ($query, $search) => $query->where('gudang', 'like', "%{$search}%"))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Master/Gudang/Index', ['gudangs' => $gudangs, 'filters' => $request->only('search')]);
    }

    public function create()
    {
        return Inertia::render('Master/Gudang/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['gudang' => ['required', 'string', 'max:255', 'unique:gudang,gudang']]);
        Gudang::create($data);

        return redirect()->route('gudang.index')->with('success', 'Gudang berhasil ditambahkan.');
    }

    public function edit(Gudang $gudang)
    {
        return Inertia::render('Master/Gudang/Edit', ['gudang' => $gudang]);
    }

    public function update(Request $request, Gudang $gudang)
    {
        $data = $request->validate(['gudang' => ['required', 'string', 'max:255', Rule::unique('gudang', 'gudang')->ignore($gudang)]]);
        $gudang->update($data);

        return redirect()->route('gudang.index')->with('success', 'Gudang berhasil diperbarui.');
    }

    public function toggleActive(Gudang $gudang)
    {
        $gudang->update(['is_active' => ! $gudang->is_active]);

        return back()->with('success', 'Status gudang berhasil diperbarui.');
    }
}
