<?php

namespace Tests\Feature;

use App\Http\Controllers\ScanController;
use App\Models\Departemen;
use App\Models\Kualitas;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use App\Models\Warna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ScanTanpaTroliTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $departemen = Departemen::create(['departemen' => 'Casting']);
        $proses = Proses::create(['departemen_id' => $departemen->id, 'urutan' => 1, 'proses' => 'Casting']);
        $shift = Shift::create(['shift' => 'Pagi']);

        $leader = User::create([
            'name' => 'Leader',
            'username' => 'leader',
            'email' => 'leader@example.com',
            'password' => bcrypt('password'),
            'departemen_id' => $departemen->id,
        ]);
        $anggota = User::create([
            'name' => 'Anggota',
            'username' => 'anggota',
            'email' => 'anggota@example.com',
            'password' => bcrypt('password'),
            'departemen_id' => $departemen->id,
        ]);

        $sesi = SesiKerja::create([
            'leader_id' => $leader->id,
            'shift_id' => $shift->id,
            'proses_id' => $proses->id,
            'jenis' => 'Body',
        ]);
        $sesi->sesi_kerja_members()->create(['user_id' => $anggota->id]);

        Kualitas::create(['kualitas' => 'A']);
        Warna::create(['warna' => 'Putih']);

        return compact('leader', 'anggota', 'sesi', 'proses');
    }

    private function callScan(string $method, array $params): void
    {
        $request = Request::create('/scan', 'POST', $params);
        app(ScanController::class)->{$method}($request);
    }

    public function test_scan_awal_membuat_produk_dan_mencatat_pengerjaan()
    {
        ['leader' => $leader, 'anggota' => $anggota, 'sesi' => $sesi] = $this->setupData();

        Auth::login($leader);
        session(['sesi_kerja_id' => $sesi->id]);
        $this->callScan('awal_store', ['qr' => 'DN00001234', 'nomor_mesin' => 'Mesin 01', 'nomor_mould' => 'M1', 'asal_slip' => 'SS1']);

        $this->assertDatabaseHas('produk', ['qrcode' => 'DN00001234', 'proses_id' => $sesi->proses_id, 'sudah_scan' => 'Sudah']);
        $this->assertDatabaseHas('pengerjaan_produk', ['produk_id' => 1, 'user_id' => $leader->id]);
        $this->assertDatabaseHas('pengerjaan_produk', ['produk_id' => 1, 'user_id' => $anggota->id]);
    }

    public function test_scan_awal_ditolak_tanpa_sesi_active(): void
    {
        $data = $this->setupData();
        Auth::login($data['leader']);

        $this->callScan('awal_store', ['qr' => 'DN00009999', 'nomor_mesin' => 'Mesin 01', 'nomor_mould' => 'M1', 'asal_slip' => 'SS1']);

        $this->assertDatabaseMissing('produk', ['qrcode' => 'DN00009999']);
        $this->assertNotNull(session('errors'));
        $this->assertArrayHasKey('error', session('errors')->toArray());
    }

    public function test_scan_validasi_mencatat_untuk_leader_dan_anggota(): void
    {
        $data = $this->setupData();
        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callScan('awal_store', ['qr' => 'DN00005678', 'nomor_mesin' => 'Mesin 01', 'nomor_mould' => 'M1', 'asal_slip' => 'SS1']);

        // Reset sudah_scan agar bisa di-scan lagi sebagai validasi (simulasi pindah proses tidak di sini)
        \App\Models\Produk::where('qrcode', 'DN00005678')->update(['sudah_scan' => 'Belum']);

        $this->callScan('validasi_store', ['qr' => 'DN00005678']);

        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => 1,
            'user_id' => $data['leader']->id,
            'status_kondisi' => 'OK',
        ]);
        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => 1,
            'user_id' => $data['anggota']->id,
            'status_kondisi' => 'OK',
        ]);
    }

    public function test_scan_validasi_menolak_produk_yang_tidak_exist(): void
    {
        $data = $this->setupData();
        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callScan('validasi_store', ['qr' => 'DN0000000']);

        $this->assertNotNull(session('errors'));
        $this->assertArrayHasKey('qr', session('errors')->toArray());
    }

    public function test_pengerjaan_tidak_bergantung_pada_troli(): void
    {
        $data = $this->setupData();
        Auth::login($data['leader']);
        session(['sesi_kerja_id' => $data['sesi']->id]);

        $this->callScan('awal_store', ['qr' => 'DN00007777', 'nomor_mesin' => 'Mesin 01', 'nomor_mould' => 'M1', 'asal_slip' => 'SS1']);

        $this->assertTrue(PengerjaanProduk::where('produk_id', 1)->count() >= 2);
    }
}
