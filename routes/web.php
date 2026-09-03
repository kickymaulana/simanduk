<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\RoleController;
use App\Http\Controllers\Master\DepartemenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Master\CacatController;
use App\Http\Controllers\Master\AturanPenolakanController;
use App\Http\Controllers\SesiKerjaController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\QrBelumDiscanController;
use App\Http\Controllers\Master\ProsesController;
use App\Http\Controllers\Master\OvenController;
use App\Http\Controllers\Master\ShiftController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\RiwayatScanMasukController;
use App\Http\Controllers\TotalPengerjaanUserController;
use App\Http\Controllers\TemuanRejectQcController;
use App\Http\Controllers\ProsesProduksiController;
use App\Http\Controllers\StokController;
use App\Http\Controllers\LaporanKualitasController;
use App\Http\Controllers\LaporanProdukBuangController;
use App\Http\Controllers\LaporanScanController;
use App\Http\Controllers\LaporanTraceController;
use App\Http\Controllers\Master\KualitasController;
use App\Http\Controllers\Master\WarnaController;
use App\Http\Controllers\PeriksaController;
use App\Http\Controllers\KoreksiScanController;
use App\Http\Controllers\SettingController;



Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'index'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::get('register', [RegisterController::class, 'index'])->name('register');
    Route::post('register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth', 'role:admin')->group(function () {
    Route::get('master/users', [UserController::class, 'index'])->name('users.index');
    Route::get('master/users/pending', [UserController::class, 'pending'])->name('users.pending');
    Route::post('master/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
    Route::post('master/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');
    Route::get('master/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('master/users/create', [UserController::class, 'store'])->name('users.store');
    Route::get('master/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('master/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('master/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('master/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('master/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::get('master/shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
    Route::post('master/shifts/create', [ShiftController::class, 'store'])->name('shifts.store');
    Route::get('master/shifts/{shift}/edit', [ShiftController::class, 'edit'])->name('shifts.edit');
    Route::put('master/shifts/{shift}', [ShiftController::class, 'update'])->name('shifts.update');
    Route::delete('master/shifts/{shift}', [ShiftController::class, 'destroy'])->name('shifts.destroy');

    Route::get('master/ovens', [OvenController::class, 'index'])->name('ovens.index');
    Route::get('master/ovens/create', [OvenController::class, 'create'])->name('ovens.create');
    Route::post('master/ovens/create', [OvenController::class, 'store'])->name('ovens.store');
    Route::get('master/ovens/{oven}/edit', [OvenController::class, 'edit'])->name('ovens.edit');
    Route::put('master/ovens/{oven}', [OvenController::class, 'update'])->name('ovens.update');
    Route::delete('master/ovens/{oven}', [OvenController::class, 'destroy'])->name('ovens.destroy');

    Route::get('master/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('master/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('master/roles/create', [RoleController::class, 'store'])->name('roles.store');
    Route::get('master/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('master/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('master/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

    Route::get('master/departemens', [DepartemenController::class, 'index'])->name('departemens.index');
    Route::get('master/departemens/create', [DepartemenController::class, 'create'])->name('departemens.create');
    Route::post('master/departemens/create', [DepartemenController::class, 'store'])->name('departemens.store');
    Route::get('master/departemens/{departemen}/edit', [DepartemenController::class, 'edit'])->name('departemens.edit');
    Route::put('master/departemens/{departemen}/edit', [DepartemenController::class, 'update'])->name('departemens.update');
    Route::delete('master/departemens/{departemen}', [DepartemenController::class, 'destroy'])->name('departemens.destroy');

    Route::get('master/proses', [ProsesController::class, 'index'])->name('proses.index');
    Route::get('master/proses/create', [ProsesController::class, 'create'])->name('proses.create');
    Route::post('master/proses/create', [ProsesController::class, 'store'])->name('proses.store');
    Route::get('master/proses/{proses}/edit', [ProsesController::class, 'edit'])->name('proses.edit');
    Route::put('master/proses/{proses}/edit', [ProsesController::class, 'update'])->name('proses.update');
    Route::post('master/proses/{proses}/toggle-active', [ProsesController::class, 'toggleActive'])->name('proses.toggle_active');

    Route::get('master/cacats', [CacatController::class, 'index'])->name('cacats.index');
    Route::get('master/cacats/create', [CacatController::class, 'create'])->name('cacats.create');
    Route::post('master/cacats/create', [CacatController::class, 'store'])->name('cacats.store');
    Route::get('master/cacats/{cacat}/edit', [CacatController::class, 'edit'])->name('cacats.edit');
    Route::put('master/cacats/{cacat}/edit', [CacatController::class, 'update'])->name('cacats.update');
    Route::delete('master/cacats/{cacat}', [CacatController::class, 'destroy'])->name('cacats.destroy');

    Route::get('master/aturan-penolakans', [AturanPenolakanController::class, 'index'])->name('aturanpenolakans.index');
    Route::get('master/aturan-penolakans/create', [AturanPenolakanController::class, 'create'])->name('aturanpenolakans.create');
    Route::post('master/aturan-penolakans/create', [AturanPenolakanController::class, 'store'])->name('aturanpenolakans.store');
    Route::get('master/aturan-penolakans/{cacat}/edit', [AturanPenolakanController::class, 'edit'])->name('aturanpenolakans.edit');
    Route::put('master/aturan-penolakans/{cacat}/edit', [AturanPenolakanController::class, 'update'])->name('aturanpenolakans.update');
    Route::delete('master/aturan-penolakans/{cacat}', [AturanPenolakanController::class, 'destroy'])->name('aturanpenolakans.destroy');

    Route::get('master/kualitas', [KualitasController::class, 'index'])->name('kualitas.index');
    Route::get('master/kualitas/create', [KualitasController::class, 'create'])->name('kualitas.create');
    Route::post('master/kualitas/create', [KualitasController::class, 'store'])->name('kualitas.store');
    Route::get('master/kualitas/{kualitas}/edit', [KualitasController::class, 'edit'])->name('kualitas.edit');
    Route::put('master/kualitas/{kualitas}/edit', [KualitasController::class, 'update'])->name('kualitas.update');
    Route::delete('master/kualitas/{kualitas}', [KualitasController::class, 'destroy'])->name('kualitas.destroy');

    Route::get('master/warna', [WarnaController::class, 'index'])->name('warna.index');
    Route::get('master/warna/create', [WarnaController::class, 'create'])->name('warna.create');
    Route::post('master/warna/create', [WarnaController::class, 'store'])->name('warna.store');
    Route::get('master/warna/{warna}/edit', [WarnaController::class, 'edit'])->name('warna.edit');
    Route::put('master/warna/{warna}/edit', [WarnaController::class, 'update'])->name('warna.update');
    Route::delete('master/warna/{warna}', [WarnaController::class, 'destroy'])->name('warna.destroy');

    Route::get('koreksi-scan', [KoreksiScanController::class, 'index'])->name('koreksi.scan');
    Route::post('koreksi-scan/cari', [KoreksiScanController::class, 'cari'])->name('koreksi.scan.cari');
    Route::post('koreksi-scan/{produk}/batalkan', [KoreksiScanController::class, 'batalkan'])->name('koreksi.scan.batalkan');

    Route::get('pengaturan', [SettingController::class, 'index'])->name('pengaturan');
    Route::post('pengaturan/toggle-cek-urutan', [SettingController::class, 'toggleCekUrutan'])->name('pengaturan.toggle_cek_urutan');
    Route::post('pengaturan/cut-off', [SettingController::class, 'updateCutOff'])->name('pengaturan.cut_off');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('sesi-kerjas', [SesiKerjaController::class, 'index'])->name('sesikerjas.index');
    Route::get('sesi-kerjas/create', [SesiKerjaController::class, 'create'])->name('sesikerjas.create');
    Route::post('sesi-kerjas/create', [SesiKerjaController::class, 'store'])->name('sesikerjas.store');
    Route::get('sesi-kerjas/{sesikerja}', [SesiKerjaController::class, 'show'])->name('sesikerjas.show');
    Route::get('sesi-kerjas/{sesikerja}/riwayat-scan', [SesiKerjaController::class, 'riwayat_scan'])->name('sesikerjas.riwayat_scan');
    Route::get('sesi-kerjas/{sesikerja}/edit', [SesiKerjaController::class, 'edit'])->name('sesikerjas.edit');
    Route::put('sesi-kerjas/{sesikerja}/edit', [SesiKerjaController::class, 'update'])->name('sesikerjas.update');
    Route::post('sesi-kerjas/{sesikerja}/aktifkan', [SesiKerjaController::class, 'aktifkan'])->name('sesikerjas.aktifkan');
    Route::delete('sesi-kerjas/{sesikerja}/nonaktif', [SesiKerjaController::class, 'nonaktif'])->name('sesikerjas.nonaktif');
    Route::delete('sesi-kerjas/{sesikerja}', [SesiKerjaController::class, 'destroy'])->name('sesikerjas.destroy');

    Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
    Route::get('scan/awal', [ScanController::class, 'awal'])->name('scan.awal');
    Route::post('scan/awal', [ScanController::class, 'awal_store'])->name('scan.awal_store');
    Route::get('scan/validasi', [ScanController::class, 'validasi'])->name('scan.validasi');
    Route::post('scan/validasi', [ScanController::class, 'validasi_store'])->name('scan.validasi_store');
    Route::get('scan/inproses', [ScanController::class, 'inproses'])->name('scan.inproses');
    Route::post('scan/inproses', [ScanController::class, 'inproses_store'])->name('scan.inproses_store');
    Route::get('scan/buang', [ScanController::class, 'buang'])->name('scan.buang');
    Route::post('scan/buang', [ScanController::class, 'buang_store'])->name('scan.buang_store');
    Route::get('scan/checking', [ScanController::class, 'checking'])->name('scan.checking');
    Route::post('scan/checking', [ScanController::class, 'checking_store'])->name('scan.checking_store');
    Route::get('scan/checking/{mode?}', [ScanController::class, 'checking'])->name('scan.checking.mode')
        ->whereIn('mode', ['inproses', 'buang']);
    Route::post('scan/checking-inproses', [ScanController::class, 'checking_inproses_store'])->name('scan.checking.inproses_store');
    Route::post('scan/checking-buang', [ScanController::class, 'checking_buang_store'])->name('scan.checking.buang_store');

    Route::get('riwayat-scan-masuk', [RiwayatScanMasukController::class, 'index'])->name('riwayat.scan.masuk');
    Route::get('total-pengerjaan-user', [TotalPengerjaanUserController::class, 'index'])->name('total.pengerjaan.user');
    Route::get('temuan-reject-qc', [TemuanRejectQcController::class, 'index'])->name('temuan.reject.qc');

    Route::get('produk/{id}', [ProdukController::class, 'show'])->name('produk.show');
    Route::post('produk/{produk}/fix-jenis', [ProdukController::class, 'fixJenis'])->name('produk.fix_jenis');
    Route::get('produk', [ProdukController::class, 'dataprodukindex'])->name('produk.index');

    Route::get('proses-produksi', [ProsesProduksiController::class, 'index'])->name('proses.produksi');
    Route::get('stok', [StokController::class, 'index'])->name('stok');

    Route::get('periksa', [PeriksaController::class, 'periksa'])->name('periksa');
    Route::post('periksa', [PeriksaController::class, 'periksa_post'])->name('periksa_post');

    Route::get('qr-belum-discan', [QrBelumDiscanController::class, 'index'])->name('qr.belum.discan');
    Route::get('qr-belum-discan/{proses}', [QrBelumDiscanController::class, 'detail'])->name('qr.belum.discan.show');

    Route::get('laporan-scan', [LaporanScanController::class, 'index'])->name('laporan.scan');

    Route::get('laporan-kualitas', [LaporanKualitasController::class, 'index'])->name('laporan.kualitas');

    Route::get('laporan-produk-buang', [LaporanProdukBuangController::class, 'index'])->name('laporan.produk.buang');

    Route::get('laporan-trace-operator', [LaporanTraceController::class, 'operator'])->name('laporan.trace.operator');
    Route::get('laporan-trace-mesin', [LaporanTraceController::class, 'mesin'])->name('laporan.trace.mesin');
});
