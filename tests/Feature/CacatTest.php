<?php

namespace Tests\Feature;

use App\Http\Controllers\Master\CacatController;
use App\Models\AturanPenolakan;
use App\Models\Cacat;
use App\Models\Departemen;
use App\Models\PengerjaanCacat;
use App\Models\PengerjaanProduk;
use App\Models\Produk;
use App\Models\Proses;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CacatTest extends TestCase
{
    use RefreshDatabase;

    private function scanReference(): array
    {
        $departemen = Departemen::create(['departemen' => 'Casting']);
        $proses = Proses::create(['proses' => 'Casting', 'urutan' => 1, 'departemen_id' => $departemen->id]);
        $user = User::create(['name' => 'User', 'username' => 'user', 'email' => 'user@test.com', 'password' => 'pwd', 'departemen_id' => $departemen->id]);
        $shift = Shift::create(['shift' => 'Pagi']);
        $sesi = SesiKerja::create(['leader_id' => $user->id, 'shift_id' => $shift->id, 'proses_id' => $proses->id, 'jenis' => 'Body']);
        $produk = Produk::create(['qrcode' => 'QRTEST01', 'nama' => 'Test', 'jenis' => 'Body', 'status_akhir' => 'OK', 'proses_id' => $proses->id]);
        $pengerjaan = PengerjaanProduk::create(['user_id' => $user->id, 'produk_id' => $produk->id, 'sesi_kerja_id' => $sesi->id, 'proses_id' => $proses->id, 'status_kondisi' => 'OK']);

        return compact('user', 'proses', 'pengerjaan');
    }

    public function test_cacat_dipakai_riwayat_scan_tidak_bisa_dihapus(): void
    {
        $reference = $this->scanReference();
        $cacat = Cacat::create(['cacat' => 'Retak', 'jenis' => 'Body']);
        PengerjaanCacat::create([
            'pengerjaan_produk_id' => $reference['pengerjaan']->id,
            'cacat_id' => $cacat->id,
            'user_scan_id' => $reference['user']->id,
            'proses_scan_id' => $reference['proses']->id,
        ]);

        $response = app(CacatController::class)->destroy($cacat);

        $this->assertTrue($response->isRedirect());
        $this->assertDatabaseHas('cacat', ['id' => $cacat->id]);
        $this->assertStringContainsString('Riwayat scan: 1 data.', session('error'));
    }

    public function test_cacat_dipakai_aturan_penolakan_tidak_bisa_dihapus(): void
    {
        $cacat = Cacat::create(['cacat' => 'Retak', 'jenis' => 'Body']);
        $departemen = Departemen::create(['departemen' => 'Casting']);
        $proses = Proses::create(['proses' => 'Casting', 'urutan' => 1, 'departemen_id' => $departemen->id]);
        AturanPenolakan::create([
            'cacat_id' => $cacat->id,
            'proses_pemeriksa' => $proses->id,
            'proses_toleransi' => $proses->id,
            'proses_buang' => $proses->id,
        ]);

        app(CacatController::class)->destroy($cacat);

        $this->assertDatabaseHas('cacat', ['id' => $cacat->id]);
        $this->assertStringContainsString('Aturan penolakan: 1 aturan.', session('error'));
    }

    public function test_cacat_tanpa_pemakaian_bisa_dihapus(): void
    {
        $cacat = Cacat::create(['cacat' => 'Retak', 'jenis' => 'Body']);

        app(CacatController::class)->destroy($cacat);

        $this->assertDatabaseMissing('cacat', ['id' => $cacat->id]);
    }
}
