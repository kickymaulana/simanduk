<?php

namespace Tests\Feature;

use App\Http\Controllers\KoreksiScanController;
use App\Models\AturanPenolakan;
use App\Models\Cacat;
use App\Models\Departemen;
use App\Models\PengerjaanCacat;
use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Models\Produk;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class KoreksiScanTest extends TestCase
{
    use RefreshDatabase;

    private function setupData(): array
    {
        $dept = Departemen::create(['departemen' => 'Casting']);
        $proses1 = Proses::create(['departemen_id' => $dept->id, 'urutan' => 1, 'proses' => 'Casting']);
        $proses2 = Proses::create(['departemen_id' => $dept->id, 'urutan' => 2, 'proses' => 'Rework']);
        $shift = Shift::create(['shift' => 'Pagi']);

        $leader = User::create([
            'name' => 'Leader', 'username' => 'leader', 'email' => 'leader@test.com',
            'password' => bcrypt('pwd'), 'departemen_id' => $dept->id,
        ]);
        User::create([
            'name' => 'Anggota', 'username' => 'anggota', 'email' => 'anggota@test.com',
            'password' => bcrypt('pwd'), 'departemen_id' => $dept->id,
        ]);

        $sesi1 = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $proses1->id, 'jenis' => 'Body',
        ]);

        $sesi2 = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $proses2->id, 'jenis' => 'Body',
        ]);

        $produk = Produk::create([
            'qrcode' => 'DN0000001', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => $proses1->id,
        ]);

        // Scan awal: casting
        PengerjaanProduk::create([
            'user_id' => $leader->id, 'produk_id' => $produk->id,
            'sesi_kerja_id' => $sesi1->id, 'proses_id' => $proses1->id,
            'status_kondisi' => 'OK',
        ]);

        // Scan buang (salah)
        $p2 = PengerjaanProduk::create([
            'user_id' => $leader->id, 'produk_id' => $produk->id,
            'sesi_kerja_id' => $sesi2->id, 'proses_id' => $proses2->id,
            'status_kondisi' => 'Buang',
        ]);

        $cacat = Cacat::create(['cacat' => 'Retak']);
        AturanPenolakan::create([
            'cacat_id' => $cacat->id, 'proses_pemeriksa' => $proses2->id,
            'proses_toleransi' => $proses1->id, 'proses_buang' => $proses1->id,
        ]);
        PengerjaanCacat::create([
            'pengerjaan_produk_id' => $p2->id, 'cacat_id' => $cacat->id,
            'user_scan_id' => $leader->id, 'proses_scan_id' => $proses2->id,
        ]);

        $produk->update(['status_akhir' => 'Buang', 'proses_id' => $proses2->id]);

        return compact('produk', 'proses1');
    }

    public function test_batalkan_scan_terakhir_mengembalikan_produk_ke_posisi_sebelumnya()
    {
        $data = $this->setupData();
        $produk = $data['produk'];
        $proses1 = $data['proses1'];

        $request = Request::create('/koreksi-scan/' . $produk->id . '/batalkan', 'POST');
        $response = app(KoreksiScanController::class)->batalkan($request, $produk);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertEquals('OK', $payload['produk']['status_akhir']);
        $this->assertEquals($proses1->id, $payload['produk']['proses_id']);

        // Cek pengerjaan sudah kembali ke 1 (hanya scan awal, tanpa anggota)
        $this->assertEquals(1, PengerjaanProduk::where('produk_id', $produk->id)->count());

        // Cek cacat sudah dihapus
        $this->assertEquals(0, PengerjaanCacat::whereIn('pengerjaan_produk_id',
            PengerjaanProduk::where('produk_id', $produk->id)->pluck('id'))->count());
    }

    public function test_batalkan_ditolak_jika_hanya_satu_histori()
    {
        $dept = Departemen::create(['departemen' => 'Casting']);
        $proses = Proses::create(['departemen_id' => $dept->id, 'urutan' => 1, 'proses' => 'Casting']);

        $leader = User::create([
            'name' => 'Leader', 'username' => 'leader', 'email' => 'leader@test.com',
            'password' => bcrypt('pwd'), 'departemen_id' => $dept->id,
        ]);
        $shift = Shift::create(['shift' => 'Pagi']);
        $sesi = SesiKerja::create([
            'leader_id' => $leader->id, 'shift_id' => $shift->id,
            'proses_id' => $proses->id, 'jenis' => 'Body',
        ]);

        $produk = Produk::create([
            'qrcode' => 'DN0000002', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => $proses->id,
        ]);

        PengerjaanProduk::create([
            'user_id' => $leader->id, 'produk_id' => $produk->id,
            'sesi_kerja_id' => $sesi->id, 'proses_id' => $proses->id,
            'status_kondisi' => 'OK',
        ]);

        $request = Request::create('/koreksi-scan/' . $produk->id . '/batalkan', 'POST');
        $response = app(KoreksiScanController::class)->batalkan($request, $produk);

        $this->assertEquals(422, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Minimal harus ada 2 histori', $payload['message']);
    }

    public function test_cari_produk_by_qr()
    {
        $dept = Departemen::create(['departemen' => 'Casting']);
        $produk = Produk::create([
            'qrcode' => 'DN0000003', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => null,
        ]);

        $request = Request::create('/koreksi-scan/cari', 'POST', ['qr' => 'dn0000003']);
        $response = app(KoreksiScanController::class)->cari($request);

        $this->assertEquals(200, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertEquals('DN0000003', $payload['produk']['qrcode']);
    }

    public function test_cari_produk_tidak_ditemukan()
    {
        $request = Request::create('/koreksi-scan/cari', 'POST', ['qr' => 'DN9999999']);
        $response = app(KoreksiScanController::class)->cari($request);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function test_batalkan_ditolak_jika_tidak_ada_histori()
    {
        $dept = Departemen::create(['departemen' => 'Casting']);
        $proses = Proses::create(['departemen_id' => $dept->id, 'urutan' => 1, 'proses' => 'Casting']);

        $produk = Produk::create([
            'qrcode' => 'DN0000004', 'nama' => 'Test', 'jenis' => 'Body',
            'status_akhir' => 'OK', 'proses_id' => $proses->id,
        ]);

        $request = Request::create('/koreksi-scan/' . $produk->id . '/batalkan', 'POST');
        $response = app(KoreksiScanController::class)->batalkan($request, $produk);

        $this->assertEquals(422, $response->getStatusCode());
    }
}