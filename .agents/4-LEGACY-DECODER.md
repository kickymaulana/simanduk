# LEGACY DECODER — Analisis Arsitektur Simanduk

> Dihasilkan oleh skill `legacy-decoder`. Sumber: kode existing + MCP (`simanduk`) + AGENTS.md (diperbarui 2026-08-22).

## FASE 1 — Stack & Struktur
- **Aplikasi**: Simanduk — manajemen produksi closet duduk berbasis scan QR.
- **Stack**: Laravel v13.3 (PHP 8.3) + Inertia.js + Vue 3 (TS) + Vite 8 + Tailwind 4 + MariaDB. `spatie/laravel-permission` (roles), `barryvdh/laravel-dompdf`, `tightenco/ziggy`, `laravel/mcp`.
- **Frontend kit**: komponen shadcn-style di `resources/js/components/ui/` (reka-ui + cva). Ikon: `@tabler/icons-vue`, `@radix-icons/vue`. Tabel: `@tanstack/vue-table`.
- **Routing**: SPA via Inertia; route di `routes/web.php` (±116 route). Route admin dibungkus `role:admin` (alias `role` = Spatie, `bootstrap/app.php`).
- **MCP server**: `App\Mcp\Servers\AppFlowServer` (routes/ai.php), dijalankan `php artisan mcp:start appflow`.
- **URL lokal**: `http://localhost/simanduk/public`. DB: `simanduk`, koneksi `mariadb`.

## FASE 2 — Codebase Mapping
- Entry point: `routes/web.php` → Controller (Inertia `render()` → Vue page di `resources/js/pages/`).
- Controller utama: `ScanController` (inti alur scan), `SesiKerjaController`, `QrBelumDiscanController`, `ProdukController` (dataprodukindex, show, fixJenis), laporan: `LaporanScanController`, `LaporanKualitasController`, `LaporanProdukBuangController`, `LaporanTraceController`, `TemuanRejectQcController`, `LogTemuanRejectController` tidak ada (sudah di-rename), `RiwayatScanMasukController`, `StokController`, `TotalPengerjaanUserController`, `DashboardController`, `ProfileController`, `Auth/` (Login, Register), `Master/` (CRUD 10 master: users, shifts, departemens, proses, cacats, aturan-penolakans, kualitas, warna, roles, ovens + User approval).
- Model (±14): Produk, Proses, Departemen, Shift, SesiKerja, SesiKerjaMember, PengerjaanProduk, PengerjaanCacat, Cacat, AturanPenolakan, Kualitas, Warna, Oven, User.
- Command: `ImportServerData` (`import:server-data`), `BackfillTanggalMasuk` (`sesi:backfill-tanggal-masuk`), `FixJenisProduk` (`produk:fix-jenis`).
- Test (4 fungsional): `RegisterApprovalTest`, `ScanBuangBlockTest`, `ScanTanpaTroliTest`, `ExampleTest` (stock, sering gagal `GET /` → abaikan).

## FASE 3 — Business Logic Extraction
1. **Auth**: register → user `status='antri'` → admin approve/reject di Master → Persetujuan User (`Master/UserController`, kolom `users.status` = antri/aktif/ditolak). Role ditentukan admin saat approval.
2. **Sesi Kerja**: leader buat sesi (shift, proses sesuai departemen, jenis Body/Tangki, anggota, target opsional, **tanggal_masuk wajib**) → aktifkan → session `sesi_kerja_id`. Semua scan WAJIB sesi aktif. Admin lihat semua sesi, hanya leader yang bisa aktifkan (guard `leader_id`). Dropdown proses hanya `is_active=true`.
3. **Scan** (bebas urutan, proses dari sesi aktif):
   - `scan/awal` → produk baru, wajib pilih jenis Body/Tangki + mesin/mould/slip; opsional `is_sample`/`kode_sampel`. `proses_id` dari sesi, catat leader+anggota.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking` (`/{mode?}`) → QC + kualitas/warna.
   - **Oven**: select opsional di validasi hanya saat proses `Oven Susun` → `produk.oven_id`.
   - ⚠️ `status_akhir='Buang'` = final, ditolak semua scan (blokir `ScanController.php:299`).
   - ⚠️ **Cek jenis**: `produk.jenis !== sesi.jenis` → ditolak (`ScanController.php:289`) + flash `fix_jenis` → form perbaikan inline di `ScanSuccessOverlay` (POST `produk.fix_jenis`).
   - ⚠️ Duplicate scan per proses ditolak, KECUALI rework di departemen QC: `In Proses→In Proses` & `In Proses→OK`.
   - Flash `scan_qr`+`scan_mode` → `ScanSuccessOverlay.vue` di `AuthenticatedLayout`.
4. **Laporan**: `laporan-scan` (target vs actual per hari per proses, filter jenis; actual `COUNT(DISTINCT produk_id, proses_id)`, target dari `tanggal_masuk`), `laporan-kualitas` (per kualitas/warna), `laporan-produk-buang` (per proses), `temuan-reject-qc` (cacat yang ditemukan user departemen QC), `laporan-trace-operator` & `laporan-trace-mesin` (trace per produk), plus riwayat-scan-masuk, total-pengerjaan-user, stok, proses-produksi, periksa, data produk.
5. **QR Belum Discan** (`/qr-belum-discan`): produk selesai di proses aktif terdekat SEBELUM X tapi belum discan di X. Proses nonaktif dilewati. Read-only. Kolom Scan Terakhir dari `Produk::latestPengerjaan()` (`latestOfMany()`).

## FASE 4 — Database Reverse (tabel aktif ±28)
- **Master**: `users` (+status), `departemen`, `proses` (departemen_id, urutan, is_active), `shift`, `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang), `kualitas`, `warna`, `oven` (id, oven, timestamps), spatie role/permission/assignments.
- **Sesi**: `sesi_kerja` (leader_id, shift_id, proses_id, jenis, target nullable, tanggal_masuk), `sesi_kerja_member`.
- **Produk**: `produk` (qrcode unik 10 char, jenis Body/Tangki, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, oven_id, nomor_mesin, nomor_mould, asal_slip, is_sample, kode_sampel).
- **Riwayat**: `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter; `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id).
- Relasi kunci: Produk→proses (posisi skrg); PengerjaanProduk→produk/**sesiKerja**/proses/user; PengerjaanCacat→cacat + user_pj/proses_pj.

## Catatan Penting (jangan dibangun ulang)
- **Sistem troli DIHAPUS total** — controller, model, tabel (`troli`, `riwayat_ganti_qr`) TIDAK ADA. Produk pakai `produk.proses_id`.
- **Proses tidak bisa dihapus permanen** — cukup nonaktifkan (`proses.is_active`) + ubah urutan.
- **Cek jenis produk** Body/Tangki + form perbaikan inline (lihat FASE 3).
- **Oven**: master CRUD admin + select validasi saat Oven Susun.
- **Proses produksi (16, urut pakai `urutan`)**: Casting(1,2,3) → Checking(4) → Solar(5) → QC Pre Spray(6 nonaktif) → Spray(7) → QC Pre Oven(8) → Oven Susun(9)/Bongkar(10) → QC Visual & Dimensi(11) → QC Bilas & Kebocoran(12) → QC Pre Packing(13) → Packing Tangki(14)/Body(15) → Loading FG(16). Departemen: Casting, Solar, Spray, Oven, Packing, QC.
