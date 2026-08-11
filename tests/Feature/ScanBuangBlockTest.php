<?php

namespace Tests\Feature;

use App\Http\Controllers\ScanController;
use App\Models\Departemen;
use App\Models\Proses;
use App\Models\SesiKerja;
use App\Models\User;
use App\Models\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ScanBuangBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_produk_buang_ditolak_di_prosesScan(): void
    {
        $dep = Departemen::create(['departemen' => 'Casting']);
        $pr = Proses::create(['departemen_id' => $dep->id, 'urutan' => 1, 'proses' => 'Casting']);
        $pr2 = Proses::create(['departemen_id' => $dep->id, 'urutan' => 2, 'proses' => 'Rework Casting']);
        $user = User::create(['name' => 'u', 'username' => 'u', 'email' => 'u@u.com', 'password' => bcrypt('x'), 'departemen_id' => $dep->id]);
        $sesi = SesiKerja::create(['leader_id' => $user->id, 'proses_id' => $pr2->id, 'jenis' => 'Body']);

        $produk = Produk::create([
            'qrcode' => 'DN00000001', 'nama' => 'X', 'jenis' => 'Body',
            'status_akhir' => 'Buang', 'sudah_scan' => 'Sudah', 'proses_id' => $pr->id,
        ]);

        Auth::login($user);
        session(['sesi_kerja_id' => $sesi->id]);

        $request = Request::create('/scan/validasi', 'POST', ['qr' => 'DN00000001']);
        $response = app(ScanController::class)->validasi_store($request);

        $errors = session('errors');

        $this->assertNotNull($errors, 'Harus ada error validation');
        $this->assertArrayHasKey('qr', $errors->toArray());
        $this->assertSame('Buang', $produk->fresh()->status_akhir);
        $this->assertSame(0, \App\Models\PengerjaanProduk::count(), 'Tidak boleh ada pengerjaan baru');
    }
}