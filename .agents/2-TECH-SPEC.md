# TECH SPEC — Simanduk

> Hasil analisis `legacy-decoder`. Diperbarui 2026-08-22.

## 1. Tech Stack
- **Backend**: Laravel v13.3 (PHP 8.3). `barryvdh/laravel-dompdf` (PDF), `tightenco/ziggy` (route JS), `laravel/mcp`.
- **Frontend**: Inertia.js + Vue 3 (TypeScript) + Vite 8 + Tailwind 4. UI kit shadcn-style di `resources/js/components/ui/` (reka-ui + cva). Ikon `@tabler/icons-vue`, `@radix-icons/vue`. Tabel `@tanstack/vue-table`.
- **Auth/Roles**: session-based; `spatie/laravel-permission`. Alias middleware `role` = Spatie (`bootstrap/app.php`), route admin dibungkus `role:admin`.
- **Database**: MariaDB, DB **`simanduk`**, koneksi `mariadb` (`config/database.php`). Kredensial di `.env`.
- **MCP**: `App\Mcp\Servers\AppFlowServer` (routes/ai.php), `php artisan mcp:start appflow`.
- **URL lokal**: `http://localhost/simanduk/public` (`.env` `APP_URL` sinkron nama folder).

## 2. DB Design
Tabel aktif ±28, tiga grup:

**Master**
- `users` (+ kolom `status` = antri/aktif/ditolak)
- `departemen`, `proses` (departemen_id, urutan, is_active), `shift`
- `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang)
- `kualitas`, `warna`, `oven` (id, oven unique, timestamps)
- spatie: roles, permissions, model_has_roles, role_has_permissions

**Sesi**
- `sesi_kerja` (leader_id, shift_id, proses_id, jenis Body/Tangki, target nullable, tanggal_masuk)
- `sesi_kerja_member` (sesi_kerja_id, user_id)

**Operasional**
- `produk` (qrcode unik 10 char, jenis, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, oven_id, nomor_mesin, nomor_mould, asal_slip, is_sample, kode_sampel)
- `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter
- `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id)

**Relasi kunci**: Produk→proses (posisi sekarang); PengerjaanProduk→produk/**sesiKerja**/proses/user; PengerjaanCacat→cacat + PJ (user/proses); Produk→oven.

## 3. Interface
- **Auth**: LoginForm.vue, RegisterForm.vue (Inertia pages).
- **Layout**: `AuthenticatedLayout` + `AppSidebar` (NavMain/NavSecondary/NavUser); `ScanSuccessOverlay.vue` global (flash `scan_qr`+`scan_mode`, error scan, dan **form perbaikan jenis** dari flash `fix_jenis`).
- **Pages utama** (resources/js/pages): Master CRUD (users/shifts/departemens/proses/cacats/aturan_penolakans/kualitas/warna/roles/ovens), Sesi Kerja, Scan (Awal/Validasi/InProses/Buang/Checking), Laporan (Dashboard, RiwayatScanMasuk, TotalPengerjaanUser, TemuanRejectQc, Stok, ProsesProduksi, Periksa, DataProduk, LaporanScan, LaporanKualitas, LaporanProdukBuang, LaporanTrace Operator/Mesin), QR Belum Discan.
- **Data ke frontend** via Inertia props: master data untuk dropdown (proses aktif, mesin/mould/slip, cacat, oven), `scan_counter`.

## 4. Alur
1. **Auth**: register → `status='antri'` (belum bisa login) → admin approve/reject + tentukan role di Master → Persetujuan User.
2. **Sesi Kerja**: leader buat sesi (shift, proses sesuai departemen, jenis, anggota, target opsional, **tanggal_masuk wajib** — untuk SHIFT 3 pilih tanggal shift dimulai) → aktifkan → session `sesi_kerja_id`. Semua scan wajib sesi aktif; hanya leader pembuat yang bisa aktifkan.
3. **Scan (bebas urutan, proses dari sesi aktif)**:
   - `scan/awal` → produk casting baru (wajib jenis + mesin/mould/slip), opsional `is_sample`/`kode_sampel`.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking/{mode?}` → QC + kualitas/warna.
   - **Oven (opsional)**: select hanya saat proses `Oven Susun` → simpan `produk.oven_id`.
   - Setiap scan: `pengerjaan_produk` (leader+anggota) + opsional `pengerjaan_cacat` (PJ dari `aturan_penolakan`).
4. **Aturan keras**: produk `status_akhir='Buang'` final — ditolak semua scan. **Jenis produk harus = jenis sesi** — mismatch → block + form perbaikan inline (`produk.fix_jenis`). Duplicate scan per proses ditolak, KECUALI rework di QC: `In Proses→In Proses` & `In Proses→OK`.
5. **QR Belum Discan** (`/qr-belum-discan`): kandidat produk selesai di proses aktif terdekat sebelum X tapi belum discan di X (proses nonaktif dilewati). Read-only.

## 5. Keamanan
- Semua route `/master/*` dibungkus `role:admin` (Spatie RoleMiddleware).
- Produk `Buang` tak bisa diproses ulang (blokir di `ScanController.php:299`).
- Cek jenis produk vs sesi (blokir di `ScanController.php:289`).
- Validasi: min 1 cacat untuk scan buang; produk tak ditemukan / duplikat scan ditolak.
- Penulisan data scan dibungkus `DB::transaction` (rollback otomatis).
- Auth session + `status` user (antri tak bisa login).

## Catatan
- Sistem troli sudah dihapus total — jangan bangun ulang. Produk pakai `produk.proses_id`.
- Proses tak bisa dihapus permanen — cukup nonaktifkan (`is_active`) + ubah urutan.
- `LaporanTrace` punya kolom kosong (Visco, Oven Bongkar, Temp) — menunggu input data baru.
