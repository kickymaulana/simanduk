<?php

namespace Tests\Feature;

use App\Http\Controllers\ScanController;
use App\Models\Departemen;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Models\Produk;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CekUrutanScanTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $dept = Departemen::create(['departemen' => 'Casting']);
        $casting = Proses::create(['departemen_id' => $dept->id, 'urutan' => 1, 'proses' => 'Casting']);
        $checking = Proses::create(['departemen_id' => $dept->id, 'urutan' => 4, 'proses' => 'Checking']);
        $solar = Proses::create(['departemen_id' => $dept->id, 'urutan' => 5, 'proses' => 'Solar']);
        $shift = Shift::create(['shift' => 'Pagi']);

        $leader = User::create([
            'name' => 'Leader', 'username' => 'leader', 'email' => 'leader@test.com',
            'password' => bcrypt('pwd'), 'departemen_id' => $dept->id,
        ]);

        $sesi = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $checking->id, 'jenis' => 'Body',
        ]);

        $produk = Produk::create([
            'qrcode' => 'DN0000001', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => $casting->id,
        ]);

        return compact('leader', 'casting', 'checking', 'solar', 'sesi', 'produk');
    }

    private function callValidasi(string $qr): void
    {
        $request = Request::create('/scan/validasi', 'POST', ['qr' => $qr]);
        app(ScanController::class)->validasi_store($request);
    }

    public function test_scan_ditolak_jika_proses_sebelumnya_belum_discan()
    {
        $data = $this->setupData();
        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callValidasi('DN0000001');

        $this->assertNotNull(session('errors'));
        $this->assertArrayHasKey('qr', session('errors')->toArray());
        $this->assertStringContainsString('belum discan di proses', session('errors')->first('qr'));

        // Produk tidak boleh berubah posisinya
        $this->assertEquals('OK', Produk::find($data['produk']->id)->status_akhir);
    }

    public function test_scan_lolos_jika_semua_proses_sebelumnya_sudah_discan()
    {
        $data = $this->setupData();

        // Catat pengerjaan di proses sebelumnya (Casting)
        PengerjaanProduk::create([
            'user_id' => $data['leader']->id, 'produk_id' => $data['produk']->id,
            'sesi_kerja_id' => $data['sesi']->id, 'proses_id' => $data['casting']->id,
            'status_kondisi' => 'OK',
        ]);

        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callValidasi('DN0000001');

        $this->assertNull(session('errors'));
        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => $data['produk']->id,
            'proses_id' => $data['checking']->id,
        ]);
    }

    public function test_cek_tidak_berlaku_saat_fitur_dimatikan()
    {
        $data = $this->setupData();
        DB::table('settings')->where('key', 'cek_urutan_scan')->update(['value' => '0']);

        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callValidasi('DN0000001');

        $this->assertNull(session('errors'));
        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => $data['produk']->id,
            'proses_id' => $data['checking']->id,
        ]);
    }

    public function test_proses_packing_tangki_tidak_wajib_untuk_produk_body()
    {
        $dept = Departemen::create(['departemen' => 'Packing']);
        $casting = Proses::create(['departemen_id' => $dept->id, 'urutan' => 1, 'proses' => 'Casting']);
        $packingTangki = Proses::create(['departemen_id' => $dept->id, 'urutan' => 14, 'proses' => 'Packing Tangki', 'jenis' => 'Tangki']);
        $packingBody = Proses::create(['departemen_id' => $dept->id, 'urutan' => 15, 'proses' => 'Packing Body', 'jenis' => 'Body']);
        $shift = Shift::create(['shift' => 'Pagi']);

        $leader = User::create([
            'name' => 'Leader', 'username' => 'leader', 'email' => 'leader@test.com',
            'password' => bcrypt('pwd'), 'departemen_id' => $dept->id,
        ]);

        $produk = Produk::create([
            'qrcode' => 'DN0000002', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => $casting->id,
        ]);

        // Sesi di Casting untuk mencatat pengerjaan sebelumnya
        $sesiCasting = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $casting->id, 'jenis' => 'Body',
        ]);

        // Discan di Casting (proses sebelumnya wajib)
        PengerjaanProduk::create([
            'user_id' => $leader->id, 'produk_id' => $produk->id,
            'sesi_kerja_id' => $sesiCasting->id, 'proses_id' => $casting->id,
            'status_kondisi' => 'OK',
        ]);

        // Sesi di Packing Body — Body tidak wajib lewat Packing Tangki
        $sesi = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $packingBody->id, 'jenis' => 'Body',
        ]);

        Auth::login($leader);
        session(['sesi_kerja_id' => $sesi->id]);

        $this->callValidasi('DN0000002');

        $this->assertNull(session('errors'), 'Body harus bisa scan di Packing Body tanpa lewat Packing Tangki.');
    }
}