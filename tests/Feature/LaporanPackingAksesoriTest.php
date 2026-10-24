<?php

namespace Tests\Feature;

use App\Models\Aksesori;
use App\Models\Gudang;
use App\Models\Kualitas;
use App\Models\Merk;
use App\Models\Packing;
use App\Models\PergerakanStokAksesori;
use App\Models\User;
use App\Models\Warna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LaporanPackingAksesoriTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Aksesori $aksesori;

    private Packing $packing;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $this->user = User::create([
            'name' => 'Operator Laporan',
            'username' => 'operator-laporan',
            'email' => 'operator-laporan@example.com',
            'password' => 'password',
        ]);
        $kualitas = Kualitas::create(['kualitas' => 'FG']);
        $merk = Merk::create(['kualitas_id' => $kualitas->id, 'merk' => 'Merk Laporan']);
        $warna = Warna::create(['warna' => 'Putih']);
        $gudang = Gudang::create(['gudang' => 'Gudang Laporan']);
        $this->aksesori = Aksesori::create([
            'aksesori' => 'ACC-001',
            'part_number' => 'ACC-001',
            'description' => 'Aksesori Laporan',
            'stok' => 7,
        ]);
        $this->packing = Packing::create([
            'qrcode_kardus' => 'KARDUS-001',
            'jenis' => 'Body',
            'kualitas_id' => $kualitas->id,
            'merk_id' => $merk->id,
            'warna_id' => $warna->id,
            'gudang_id' => $gudang->id,
            'user_id' => $this->user->id,
        ]);
        $this->packing->accessories()->create([
            'aksesori_id' => $this->aksesori->id,
            'quantity' => 3,
        ]);
        PergerakanStokAksesori::create([
            'aksesori_id' => $this->aksesori->id,
            'packing_id' => $this->packing->id,
            'user_id' => $this->user->id,
            'tipe' => PergerakanStokAksesori::KELUAR_PACKING,
            'quantity' => 3,
            'stok_sebelum' => 10,
            'stok_sesudah' => 7,
            'keterangan' => 'Packing KARDUS-001',
        ]);
    }

    public function test_authenticated_user_can_view_filtered_packing_report_with_accessory_detail(): void
    {
        $this->actingAs($this->user)
            ->get(route('laporan.scan.packing', [
                'mulai' => now()->toDateString(),
                'sampai' => now()->toDateString(),
                'jenis' => 'Body',
                'search' => 'KARDUS',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LaporanScanPacking/Index')
                ->has('packings.data', 1)
                ->where('packings.data.0.qrcode_kardus', 'KARDUS-001')
                ->where('packings.data.0.total_aksesori', 3)
                ->where('packings.data.0.accessories.0.part_number', 'ACC-001'));
    }

    public function test_authenticated_user_can_view_filtered_accessory_stock_report(): void
    {
        $this->actingAs($this->user)
            ->get(route('laporan.stok.aksesori', [
                'mulai' => now()->toDateString(),
                'sampai' => now()->toDateString(),
                'tipe' => PergerakanStokAksesori::KELUAR_PACKING,
                'aksesori_id' => $this->aksesori->id,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LaporanStokAksesori/Index')
                ->has('aksesoris', 1)
                ->where('aksesoris.0.stok', 7)
                ->has('riwayat.data', 1)
                ->where('riwayat.data.0.tipe', PergerakanStokAksesori::KELUAR_PACKING)
                ->where('riwayat.data.0.packing.qrcode_kardus', 'KARDUS-001'));
    }

    public function test_packing_and_accessory_stock_reports_require_authentication(): void
    {
        $this->get(route('laporan.scan.packing'))->assertRedirect(route('login'));
        $this->get(route('laporan.stok.aksesori'))->assertRedirect(route('login'));
    }
}
