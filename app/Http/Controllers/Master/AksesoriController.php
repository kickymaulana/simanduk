<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Aksesori;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AksesoriController extends Controller
{
    public function index(Request $request)
    {
        $aksesoris = Aksesori::when($request->search, fn ($query, $search) => $query->where(function ($query) use ($search) {
            $query->where('part_number', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('aksesori', 'like', "%{$search}%");
        }))
            ->latest()->paginate(10)->withQueryString();

        return Inertia::render('Master/Aksesori/Index', ['aksesoris' => $aksesoris, 'filters' => $request->only('search')]);
    }

    public function create()
    {
        return Inertia::render('Master/Aksesori/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255', 'unique:aksesori,part_number'],
            'description' => ['required', 'string', 'max:255'],
        ]);
        $data['aksesori'] = $data['part_number'];
        Aksesori::create($data);

        return redirect()->route('aksesori.index')->with('success', 'Aksesori berhasil ditambahkan.');
    }

    public function edit(Aksesori $aksesori)
    {
        return Inertia::render('Master/Aksesori/Edit', ['aksesori' => $aksesori]);
    }

    public function update(Request $request, Aksesori $aksesori)
    {
        $data = $request->validate([
            'part_number' => ['required', 'string', 'max:255', Rule::unique('aksesori', 'part_number')->ignore($aksesori)],
            'description' => ['required', 'string', 'max:255'],
        ]);
        $data['aksesori'] = $data['part_number'];
        $aksesori->update($data);

        return redirect()->route('aksesori.index')->with('success', 'Aksesori berhasil diperbarui.');
    }

    public function toggleActive(Aksesori $aksesori)
    {
        $aksesori->update(['is_active' => ! $aksesori->is_active]);

        return back()->with('success', 'Status aksesori berhasil diperbarui.');
    }
}
