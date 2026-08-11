<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Departemen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role; // Tambahkan ini

class RegisterController extends Controller
{

    public function index()
    {
        return Inertia::render('Auth/Register', [
            'departemens' => Departemen::all(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'departemen_id' => 'required|exists:departemen,id',
        ]);

        // Generate email otomatis berdasarkan username
        $autoEmail = $request->username . '@sisamcus.com';

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $autoEmail,
            'password' => Hash::make($request->password),
            'departemen_id' => $request->departemen_id,
            'status' => 'antri',
        ]);

        // Tidak auto-login; menunggu persetujuan admin (role ditentukan admin saat approval)

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil! Akun kamu menunggu persetujuan admin. Silakan login setelah disetujui.');
    }
}
