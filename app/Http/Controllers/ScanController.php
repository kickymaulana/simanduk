<?php

namespace App\Http\Controllers;

use App\Models\AturanPenolakan;
use App\Models\Cacat;
use App\Models\Kualitas;
use App\Models\PengerjaanProduk;
use App\Models\Produk;
use App\Models\SesiKerja;
use App\Models\Warna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ScanController extends Controller
{
    private function sesiAktif(): ?SesiKerja
    {
        $id = session('sesi_kerja_id');

        return $id ? SesiKerja::with(['sesi_kerja_members', 'proses'])->find($id) : null;
    }

    public function index()
    {
        return Inertia::render('Scan/Index', [
            'sesiAktif' => $this->sesiAktif(),
        ]);
    }

    // ─────────────────────────── SCAN AWAL (registrasi produk) ───────────────────────────

    public function awal()
    {
        $sesi = $this->sesiAktif();

        return Inertia::render('Scan/Awal', [
            'sesi' => $sesi,
        ]);
    }

    public function awal_store(Request $request)
    {
        $validated = $request->validate([
            'qr'          => ['required', 'string', 'size:10', 'regex:/^[A-Z0-9]+$/', 'unique:produk,qrcode'],
            'nomor_mesin' => 'required|string',
            'nomor_mould' => 'required|string',
            'asal_slip'   => 'required|string',
        ], [
            'qr.unique'       => 'QR Code ini sudah terdaftar.',
            'qr.size'         => 'QR Code harus tepat 10 karakter.',
            'qr.regex'        => 'QR Code hanya boleh huruf besar & angka.',
            'nomor_mesin.required' => 'Pilih nomor mesin!',
            'nomor_mould.required' => 'Pilih nomor mould!',
            'asal_slip.required'   => 'Pilih asal slip!',
        ]);

        $sesi = $this->sesiAktif();

        if (! $sesi) {
            return back()->withErrors(['error' => 'Aktifkan sesi kerja terlebih dahulu.']);
        }

        $qr = strtoupper($validated['qr']);

        try {
            DB::transaction(function () use ($qr, $validated, $sesi) {
                $produk = Produk::create([
                    'qrcode'     => $qr,
                    'nama'       => 'Sample ' . $qr,
                    'jenis'      => $sesi->jenis,
                    'status_akhir' => 'OK',
                    'sudah_scan' => 'Sudah',
                    'proses_id'  => $sesi->proses_id,
                    'nomor_mesin' => $validated['nomor_mesin'] ?? null,
                    'nomor_mould' => $validated['nomor_mould'] ?? null,
                    'asal_slip'   => $validated['asal_slip'] ?? null,
                ]);

                $this->catatPengerjaan($produk, $sesi, 'OK');
            });

            return back()->with('success', "Produk {$qr} berhasil dicatat.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal: ' . $e->getMessage()]);
        }
    }

    // ─────────────────────────── SCAN VALIDASI (OK) ───────────────────────────

    public function validasi()
    {
        return Inertia::render('Scan/Validasi', [
            'sesi' => $this->sesiAktif(),
        ]);
    }

    public function validasi_store(Request $request)
    {
        return $this->prosesScan($request, 'OK', function (Produk $produk, SesiKerja $sesi) {
            $produk->update(['sudah_scan' => 'Sudah', 'status_akhir' => 'OK', 'proses_id' => $sesi->proses_id]);
        });
    }

    // ─────────────────────────── SCAN IN PROSES (cacat toleransi) ───────────────────────────

    public function inproses()
    {
        return Inertia::render('Scan/Inproses', [
            'sesi'          => $this->sesiAktif(),
            'pilihan_cacat' => $this->pilihanCacat(),
        ]);
    }

    public function inproses_store(Request $request)
    {
        $request->validate([
            'qr'        => 'required|string',
            'cacat_ids' => 'nullable|array',
        ]);

        return $this->prosesScan($request, 'In Proses', function (Produk $produk, SesiKerja $sesi) {
            $produk->update(['sudah_scan' => 'Sudah', 'status_akhir' => 'In Proses', 'proses_id' => $sesi->proses_id]);
        }, $request->cacat_ids ?? []);
    }

    // ─────────────────────────── SCAN BUANG (cacat buang) ───────────────────────────

    public function buang()
    {
        return Inertia::render('Scan/Buang', [
            'sesi'          => $this->sesiAktif(),
            'pilihan_cacat' => $this->pilihanCacat(),
        ]);
    }

    public function buang_store(Request $request)
    {
        $request->validate([
            'qr'        => 'required|string',
            'cacat_ids' => 'required|array|min:1',
        ], [
            'cacat_ids.required' => 'Wajib memilih minimal satu jenis cacat untuk membuang produk!',
        ]);

        return $this->prosesScan($request, 'Buang', function (Produk $produk, SesiKerja $sesi) {
            $produk->update(['sudah_scan' => 'Sudah', 'status_akhir' => 'Buang', 'proses_id' => $sesi->proses_id]);
        }, $request->cacat_ids, true);
    }

    // ─────────────────────────── SCAN CHECKING (QC: kualitas & warna) ───────────────────────────

    public function checking($mode = 'ok')
    {
        $page = match ($mode) {
            'inproses' => 'Scan/CheckingInproses',
            'buang'    => 'Scan/CheckingBuang',
            default    => 'Scan/Checking',
        };

        return Inertia::render($page, [
            'sesi'             => $this->sesiAktif(),
            'pilihan_cacat'    => in_array($mode, ['inproses', 'buang']) ? $this->pilihanCacat() : collect(),
            'pilihan_kualitas' => Kualitas::all(['id', 'kualitas']),
            'pilihan_warna'    => Warna::all(['id', 'warna']),
        ]);
    }

    public function checking_store(Request $request)
    {
        $request->validate([
            'qr'          => 'required|string',
            'kualitas_id' => 'nullable|exists:kualitas,id',
            'warna_id'    => 'nullable|exists:warna,id',
        ]);

        return $this->prosesScan($request, 'OK', function (Produk $produk, SesiKerja $sesi) use ($request) {
            $produk->update([
                'sudah_scan'  => 'Sudah',
                'status_akhir' => 'OK',
                'proses_id'   => $sesi->proses_id,
                'kualitas_id' => $request->kualitas_id,
                'warna_id'    => $request->warna_id,
            ]);
        });
    }

    public function checking_inproses_store(Request $request)
    {
        $request->validate([
            'qr'          => 'required|string',
            'cacat_ids'   => 'nullable|array',
            'kualitas_id' => 'nullable|exists:kualitas,id',
            'warna_id'    => 'nullable|exists:warna,id',
        ]);

        return $this->prosesScan($request, 'In Proses', function (Produk $produk, SesiKerja $sesi) use ($request) {
            $produk->update([
                'sudah_scan'  => 'Sudah',
                'status_akhir' => 'In Proses',
                'proses_id'   => $sesi->proses_id,
                'kualitas_id' => $request->kualitas_id,
                'warna_id'    => $request->warna_id,
            ]);
        }, $request->cacat_ids ?? []);
    }

    public function checking_buang_store(Request $request)
    {
        $request->validate([
            'qr'          => 'required|string',
            'cacat_ids'   => 'required|array|min:1',
            'kualitas_id' => 'nullable|exists:kualitas,id',
            'warna_id'    => 'nullable|exists:warna,id',
        ], [
            'cacat_ids.required' => 'Wajib memilih minimal satu jenis cacat untuk membuang produk!',
        ]);

        return $this->prosesScan($request, 'Buang', function (Produk $produk, SesiKerja $sesi) use ($request) {
            $produk->update([
                'sudah_scan'  => 'Sudah',
                'status_akhir' => 'Buang',
                'proses_id'   => $sesi->proses_id,
                'kualitas_id' => $request->kualitas_id,
                'warna_id'    => $request->warna_id,
            ]);
        }, $request->cacat_ids, true);
    }

    // ─────────────────────────── LOGIKA INTI ───────────────────────────

    /**
     * Inti semua scan produk: cari produk by QR (global), catat pengerjaan,
     * update produk. Proses diambil dari sesi aktif (bukan troli).
     */
    private function prosesScan(Request $request, string $statusKondisi, callable $updateProduk, array $cacatIds = [], bool $pakaiProsesBuang = false)
    {
        $sesi = $this->sesiAktif();

        if (! $sesi) {
            return back()->withErrors(['error' => 'Silakan aktifkan Sesi Kerja terlebih dahulu!']);
        }

        $produk = Produk::where('qrcode', $request->qr)->first();

        if (! $produk) {
            return back()->withErrors(['qr' => "Produk {$request->qr} tidak ditemukan di sistem!"]);
        }

        if ($produk->proses_id === $sesi->proses_id && $produk->sudah_scan === 'Sudah') {
            return back()->withErrors(['qr' => "Produk {$request->qr} sudah discan di proses ini!"]);
        }

        try {
            DB::transaction(function () use ($produk, $sesi, $statusKondisi, $updateProduk, $cacatIds, $pakaiProsesBuang) {
                $pengerjaanLeader = $this->catatPengerjaan($produk, $sesi, $statusKondisi);

                if (! empty($cacatIds) && $pengerjaanLeader) {
                    $this->catatCacatDanPj($pengerjaanLeader, $produk, $sesi, $cacatIds, $pakaiProsesBuang);
                }

                $updateProduk($produk, $sesi);
            });

            return back()->with('success', "Produk {$request->qr} berhasil diproses.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
        }
    }

    /**
     * Catat pengerjaan untuk leader + semua anggota tim.
     */
    private function catatPengerjaan(Produk $produk, SesiKerja $sesi, string $statusKondisi)
    {
        $leader = PengerjaanProduk::create([
            'user_id'       => Auth::id(),
            'produk_id'     => $produk->id,
            'sesi_kerja_id' => $sesi->id,
            'proses_id'     => $sesi->proses_id,
            'status_kondisi' => $statusKondisi,
        ]);

        foreach ($sesi->sesi_kerja_members as $member) {
            PengerjaanProduk::create([
                'user_id'       => $member->user_id,
                'produk_id'     => $produk->id,
                'sesi_kerja_id' => $sesi->id,
                'proses_id'     => $sesi->proses_id,
                'status_kondisi' => $statusKondisi,
            ]);
        }

        return $leader;
    }

    /**
     * Catat detail cacat + identifikasi Penanggung Jawab (PJ) dari AturanPenolakan.
     */
    private function catatCacatDanPj(PengerjaanProduk $pengerjaanLeader, Produk $produk, SesiKerja $sesi, array $cacatIds, bool $pakaiProsesBuang)
    {
        foreach ($cacatIds as $cid) {
            $aturan = AturanPenolakan::where('cacat_id', $cid)
                ->where('proses_pemeriksa', $sesi->proses_id)
                ->first();

            $userPJId = null;
            $prosesPJId = null;

            if ($aturan) {
                $prosesPJId = $pakaiProsesBuang ? $aturan->proses_buang : $aturan->proses_toleransi;

                $lastJob = PengerjaanProduk::where('produk_id', $produk->id)
                    ->where('proses_id', $prosesPJId)
                    ->latest('id')
                    ->first();

                $userPJId = $lastJob ? $lastJob->user_id : null;
            }

            $pengerjaanLeader->pengerjaan_cacats()->create([
                'cacat_id'      => $cid,
                'user_scan_id'  => Auth::id(),
                'proses_scan_id' => $sesi->proses_id,
                'user_pj_id'    => $userPJId,
                'proses_pj_id'  => $prosesPJId,
            ]);
        }
    }

    private function pilihanCacat()
    {
        $sesi = $this->sesiAktif();

        return $sesi
            ? Cacat::whereHas('aturan_penolakans', fn ($q) => $q->where('proses_pemeriksa', $sesi->proses_id))
                ->select(['id', 'cacat'])
                ->distinct()
                ->get()
            : collect();
    }
}