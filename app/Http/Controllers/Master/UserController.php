<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Departemen;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{

    /** Daftar user yang masih menunggu persetujuan (status antri). */
    public function pending(Request $request)
    {
        $users = User::query()
            ->with(['roles:id,name', 'departemen:id,departemen'])
            ->where('status', 'antri')
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Master/Users/Pending', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /** Setujui user: aktifkan + beri role. */
    public function approve(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|exists:roles,id',
        ]);

        $role = Role::findOrFail($request->role);

        $user->syncRoles([$role->id]);
        $user->update(['status' => 'aktif']);

        return redirect()->route('users.pending')->with('success', "User {$user->name} disetujui.");
    }

    /** Tolak user antri. */
    public function reject(User $user)
    {
        if ($user->status !== 'antri') {
            return back()->with('error', 'User tidak berstatus antri.');
        }

        $user->update(['status' => 'ditolak']);

        return redirect()->route('users.pending')->with('success', "User {$user->name} ditolak.");
    }

    public function index(Request $request)
    {
        $users = User::query()
        ->select('id', 'name', 'username', 'email', 'departemen_id', 'status', 'created_at')
        ->with([
            'roles:id,name',
            'departemen:id,departemen'
        ])
        ->when($request->search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%");
        })
        ->orderBy('departemen_id', 'asc') // Urutkan berdasarkan ID Departemen
        ->paginate(10)
        ->withQueryString();

        return Inertia::render('Master/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']) // Kirim balik input pencarian ke frontend
        ]);
    }

    public function create()
    {
        return Inertia::render('Master/Users/Create', [
            'departemens' => Departemen::orderBy('departemen', 'asc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|lowercase|email|max:255|unique:users',
            'departemen_id' => 'required|exists:departemen,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'departemen_id' => $request->departemen_id,
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }


    public function show(User $user)
    {

        $user->load('departemen');

        return Inertia::render('Master/Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'departemen_nama' => $user->departemen?->departemen ?? 'Tanpa Departemen',
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('Master/Users/Edit', [
            'user' => $user,
            'departemens' => Departemen::orderBy('departemen', 'asc')->get(),
        ]);
    }



    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'departemen_id' => ['required', 'exists:departemen,id'], // Validasi departemen
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'departemen_id' => $request->departemen_id,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()
            ->route('users.show', $user->id)
            ->with('success', 'Data user berhasil diperbarui.');
    }


    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        // Cek keterkaitan di model-model yang kamu sebutkan
        $hasRelation = $user->sesiKerjaMembers()->exists() ||
                    PengerjaanProduk::where('user_id', $user->id)->exists() ||
                    PengerjaanCacat::where('user_scan_id', $user->id)
                                                ->orWhere('user_pj_id', $user->id)
                                                ->exists() ||
                    SesiKerja::where('leader_id', $user->id)->exists();

        if ($hasRelation) {
            return back()->with('error', 'User tidak bisa dihapus karena memiliki riwayat pengerjaan atau data terkait.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
