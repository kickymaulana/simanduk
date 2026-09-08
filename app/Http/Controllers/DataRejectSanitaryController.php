<?php

namespace App\Http\Controllers;

use App\Models\PengerjaanProduk;
use App\Models\Proses;
use App\Support\CutOff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DataRejectSanitaryController extends Controller
{
    public function index(Request $request)
    {
        $now = now();
        $bulan = (int) ($request->bulan ?? $now->month);
        $tahun = (int) ($request->tahun ?? $now->year);

        $proses = Proses::query()
            ->where('is_active', true)
            ->orderBy('urutan')
            ->get(['id', 'proses', 'urutan']);

        $casting = $this->findProcess($proses, 'Casting');
        $reworkCasting = $this->findProcess($proses, 'Rework Casting');
        $solar = $this->findProcess($proses, 'Solar');
        $aging = $this->findProcess($proses, 'Aging');
        $opRework = $this->findProcess($proses, 'Op. Rework');
        $opSolar = $this->findProcess($proses, 'Op. Solar');
        $opSpray = $this->findProcess($proses, 'Op Spray');
        $qcBeforeSprayOven = $this->findProcess($proses, 'QC sebelum Spray & Oven');
        $opOven = $this->findProcess($proses, 'Op. Oven');
        $opFinishGood = $this->findProcess($proses, 'Op. Finish Good');
        $ovenBongkar = $this->findProcess($proses, 'Oven Bongkar');

        [$start, $end] = CutOff::rangeBulan($bulan, $tahun);

        $rows = PengerjaanProduk::query()
            ->join('produk', 'produk.id', '=', 'pengerjaan_produk.produk_id')
            ->join('proses', 'proses.id', '=', 'pengerjaan_produk.proses_id')
            ->select([
                DB::raw('DATE(' . CutOff::expr('pengerjaan_produk.created_at') . ') as tanggal'),
                'produk.jenis',
                'pengerjaan_produk.status_kondisi',
                'pengerjaan_produk.proses_id',
                'pengerjaan_produk.produk_id',
                'proses.proses as nama_proses',
                'proses.urutan',
            ])
            ->where('pengerjaan_produk.created_at', '>=', $start)
            ->where('pengerjaan_produk.created_at', '<', $end)
            ->whereIn('produk.jenis', ['Body', 'Tangki'])
            ->whereIn('pengerjaan_produk.status_kondisi', ['OK', 'In Proses', 'Buang'])
            ->get();

        $rejectRows = $rows->filter(fn ($row) => $row->status_kondisi === 'Buang');
        $okRows = $rows->filter(fn ($row) => $row->status_kondisi !== 'Buang');

        $daysInMonth = (int) $now->copy()->month($bulan)->year($tahun)->endOfMonth()->format('d');
        $dates = collect(range(1, $daysInMonth));
        $items = ['Body', 'Tangki'];

        $columns = [
            ['key' => 'reject_buang_basah', 'label' => 'Buang Basah'],
            ['key' => 'reject_buang_inproses_rework', 'label' => 'Rework Casting'],
            ['key' => 'reject_buang_inproses_solar', 'label' => 'Solar'],
            ['key' => 'reject_buang_aging', 'label' => 'Aging'],
            ['key' => 'reject_buang_op_rework', 'label' => 'Op. Rework'],
            ['key' => 'reject_buang_op_solar', 'label' => 'Op. Solar'],
            ['key' => 'reject_buang_op_spray', 'label' => 'Op. Spray'],
            ['key' => 'reject_buang_qc_before_spray_oven', 'label' => 'QC sebelum Spray & Oven'],
            ['key' => 'reject_buang_op_oven', 'label' => 'Op. Oven'],
            ['key' => 'reject_buang_op_finish_good', 'label' => 'Op. Finish Good'],
            ['key' => 'reject_buang_matang', 'label' => 'Buang Matang'],
            ['key' => 'total_reject_buang', 'label' => 'Total Reject Buang (QTY)'],
            ['key' => 'output_casting', 'label' => 'Output Casting (OK+R)'],
            ['key' => 'reject_percent', 'label' => '% Reject (Target 16%)'],
        ];

        $data = [];
        foreach ($dates as $day) {
            foreach ($items as $jenis) {
                $data[] = array_merge(
                    ['tanggal' => $day, 'jenis' => $jenis, 'output_casting' => 0, 'total_reject_buang' => 0, 'reject_percent' => 0],
                    $this->emptyRow($columns)
                );
            }
        }

        foreach ($rejectRows as $row) {
            $idx = collect($data)->search(fn ($item) => $item['tanggal'] === (int) date('d', strtotime($row->tanggal)) && $item['jenis'] === $row->jenis);
            if ($idx === false) {
                continue;
            }

            $keys = $this->rejectKeys($row, $casting?->id, $reworkCasting?->id, $solar?->id, $aging?->id, $opRework?->id, $opSolar?->id, $opSpray?->id, $qcBeforeSprayOven?->id, $opOven?->id, $opFinishGood?->id, $ovenBongkar?->urutan);
            foreach ($keys as $key) {
                if (isset($data[$idx][$key])) {
                    $data[$idx][$key]++;
                }
            }
        }

        foreach ($okRows as $row) {
            $idx = collect($data)->search(fn ($item) => $item['tanggal'] === (int) date('d', strtotime($row->tanggal)) && $item['jenis'] === $row->jenis);
            if ($idx === false) {
                continue;
            }

            if ($this->isCastingOutput($row, $casting?->id, $reworkCasting?->id)) {
                $data[$idx]['output_casting']++;
            }
        }

        foreach ($data as &$row) {
            $row['total_reject_buang'] = $row['reject_buang_basah'] + $row['reject_buang_inproses_rework'] + $row['reject_buang_inproses_solar'] + $row['reject_buang_aging'] + $row['reject_buang_op_rework'] + $row['reject_buang_op_solar'] + $row['reject_buang_op_spray'] + $row['reject_buang_qc_before_spray_oven'] + $row['reject_buang_op_oven'] + $row['reject_buang_op_finish_good'] + $row['reject_buang_matang'];
            $row['reject_percent'] = $row['output_casting'] > 0 ? round(($row['total_reject_buang'] / $row['output_casting']) * 100, 2) : 0;
        }
        unset($row);

        $totalBody = $this->emptyRow($columns);
        $totalTangki = $this->emptyRow($columns);
        foreach ($data as $row) {
            if ($row['jenis'] === 'Body') {
                foreach ($totalBody as $key => $value) {
                    if (is_numeric($row[$key] ?? null)) {
                        $totalBody[$key] += $row[$key];
                    }
                }
            } else {
                foreach ($totalTangki as $key => $value) {
                    if (is_numeric($row[$key] ?? null)) {
                        $totalTangki[$key] += $row[$key];
                    }
                }
            }
        }
        $totalBody['reject_percent'] = $totalBody['output_casting'] > 0 ? round(($totalBody['total_reject_buang'] / $totalBody['output_casting']) * 100, 2) : 0;
        $totalTangki['reject_percent'] = $totalTangki['output_casting'] > 0 ? round(($totalTangki['total_reject_buang'] / $totalTangki['output_casting']) * 100, 2) : 0;

        $minYear = (int) (PengerjaanProduk::query()->min(DB::raw('YEAR(' . CutOff::expr('created_at') . ')')) ?? $now->year);

        return Inertia::render('DataRejectSanitary/Index', [
            'rows' => $data,
            'columns' => $columns,
            'totals' => [
                'Body' => $totalBody,
                'Tangki' => $totalTangki,
            ],
            'filter' => [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'daftar_bulan' => [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'],
                'daftar_tahun' => collect(range($minYear, $now->year))->reverse()->values()->all(),
            ],
        ]);
    }

    private function findProcess($processes, string $name)
    {
        return $processes->first(fn ($item) => strcasecmp(trim($item->proses), $name) === 0);
    }

    private function emptyRow(array $columns): array
    {
        $row = [];
        foreach ($columns as $column) {
            $row[$column['key']] = 0;
        }
        return $row;
    }

    private function rejectKeys($row, ?int $castingId, ?int $reworkId, ?int $solarId, ?int $agingId, ?int $opReworkId, ?int $opSolarId, ?int $opSprayId, ?int $qcBeforeSprayOvenId, ?int $opOvenId, ?int $opFinishGoodId, ?int $ovenBongkarUrutan): array
    {
        if ($row->proses_id === $castingId) {
            return [];
        }

        if ($row->status_kondisi !== 'Buang') {
            return [];
        }

        if ($ovenBongkarUrutan !== null && $row->urutan >= $ovenBongkarUrutan) {
            return ['reject_buang_matang'];
        }

        return match ($row->proses_id) {
            $reworkId => ['reject_buang_basah', 'reject_buang_inproses_rework'],
            $solarId => ['reject_buang_basah', 'reject_buang_inproses_solar'],
            $agingId => ['reject_buang_aging'],
            $opReworkId => ['reject_buang_op_rework'],
            $opSolarId => ['reject_buang_op_solar'],
            $opSprayId => ['reject_buang_op_spray'],
            $qcBeforeSprayOvenId => ['reject_buang_qc_before_spray_oven'],
            $opOvenId => ['reject_buang_op_oven'],
            $opFinishGoodId => ['reject_buang_op_finish_good'],
            default => ['reject_buang_basah'],
        };
    }

    private function isCastingOutput($row, ?int $castingId, ?int $reworkId): bool
    {
        return $row->proses_id === $castingId || ($reworkId && $row->proses_id === $reworkId && in_array($row->status_kondisi, ['OK', 'In Proses'], true));
    }
}
