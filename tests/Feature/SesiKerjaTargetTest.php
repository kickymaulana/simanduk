<?php

namespace Tests\Feature;

use App\Models\Departemen;
use App\Models\Proses;
use App\Models\SesiKerja;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SesiKerjaTargetTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_limit_applies_to_create_and_update_for_all_processes(): void
    {
        URL::forceRootUrl('http://localhost');
        $departemen = Departemen::create(['departemen' => 'QC']);
        $user = User::factory()->create(['username' => 'target-test', 'departemen_id' => $departemen->id]);
        $shift = Shift::create(['shift' => 'Pagi']);
        $this->actingAs($user);

        foreach (['Casting', 'QC Visual & Dimensi'] as $nama) {
            $proses = Proses::create(['departemen_id' => $departemen->id, 'urutan' => 1, 'proses' => $nama]);
            $data = ['shift_id' => $shift->id, 'proses_id' => $proses->id, 'jenis' => 'Body', 'tanggal_masuk' => '2026-11-01'];

            foreach ([201, 10000000, 0, -1, 1.5, 'abc'] as $target) {
                $before = SesiKerja::count();
                $this->post(route('sesikerjas.store'), [...$data, 'target' => $target])->assertSessionHasErrors('target');
                $this->assertSame($before, SesiKerja::count());
            }

            foreach ([1, 200, null, ''] as $target) {
                $this->post(route('sesikerjas.store'), [...$data, 'target' => $target])->assertSessionHasNoErrors();
                $sesi = SesiKerja::latest('id')->firstOrFail();
                $this->assertEquals($target === '' ? null : $target, $sesi->target);
                $this->put(route('sesikerjas.update', $sesi), [...$data, 'target' => 201])->assertSessionHasErrors('target');
                $this->assertEquals($target === '' ? null : $target, $sesi->fresh()->target);
                $this->put(route('sesikerjas.update', $sesi), [...$data, 'target' => 200])->assertSessionHasNoErrors();
                $this->assertEquals(200, $sesi->fresh()->target);
                $this->put(route('sesikerjas.update', $sesi), [...$data, 'target' => ''])->assertSessionHasNoErrors();
                $this->assertNull($sesi->fresh()->target);
            }
        }
    }
}
