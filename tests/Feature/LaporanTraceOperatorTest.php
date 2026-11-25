<?php

namespace Tests\Feature;

use App\Models\Departemen;
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

class LaporanTraceOperatorTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Proses $proses;

    private SesiKerja $sesi;

    protected function setUp(): void
    {
        parent::setUp();
        URL::forceRootUrl('http://localhost');

        $departemen = Departemen::create(['departemen' => 'QC']);
        $this->proses = Proses::create(['departemen_id' => $departemen->id, 'urutan' => 1, 'proses' => 'QC Visual & Dimensi']);
        $this->user = User::factory()->create(['username' => 'trace-test', 'departemen_id' => $departemen->id, 'status' => 'aktif']);
        $shift = Shift::create(['shift' => 'Pagi']);
        $this->sesi = SesiKerja::create([
            'leader_id' => $this->user->id,
            'shift_id' => $shift->id,
            'proses_id' => $this->proses->id,
            'jenis' => 'Body',
            'tanggal_masuk' => '2026-11-01',
        ]);
        $this->actingAs($this->user);
    }

    private function produk(string $qr, array $tanggal = []): Produk
    {
        $produk = Produk::create(['qrcode' => $qr, 'nama' => 'Trace', 'jenis' => 'Body', 'status_akhir' => 'OK', 'proses_id' => $this->proses->id]);
        foreach ($tanggal as $waktu) {
            PengerjaanProduk::create([
                'user_id' => $this->user->id,
                'produk_id' => $produk->id,
                'sesi_kerja_id' => $this->sesi->id,
                'proses_id' => $this->proses->id,
                'status_kondisi' => 'OK',
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        }

        return $produk;
    }

    public function test_orders_by_latest_qc_row_and_keeps_unscanned_products_last(): void
    {
        $this->produk('LAMA', ['2026-11-10 10:00:00', '2026-11-01 10:00:00']);
        $this->produk('BARU', ['2026-11-05 10:00:00']);
        $this->produk('BELUM');

        $this->get(route('laporan.trace.operator'))->assertInertia(fn (Assert $page) => $page
            ->where('filters.qc_order', 'desc')
            ->where('produks.data.0.qrcode', 'BARU')
            ->where('produks.data.1.qrcode', 'LAMA')
            ->where('produks.data.2.qrcode', 'BELUM'));
        $this->get(route('laporan.trace.operator', ['qc_order' => 'asc']))->assertInertia(fn (Assert $page) => $page
            ->where('produks.data.0.qrcode', 'LAMA')
            ->where('produks.data.1.qrcode', 'BARU')
            ->where('produks.data.2.qrcode', 'BELUM'));
    }

    public function test_date_range_includes_entire_end_day_and_uses_latest_qc_row(): void
    {
        $this->produk('AWAL', ['2026-11-02 00:00:00']);
        $this->produk('AKHIR', ['2026-11-03 23:59:59']);
        $this->produk('LUAR', ['2026-11-03 12:00:00', '2026-11-04 00:00:00']);
        $this->produk('BELUM');

        $this->get(route('laporan.trace.operator', ['qc_dari' => '2026-11-02', 'qc_sampai' => '2026-11-03']))
            ->assertInertia(fn (Assert $page) => $page->where('produks.total', 2)
                ->where('produks.data.0.qrcode', 'AKHIR')->where('produks.data.1.qrcode', 'AWAL'));
        $this->get(route('laporan.trace.operator', ['qc_sampai' => '2026-11-02']))
            ->assertInertia(fn (Assert $page) => $page->where('produks.total', 1)->where('produks.data.0.qrcode', 'AWAL'));
    }

    public function test_sorting_and_filters_apply_before_pagination(): void
    {
        for ($day = 1; $day <= 17; $day++) {
            $this->produk(sprintf('QR%02d', $day), [sprintf('2026-11-%02d 10:00:00', $day)]);
        }
        $this->get(route('laporan.trace.operator', ['qc_order' => 'asc', 'qc_dari' => '2026-11-01', 'jenis' => 'Body', 'search' => 'QR', 'page' => 2]))
            ->assertInertia(fn (Assert $page) => $page->where('produks.total', 17)
                ->where('produks.data.0.qrcode', 'QR16')->where('produks.data.1.qrcode', 'QR17')
                ->where('filters.qc_dari', '2026-11-01')->where('filters.jenis', 'Body'));
    }

    public function test_machine_report_uses_qc_sort_filters_and_oven_process_timestamps(): void
    {
        $ovenSusun = Proses::create(['departemen_id' => $this->proses->departemen_id, 'urutan' => 2, 'proses' => 'Oven Susun']);
        $ovenBongkar = Proses::create(['departemen_id' => $this->proses->departemen_id, 'urutan' => 3, 'proses' => 'Oven Bongkar']);
        $produk = $this->produk('OVEN1', ['2026-11-08 10:00:00']);

        foreach ([[$ovenSusun, '2026-11-07 14:00:00'], [$ovenBongkar, '2026-11-08 05:00:00']] as [$proses, $waktu]) {
            PengerjaanProduk::create([
                'user_id' => $this->user->id,
                'produk_id' => $produk->id,
                'sesi_kerja_id' => $this->sesi->id,
                'proses_id' => $proses->id,
                'status_kondisi' => 'OK',
                'created_at' => $waktu,
                'updated_at' => $waktu,
            ]);
        }

        $this->get(route('laporan.trace.mesin', ['qc_dari' => '2026-11-08', 'qc_order' => 'asc']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.qc_order', 'asc')
                ->where('produks.total', 1)
                ->where('produks.data.0.oven.susun', '07 Nov, 14:00')
                ->where('produks.data.0.oven.bongkar', '08 Nov, 05:00'));
    }

    public function test_rejects_invalid_sort_and_date_range(): void
    {
        $this->get(route('laporan.trace.operator', ['qc_order' => 'invalid']))->assertSessionHasErrors('qc_order');
        $this->get(route('laporan.trace.operator', ['qc_dari' => '2026-11-04', 'qc_sampai' => '2026-11-02']))->assertSessionHasErrors('qc_sampai');
        $this->get(route('laporan.trace.operator', ['qc_dari' => 'not-a-date']))->assertSessionHasErrors('qc_dari');
    }

    public function test_machine_report_defaults_to_qc_visual_descending_and_keeps_unscanned_last(): void
    {
        $this->produk('PERTAMA', ['2026-11-05 10:00:00']);
        $this->produk('KEDUA', ['2026-11-01 10:00:00']);
        $this->produk('BELUM');

        $this->get(route('laporan.trace.mesin'))->assertInertia(fn (Assert $page) => $page
            ->where('filters.qc_order', 'desc')
            ->where('produks.data.0.qrcode', 'PERTAMA')
            ->where('produks.data.1.qrcode', 'KEDUA')
            ->where('produks.data.2.qrcode', 'BELUM'));
    }
}
