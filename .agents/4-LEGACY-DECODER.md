# LEGACY DECODER — Analisis Arsitektur Simanduk

> Dihasilkan oleh skill `legacy-decoder`. Sumber: kode existing + MCP (`simanduk`) + AGENTS.md (diverifikasi 2026-08-20).

## FASE 1 — Stack & Struktur
- **Aplikasi**: Simanduk — manajemen produksi closet duduk berbasis scan QR.
- **Stack**: Laravel v13.3 (PHP 8.3) + Inertia.js + Vue 3 (TS) + Vite 8 + Tailwind 4 + MariaDB. `spatie/laravel-permission` (roles), `barryvdh/laravel-dompdf`, `tightenco/ziggy`, `laravel/mcp`.
- **Frontend kit**: komponen shadcn-style di `resources/js/components/ui/` (reka-ui + cva). Ikon: `@tabler/icons-vue`, `@radix-icons/vue`. Tabel: `@tanstack/vue-table`.
- **Routing**: SPA via Inertia; route di `routes/web.php` (106 route). Route admin dibungkus `role:admin` (alias `role` = Spatie, `bootstrap/app.php`).
- **MCP server**: `App\Mcp\Servers\AppFlowServer` (routes/ai.php), dijalankan `php artisan mcp:start appflow`.
- **URL lokal**: `http://localhost/simanduk/public`. DB: `simanduk`, koneksi `mariadb`.

## FASE 2 — Codebase Mapping
- Entry point: `routes/web.php` → Controller (Inertia `render()` → Vue page di `resources/js/pages/`).
- Controller utama (25 file): `ScanController` (inti alur scan), `SesiKerjaController`, `QrBelumDiscanController`, `LaporanScanPerbulanController`, `LogTemuanRejectController`, `PeriksaController`, `ProdukController`, `ProsesProduksiController`, `RiwayatScanMasukController`, `StokController`, `TotalPengerjaanUserController`, `DashboardController`, `ProfileController`, `Auth/` (Login, Register), `Master/` (CRUD 9 master + User approval).
- `ProdukController` hanya berisi `dataprodukindex` & `show` (sisa troli sudah dihapus).
- Model (13): Produk, Proses, Departemen, Shift, SesiKerja, SesiKerjaMember, PengerjaanProduk, PengerjaanCacat, Cacat, AturanPenolakan, Kualitas, Warna, User.
- Command: `ImportServerData` (`import:server-data`).
- Test (4 fungsional): `RegisterApprovalTest`, `ScanBuangBlockTest`, `ScanTanpaTroliTest`, `ExampleTest`.

## FASE 3 — Business Logic Extraction
1. **Auth**: register → user `status='antri'` → admin approve/reject di Master → Persetujuan User (`Master/UserController`, kolom `users.status` = antri/aktif/ditolak). Role ditentukan admin saat approval.
2. **Sesi Kerja**: leader buat sesi (shift, proses sesuai departemen, jenis Body/Tangki, anggota, target opsional) → aktifkan → session `sesi_kerja_id`. Semua scan WAJIB sesi aktif. Dropdown proses hanya `is_active=true`.
3. **Scan** (bebas urutan, proses dari sesi aktif):
   - `scan/awal` → produk baru (casting), `proses_id` dari sesi, catat leader + anggota. WAJIB mesin/mould/asal slip.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking` (`/{mode?}`) → QC + kualitas/warna.
   - Setiap scan: `pengerjaan_produk` (leader+anggota) + opsional `pengerjaan_cacat` (PJ dari `aturan_penolakan`).
   - ⚠️ `status_akhir='Buang'` = final, ditolak semua scan (blokir `ScanController.php:268`).
   - ⚠️ Duplicate scan per proses ditolak, KECUALI rework di departemen QC: `In Proses→In Proses` (multi cycle) & `In Proses→OK` (final).
   - Flash `scan_qr`+`scan_mode` → `ScanSuccessOverlay.vue` di `AuthenticatedLayout`.
4. **Laporan**: dashboard, riwayat-scan-masuk, total-pengerjaan-user, log-temuan-reject, stok (per departemen), proses-produksi, periksa, data produk, laporan-scan-perbulan.
5. **QR Belum Discan** (`/qr-belum-discan`): produk selesai di proses aktif terdekat SEBELUM X tapi belum discan di X (kandidat QR lepas/rusak). Proses nonaktif dilewati. Read-only.

## FASE 4 — Database Reverse (tabel aktif ±26)
- **Master**: `users` (+status), `departemen`, `proses` (departemen_id, urutan, is_active), `shift`, `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang), `kualitas`, `warna`, spatie role/permission/assignments.
- **Sesi**: `sesi_kerja` (leader_id, shift_id, proses_id, jenis, target nullable), `sesi_kerja_member`.
- **Produk**: `produk` (qrcode unik 10 char, jenis Body/Tangki, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, nomor_mesin, nomor_mould, asal_slip).
- **Riwayat**: `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter; `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id).
- Relasi kunci: Produk→proses (posisi skrg), PengerjaanProduk→sesi/proses/produk/user, PengerjaanCacat→cacat + user_pj/proses_pj.

## Catatan Penting (jangan dibangun ulang)
- **Sistem troli DIHAPUS total**: controller (`TroliController`, `Master/MasterTroliController`, `ScanCheckingController`), model (`Troli`, `TroliFisik`), tabel (`troli`, `riwayat_ganti_qr`) TIDAK ADA. Produk pakai `produk.proses_id`.
- **Proses tidak bisa dihapus permanen** — cukup nonaktifkan (`proses.is_active`, toggle Master → Proses) + ubah urutan. Route `proses.destroy` tidak ada.
- **Proses produksi aktual (15 tahap)**: Casting(1,2,3) → Checking(4) → Solar(5) → QC Pre Spray(6) → Spray(7) → QC Pre Oven(8) → Oven Susun(9)/Bongkar(10) → qc VISUAL(11) → QC Pre Packing(12) → Packing Tanki(13)/Body(14) → Loading FG(15). `QC Pre Spray` nonaktif saat ini. Departemen: Casting, Solar, Spray, Oven, Packing, QC.
