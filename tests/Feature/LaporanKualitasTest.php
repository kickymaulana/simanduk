<?php

namespace Tests\Feature;

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

class LaporanKualitasTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_remains_based_on_quality_snapshot_derived_from_merk(): void
    {
        URL::forceRootUrl('http://localhost');

        $departemen = Departemen::create(['departemen' => 'QC']);
        $proses = Proses::create([
            'departemen_id' => $departemen->id,
            'urutan' => 1,
            'proses' => 'QC Visual & Dimensi',
            'is_active' => true,
        ]);
        $shift = Shift::create(['shift' => 'Pagi']);
        $user = User::create([
            'name' => 'Operator QC',
            'username' => 'operator-qc-report',
            'email' => 'operator-qc-report@example.com',
            'password' => bcrypt('password'),
            'departemen_id' => $departemen->id,
        ]);
        $sesi = SesiKerja::create([
            'leader_id' => $user->id,
            'shift_id' => $shift->id,
            'proses_id' => $proses->id,
            'jenis' => 'Body',
            'tanggal_masuk' => now()->toDateString(),
        ]);
        Kualitas::create(['kualitas' => 'FG(EXPORT)']);
        $ab = Kualitas::create(['kualitas' => 'AB (DYNA / RAPTOR)']);
        Kualitas::create(['kualitas' => 'SG (HIU)']);
        $dyna = Merk::create([
            'kualitas_id' => $ab->id,
            'merk' => 'DYNA',
            'is_active' => true,
        ]);
        $produk = Produk::create([
            'qrcode' => 'DN00000005',
            'nama' => 'Produk Laporan',
            'jenis' => 'Body',
            'status_akhir' => 'OK',
            'sudah_scan' => 'Sudah',
            'merk_id' => $dyna->id,
            'kualitas_id' => $ab->id,
        ]);
        PengerjaanProduk::create([
            'user_id' => $user->id,
            'produk_id' => $produk->id,
            'sesi_kerja_id' => $sesi->id,
            'proses_id' => $proses->id,
            'status_kondisi' => 'OK',
            'merk_id' => $dyna->id,
            'kualitas_id' => $ab->id,
            'created_at' => now()->setTime(12, 0),
            'updated_at' => now()->setTime(12, 0),
        ]);

        $this->actingAs($user)
            ->get(route('laporan.kualitas', [
                'bulan' => now()->month,
                'tahun' => now()->year,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('LaporanKualitas/Index')
                ->has('rows', 1)
                ->where('rows.0.jenis', 'Body')
                ->where('rows.0.input', 1)
                ->where('rows.0.ab', 1)
                ->where('rows.0.fg', 0)
                ->where('rows.0.sg', 0)
                ->where('summary.ab', 1));
    }
}
