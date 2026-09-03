<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        return Inertia::render('Pengaturan/Index', [
            'cek_urutan_scan' => DB::table('settings')->where('key', 'cek_urutan_scan')->value('value') === '1',
            'cut_off_jam' => (int) (DB::table('settings')->where('key', 'cut_off_jam')->value('value') ?? 6),
        ]);
    }

    public function toggleCekUrutan(Request $request)
    {
        $request->validate([
            'aktif' => 'required|boolean',
        ]);

        DB::table('settings')
            ->where('key', 'cek_urutan_scan')
            ->update(['value' => $request->boolean('aktif') ? '1' : '0']);

        return back()->with('message', 'Status cek urutan scan berhasil diperbarui.');
    }

    public function updateCutOff(Request $request)
    {
        $request->validate([
            'jam' => 'required|integer|min:0|max:23',
        ]);

        DB::table('settings')
            ->where('key', 'cut_off_jam')
            ->update(['value' => (string) $request->integer('jam')]);

        return back()->with('message', 'Jam cut off produksi berhasil diperbarui.');
    }
}