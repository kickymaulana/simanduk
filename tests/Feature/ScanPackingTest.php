<?php

namespace Tests\Feature;

use App\Models\Aksesori;
use App\Models\Gudang;
use App\Models\Kualitas;
use App\Models\Merk;
use App\Models\Packing;
use App\Models\User;
use App\Models\Warna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ScanPackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Kualitas $grade;

    private Merk $merk;

    private Warna $warna;

    private Gudang $gudang;

    private Aksesori $aksesoriSatu;

    private Aksesori $aksesoriDua;

    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('http://localhost');

        $this->user = User::create([
            'name' => 'Operator Packing',
            'username' => 'packing',
            'email' => 'packing@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->grade = Kualitas::create(['kualitas' => 'FG']);
        $this->merk = Merk::create(['kualitas_id' => $this->grade->id, 'merk' => 'Merk A']);
        $this->warna = Warna::create(['warna' => 'Putih']);
        $this->gudang = Gudang::create(['gudang' => 'Gudang A']);
        $this->aksesoriSatu = Aksesori::create([
            'aksesori' => 'SC-001',
            'part_number' => 'SC-001',
            'description' => 'Seat Cover',
            'stok' => 10,
        ]);
        $this->aksesoriDua = Aksesori::create([
            'aksesori' => 'BT-002',
            'part_number' => 'BT-002',
            'description' => 'Baut',
            'stok' => 20,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'qrcode_kardus' => 'box-0001',
            'jenis' => 'Body',
            'merk_id' => $this->merk->id,
            'warna_id' => $this->warna->id,
            'gudang_id' => $this->gudang->id,
            'accessories' => [
                ['aksesori_id' => $this->aksesoriSatu->id, 'quantity' => 1],
                ['aksesori_id' => $this->aksesoriDua->id, 'quantity' => 4],
            ],
        ], $overrides);
    }

    public function test_grade_is_derived_from_brand_and_multiple_accessories_are_stored(): void
    {
        $response = $this->actingAs($this->user)->post('/scan-packing', $this->payload([
            'kualitas_id' => 999999,
        ]));

        $response->assertRedirect(route('scan.packing.index'));
        $response->assertSessionHas('scan_qr', 'BOX-0001');
        $response->assertSessionHas('scan_mode', 'Packing Berhasil');
        $response->assertSessionHas('scan_detail.total_aksesori', 5);
        $this->assertDatabaseHas('packing', [
            'qrcode_kardus' => 'BOX-0001',
            'jenis' => 'Body',
            'kualitas_id' => $this->grade->id,
            'merk_id' => $this->merk->id,
            'user_id' => $this->user->id,
        ]);
        $packing = Packing::where('qrcode_kardus', 'BOX-0001')->firstOrFail();
        $this->assertDatabaseHas('packing_accessories', ['packing_id' => $packing->id, 'aksesori_id' => $this->aksesoriSatu->id, 'quantity' => 1]);
        $this->assertDatabaseHas('packing_accessories', ['packing_id' => $packing->id, 'aksesori_id' => $this->aksesoriDua->id, 'quantity' => 4]);
        $this->assertSame(9, $this->aksesoriSatu->fresh()->stok);
        $this->assertSame(16, $this->aksesoriDua->fresh()->stok);
        $this->assertDatabaseHas('pergerakan_stok_aksesori', [
            'aksesori_id' => $this->aksesoriSatu->id,
            'packing_id' => $packing->id,
            'user_id' => $this->user->id,
            'tipe' => 'KELUAR_PACKING',
            'quantity' => 1,
            'stok_sebelum' => 10,
            'stok_sesudah' => 9,
        ]);
        $this->assertDatabaseHas('pergerakan_stok_aksesori', [
            'aksesori_id' => $this->aksesoriDua->id,
            'packing_id' => $packing->id,
            'user_id' => $this->user->id,
            'tipe' => 'KELUAR_PACKING',
            'quantity' => 4,
            'stok_sebelum' => 20,
            'stok_sesudah' => 16,
        ]);
    }

    public function test_grade_is_hidden_from_packing_props_and_flash_while_still_saved_from_brand(): void
    {
        DB::table('settings')->where('key', 'tampilkan_grade_scan_packing')->update(['value' => '0']);

        $this->actingAs($this->user)
            ->get(route('scan.packing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tampilkan_grade', false)
                ->where('merks.0.id', $this->merk->id)
                ->where('merks.0.merk', 'Merk A')
                ->missing('merks.0.kualitas')
                ->missing('merks.0.kualitas_id'));

        $this->actingAs($this->user)
            ->post(route('scan.packing.store'), $this->payload())
            ->assertSessionHasNoErrors()
            ->assertSessionMissing('scan_detail.kualitas');

        $this->assertDatabaseHas('packing', [
            'merk_id' => $this->merk->id,
            'kualitas_id' => $this->grade->id,
        ]);
    }

    public function test_grade_is_shown_in_packing_props_and_flash_by_default(): void
    {
        $this->actingAs($this->user)
            ->get(route('scan.packing.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('tampilkan_grade', true)
                ->where('merks.0.kualitas.kualitas', 'FG'));

        $this->actingAs($this->user)
            ->post(route('scan.packing.store'), $this->payload())
            ->assertSessionHas('scan_detail.kualitas', 'FG');
    }

    public function test_duplicate_cardboard_qr_is_rejected_case_insensitively(): void
    {
        $this->actingAs($this->user)->post('/scan-packing', $this->payload())->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post('/scan-packing', $this->payload(['qrcode_kardus' => 'box-0001']))
            ->assertSessionHasErrors('qrcode_kardus');

        $this->assertDatabaseCount('packing', 1);
        $this->assertDatabaseCount('packing_accessories', 2);
        $this->assertDatabaseCount('pergerakan_stok_aksesori', 2);
        $this->assertSame(9, $this->aksesoriSatu->fresh()->stok);
        $this->assertSame(16, $this->aksesoriDua->fresh()->stok);
    }

    public function test_brand_automatically_sets_its_own_grade(): void
    {
        $gradeLain = Kualitas::create(['kualitas' => 'AB']);
        $merkLain = Merk::create(['kualitas_id' => $gradeLain->id, 'merk' => 'Merk B']);

        $this->actingAs($this->user)
            ->post('/scan-packing', $this->payload([
                'merk_id' => $merkLain->id,
                'kualitas_id' => $this->grade->id,
            ]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('packing', [
            'merk_id' => $merkLain->id,
            'kualitas_id' => $gradeLain->id,
        ]);
    }

    public function test_packing_without_accessory_is_rejected(): void
    {
        $this->actingAs($this->user)
            ->post('/scan-packing', $this->payload(['accessories' => []]))
            ->assertSessionHasErrors('accessories');

        $this->assertDatabaseCount('packing', 0);
        $this->assertDatabaseCount('packing_accessories', 0);
    }

    public function test_duplicate_or_invalid_accessory_prevents_the_complete_transaction(): void
    {
        $this->actingAs($this->user)->post('/scan-packing', $this->payload([
            'accessories' => [
                ['aksesori_id' => $this->aksesoriSatu->id, 'quantity' => 1],
                ['aksesori_id' => $this->aksesoriSatu->id, 'quantity' => 0],
            ],
        ]))->assertSessionHasErrors(['accessories.1.aksesori_id', 'accessories.1.quantity']);

        $this->assertDatabaseCount('packing', 0);
        $this->assertDatabaseCount('packing_accessories', 0);
    }

    public function test_insufficient_stock_rolls_back_entire_packing_and_keeps_qr_available(): void
    {
        $this->aksesoriDua->update(['stok' => 2]);

        $response = $this->actingAs($this->user)->post('/scan-packing', $this->payload());

        $response->assertSessionHasErrors([
            'accessories' => 'Stok aksesori BT-002 tidak cukup. Tersedia: 2, kebutuhan: 4.',
        ]);
        $this->assertDatabaseCount('packing', 0);
        $this->assertDatabaseCount('packing_accessories', 0);
        $this->assertDatabaseCount('pergerakan_stok_aksesori', 0);
        $this->assertSame(10, $this->aksesoriSatu->fresh()->stok);
        $this->assertSame(2, $this->aksesoriDua->fresh()->stok);

        $this->aksesoriDua->update(['stok' => 4]);

        $this->actingAs($this->user)
            ->post('/scan-packing', $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('packing', ['qrcode_kardus' => 'BOX-0001']);
    }
}
