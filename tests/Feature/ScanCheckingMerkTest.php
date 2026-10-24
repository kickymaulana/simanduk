<?php

namespace Tests\Feature;

use App\Models\AturanPenolakan;
use App\Models\Cacat;
use App\Models\Departemen;
use App\Models\Kualitas;
use App\Models\Merk;
use App\Models\PengerjaanProduk;
use App\Models\Produk;
use App\Models\Proses;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScanCheckingMerkTest extends TestCase
{
    use RefreshDatabase;

    private User $leader;

    private User $anggota;

    private Proses $proses;

    private SesiKerja $sesi;

    private Kualitas $ab;

    private Kualitas $sg;

    private Merk $dyna;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $departemen = Departemen::create(['departemen' => 'QC']);
        $this->proses = Proses::create([
            'departemen_id' => $departemen->id,
            'urutan' => 1,
            'proses' => 'QC Visual & Dimensi',
            'is_active' => true,
        ]);
        $shift = Shift::create(['shift' => 'Pagi']);
        $this->leader = User::create([
            'name' => 'Leader QC',
            'username' => 'leader-qc',
            'email' => 'leader-qc@example.com',
            'password' => bcrypt('password'),
            'departemen_id' => $departemen->id,
        ]);
        $this->anggota = User::create([
            'name' => 'Anggota QC',
            'username' => 'anggota-qc',
            'email' => 'anggota-qc@example.com',
            'password' => bcrypt('password'),
            'departemen_id' => $departemen->id,
        ]);
        $this->sesi = SesiKerja::create([
            'leader_id' => $this->leader->id,
            'shift_id' => $shift->id,
            'proses_id' => $this->proses->id,
            'jenis' => 'Body',
            'tanggal_masuk' => now()->toDateString(),
        ]);
        $this->sesi->sesi_kerja_members()->create(['user_id' => $this->anggota->id]);

        $this->ab = Kualitas::create(['kualitas' => 'AB (DYNA / RAPTOR)']);
        $this->sg = Kualitas::create(['kualitas' => 'SG (HIU)']);
        $this->dyna = Merk::create([
            'kualitas_id' => $this->ab->id,
            'merk' => 'DYNA',
            'is_active' => true,
        ]);
        Merk::create([
            'kualitas_id' => $this->sg->id,
            'merk' => 'HIU',
            'is_active' => false,
        ]);
    }

    public function test_all_checking_modes_receive_active_merks_with_grade(): void
    {
        foreach ([
            ['scan.checking', [], 'Scan/Checking'],
            ['scan.checking.mode', ['mode' => 'inproses'], 'Scan/CheckingInproses'],
            ['scan.checking.mode', ['mode' => 'buang'], 'Scan/CheckingBuang'],
        ] as [$routeName, $parameters, $component]) {
            $this->actingAs($this->leader)
                ->withSession(['sesi_kerja_id' => $this->sesi->id])
                ->get(route($routeName, $parameters))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->has('pilihan_merk', 1)
                    ->where('pilihan_merk.0.id', $this->dyna->id)
                    ->where('pilihan_merk.0.merk', 'DYNA')
                    ->where('pilihan_merk.0.kualitas.kualitas', 'AB (DYNA / RAPTOR)'));
        }
    }

    public function test_dyna_sets_product_and_work_snapshots_to_dyna_and_ab_while_ignoring_quality_request(): void
    {
        $produk = $this->produk('DN00000001');

        $this->actingAs($this->leader)
            ->withSession(['sesi_kerja_id' => $this->sesi->id])
            ->post(route('scan.checking_store'), [
                'qr' => $produk->qrcode,
                'merk_id' => $this->dyna->id,
                'kualitas_id' => $this->sg->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('produk', [
            'id' => $produk->id,
            'merk_id' => $this->dyna->id,
            'kualitas_id' => $this->ab->id,
            'status_akhir' => 'OK',
        ]);
        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => $produk->id,
            'user_id' => $this->leader->id,
            'merk_id' => $this->dyna->id,
            'kualitas_id' => $this->ab->id,
        ]);
        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => $produk->id,
            'user_id' => $this->anggota->id,
            'merk_id' => $this->dyna->id,
            'kualitas_id' => $this->ab->id,
        ]);
        $this->assertDatabaseMissing('pengerjaan_produk', [
            'produk_id' => $produk->id,
            'kualitas_id' => $this->sg->id,
        ]);
    }

    public function test_inproses_and_buang_store_merk_and_derived_quality_snapshots(): void
    {
        $cacat = Cacat::create(['cacat' => 'Retak', 'jenis' => 'Body']);
        AturanPenolakan::create([
            'cacat_id' => $cacat->id,
            'proses_toleransi' => $this->proses->id,
            'proses_buang' => $this->proses->id,
            'proses_pemeriksa' => $this->proses->id,
        ]);

        foreach ([
            ['scan.checking.inproses_store', 'DN00000006', 'In Proses'],
            ['scan.checking.buang_store', 'DN00000007', 'Buang'],
        ] as [$routeName, $qrcode, $status]) {
            $produk = $this->produk($qrcode);

            $this->actingAs($this->leader)
                ->withSession(['sesi_kerja_id' => $this->sesi->id])
                ->post(route($routeName), [
                    'qr' => $produk->qrcode,
                    'cacat_ids' => [$cacat->id],
                    'merk_id' => $this->dyna->id,
                    'kualitas_id' => $this->sg->id,
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('produk', [
                'id' => $produk->id,
                'status_akhir' => $status,
                'merk_id' => $this->dyna->id,
                'kualitas_id' => $this->ab->id,
            ]);
            $this->assertDatabaseHas('pengerjaan_produk', [
                'produk_id' => $produk->id,
                'status_kondisi' => $status,
                'merk_id' => $this->dyna->id,
                'kualitas_id' => $this->ab->id,
            ]);
        }
    }

    public function test_checking_requires_an_active_merk(): void
    {
        $produk = $this->produk('DN00000002');
        $inactive = Merk::where('is_active', false)->firstOrFail();

        $this->actingAs($this->leader)
            ->withSession(['sesi_kerja_id' => $this->sesi->id])
            ->post(route('scan.checking_store'), [
                'qr' => $produk->qrcode,
                'merk_id' => $inactive->id,
            ])
            ->assertSessionHasErrors('merk_id');

        $this->assertDatabaseCount('pengerjaan_produk', 0);
        $this->assertNull($produk->fresh()->merk_id);
    }

    public function test_existing_product_and_work_rows_remain_null_after_additive_migration(): void
    {
        $produk = $this->produk('DN00000003');
        $pengerjaan = PengerjaanProduk::create([
            'user_id' => $this->leader->id,
            'produk_id' => $produk->id,
            'sesi_kerja_id' => $this->sesi->id,
            'proses_id' => $this->proses->id,
            'status_kondisi' => 'OK',
            'kualitas_id' => null,
        ]);

        $this->assertNull($produk->fresh()->merk_id);
        $this->assertNull($pengerjaan->fresh()->merk_id);
    }

    public function test_non_checking_scan_still_records_work_without_merk(): void
    {
        $produk = $this->produk('DN00000004');

        $this->actingAs($this->leader)
            ->withSession(['sesi_kerja_id' => $this->sesi->id])
            ->post(route('scan.validasi_store'), ['qr' => $produk->qrcode])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pengerjaan_produk', [
            'produk_id' => $produk->id,
            'user_id' => $this->leader->id,
            'merk_id' => null,
            'kualitas_id' => null,
        ]);
        $this->assertNull($produk->fresh()->merk_id);
    }

    private function produk(string $qrcode): Produk
    {
        return Produk::create([
            'qrcode' => $qrcode,
            'nama' => 'Produk '.$qrcode,
            'jenis' => 'Body',
            'status_akhir' => 'OK',
            'sudah_scan' => 'Belum',
        ]);
    }
}
