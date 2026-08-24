# AGENTS.md

Panduan struktur aplikasi untuk AI agent (opencode) agar memahami proyek ini tanpa dijelaskan dari nol.

## Identitas & Stack
- **Aplikasi**: Simanduk — sistem manajemen produksi closet duduk berbasis scan QR.
- **Stack**: Laravel v13.3 (PHP 8.3) + Inertia.js + Vue 3 (TS) + Vite + Tailwind + MariaDB. Roles: spatie/laravel-permission.
- **URL lokal**: `http://localhost/simanduk/public` (`.env` `APP_URL` harus sinkron dgn nama folder).
- **Database**: MariaDB, DB **`simanduk`** (koneksi `mariadb` di `config/database.php`). Kredensial di `.env`.
- **Branch aktif**: `hilangkan_troli`.

> RULE GIT: Jangan jalankan perintah git tulis (add/commit/push/stage/hapus) kecuali diminta eksplisit. Perintah baca (status/log/diff) boleh.

## Menjalankan
- Dev (sekali jalan, backend+queue+log+vite): `composer dev`. Atau manual: `npm run dev` + Apache / `php artisan serve`.
- Setup penuh dari nol: `composer setup` (install, `.env`, key, migrate, build).
- Build: `npm run build`.
- Eksplorasi MCP: `php artisan mcp:start appflow` (didaftarkan di `opencode.json` key `simanduk`). Tools: `list-routes-tool`, `database-schema-tool`, `list-models-tool`.
- Test: `composer test` (= `config:clear` + `php artisan test`). Satu test: `php artisan test --filter=ScanTanpaTroliTest`. Test ada: `RegisterApprovalTest`, `ScanBuangBlockTest`, `ScanTanpaTroliTest` (+ `ExampleTest` stock yang sering gagal `GET /` → 404, abaikan).
- DB awal: `php artisan migrate --seed` (Seeder wajib — tanpa Role/User admin tidak ada yang bisa login/approve).
- **Backfill tanggal sesi lama**: `php artisan sesi:backfill-tanggal-masuk` — isi `tanggal_masuk` null dari tanggal scan pertama (atau `created_at` sesi jika tanpa scan). Idempotent.
- **Fix jenis produk lama**: `php artisan produk:fix-jenis` — produk `jenis='Body'` yang pengerjaan pertamanya di sesi Tangki → diubah jadi `Tangki` (±1293). ⚠️ Jalankan di server setelah deploy.
- **Import data dari dump server**: `php artisan import:server-data` (atau `--file="path"`). ⚠️ Butuh binary `mysql` di PATH (atau env `MYSQL_BIN`). Dump lama **harus masih punya tabel `troli`** — dipakai untuk memetakan `produk.proses_id`. Meng-copy master + data operasional (Opsi A) lewat DB staging `simanduk_import`. ⚠️ Dump lama TIDAK punya kolom `users.status`, `proses.is_active`, `produk.proses_id` → user & proses di-reset aktif. **Urutan aman: import dulu, lalu set ulang proses yang dinonaktifkan.**

## Konsep Bisnis
Produk = **Body** dan **Tangki** closet duduk (produk terpisah). Tiap produk fisik punya **QR 10 char** (mis. `DN0002343`); QR sudah dicetak sebelum produk dicetak. `produk.id` (integer) = identitas stabil; `produk.qrcode` = kolom unik yang bisa diganti.

## Alur Kerja
1. **Auth** username+password → dashboard. Registrasi menghasilkan user berstatus **`antri`** (belum bisa login) → admin setujui di **Master → Persetujuan User** (`Master/UserController@pending/approve/reject`, kolom `users.status` = antri/aktif/ditolak). Role ditentukan admin saat approval. Middleware role: alias `role` = Spatie (`bootstrap/app.php`), route admin dibungkus `role:admin`.
2. **Sesi Kerja** (`SesiKerjaController`): leader buat sesi (shift, proses sesuai departemen, jenis Body/Tangki, anggota, target opsional, **`tanggal_masuk` WAJIB** = tanggal shift berjalan; untuk SHIFT 3 (00:00-08:00) pilih tanggal saat shift dimulai) lalu **aktifkan** → session `sesi_kerja_id`. Semua scan wajib sesi aktif. Dropdown proses hanya menampilkan `is_active = true`. Admin lihat semua sesi, tapi **hanya leader (pembuat) yang bisa mengaktifkan sesi** (`aktifkan()` guard `leader_id`).
3. **Scan Produk** (`ScanController`, route `/scan/*`) — bebas troli & bebas urutan, proses dari sesi aktif:
   - `scan/awal` → daftar produk baru (casting), produk dibuat dgn `proses_id` sesi + catat leader & semua anggota. **WAJIB** pilih mesin/mould/asal slip + **jenis (Body/Tangki)** + opsional `is_sample`/`kode_sampel`.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking` (`/{mode?}`, mode `inproses`/`buang`) → QC + kualitas/warna.
   - **Pilihan Oven (opsional)**: di scan validasi, select oven hanya muncul saat proses **`Oven Susun`** → simpan `produk.oven_id` (tabel master `oven`).
   - Setiap scan membuat `pengerjaan_produk` (leader + anggota) dan opsional `pengerjaan_cacat` (PJ dari `aturan_penolakan`).
   - ⚠️ **Cek jenis produk vs sesi**: `produk.jenis !== sesi.jenis` → ditolak (`ScanController.php:289`), flash `fix_jenis` → `ScanSuccessOverlay.vue` menampilkan form perbaikan jenis inline (POST `produk/{produk}/fix-jenis`). Berlaku semua scan kecuali awal.
   - ⚠️ **Produk `status_akhir='Buang'` bersifat final — ditolak di semua scan** (blokir di `prosesScan`, ScanController.php:299).
   - ⚠️ **Duplicate scan per proses**: produk sudah discan di proses ini → ditolak, KECUALI rework di departemen **QC**: `In Proses→In Proses` (multi cycle, berulang) dan `In Proses→OK` (final). Tidak ada rework selain itu.
   - Setiap render halaman scan mengirim `scan_counter` (jumlah pengerjaan user di sesi aktif). Sukses scan mengirim flash `scan_qr`+`scan_mode` → ditampilkan **`ScanSuccessOverlay.vue`** di `AuthenticatedLayout`. Saat ganti dropdown mesin/mould/slip di scan awal, kursor otomatis kembali ke kolom scan.
4. **Laporan** (semua via `Laporan*Controller`/dedicated):
   - `laporan-scan` (`LaporanScanController`) — target vs actual per proses per hari, filter Bulan/Tahun/**Jenis** (Semua/Body/Tangki). Actual = `COUNT(DISTINCT produk_id, proses_id)` (bukan `count(*)` — hindari overcount tim). Target dari `sesi_kerja.tanggal_masuk`. ⚠️ Jangan pakai alias kolom `tanggal` pada query `SesiKerja` — model punya aksesor `tanggal()` yang meng-override → null. Pakai kolom asli (`tanggal_masuk`).
   - `laporan-kualitas` (`LaporanKualitasController`) — jumlah produk per kualitas (urutan FG, AB, SG, KW3) & per warna per hari, dari `produk.updated_at`. Dihitung dari snapshot `produk` (kualitas/warna terakhir), bukan riwayat per-scan.
   - `laporan-produk-buang` (`LaporanProdukBuangController`) — jumlah produk `status_akhir='Buang'` per proses per hari.
   - `temuan-reject-qc` (`TemuanRejectQcController`) — daftar `pengerjaan_cacat` yang **`user_scan`-nya di departemen QC** (semua proses), + kolom Jenis & Kualitas produk.
   - `laporan-trace-operator` + `laporan-trace-mesin` (`LaporanTraceController`) — penelusuran per produk: operator per proses (leader sesi), mesin/parameter per proses. Kolom tanpa data (Visco, Oven Bongkar, Temp) tampil `-`. Relasi pengerjaan: `PengerjaanProduk.sesiKerja` (bukan `sesi`).
   - Lainnya: dashboard, riwayat-scan-masuk, total-pengerjaan-user, stok, proses-produksi, periksa, data produk.
5. **QR Belum Discan** (`QrBelumDiscanController`, route `/qr-belum-discan`): daftar kandidat produk yang selesai di proses sebelumnya tapi belum discan di proses ini (kandidat QR lepas/rusak). Kriteria: `produk.proses_id` = **proses aktif terdekat SEBELUM X** (`urutan` lebih kecil & `is_active=true` — proses nonaktif dilewati/loncat), `status_akhir != Buang`, tidak punya `pengerjaan_produk` di proses X, `updated_at` dalam rentang filter (default 7 hari). Hanya proses `is_active=true`. Read-only. Detail punya kolom **Scan Terakhir** (relasi `Produk::latestPengerjaan()` via `latestOfMany()`).

## Perubahan Besar (sudah berjalan — jangan dibangun ulang)
- **Sistem troli DIHAPUS total**: controller (`TroliController`, `Master/MasterTroliController`, `ScanCheckingController`), model (`Troli`, `TroliFisik`), tabel (`troli`, `riwayat_ganti_qr`) tidak ada lagi. `ProdukController` berisi `dataprodukindex`, `show`, `fixJenis`.
- Produk memakai **`produk.proses_id`** (posisi sekarang), bukan troli.
- **Proses tidak bisa dihapus permanen** (`destroy` + route `proses.destroy` dihapus). Admin cukup **nonaktifkan** (`proses.is_active`, toggle di Master → Proses) + ubah urutan. Proses nonaktif disembunyikan dari dropdown sesi, ProsesProduksi, Stok, QR Belum Discan.
- **Stok per departemen**: dikelompokkan per departemen, hanya proses aktif, mengecualikan produk `status_akhir='Buang'`.
- **Cek jenis produk** (Body/Tangki) + form perbaikan inline — lihat Alur Kerja poin 3.
- **Oven**: tabel master `oven` (CRUD admin), `produk.oven_id`, select opsional di scan validasi saat proses `Oven Susun`.

## Struktur DB
- Master: `users` (kolom `status` = antri/aktif/ditolak), `departemen`, `proses` (departemen_id, urutan, is_active), `shift`, `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang), `kualitas`, `warna`, `oven`, spatie role/permission.
- Sesi: `sesi_kerja` (leader_id, shift_id, proses_id, jenis, target nullable, tanggal_masuk), `sesi_kerja_member`.
- Produk: `produk` (qrcode unik, jenis, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, oven_id, nomor_mesin, nomor_mould, asal_slip, is_sample, kode_sampel).
- Riwayat: `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter; `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id).

## Catatan Proses Produksi (kondisi aktual 16 proses, bisa berubah)
Casting(1 Casting, 2 Rework Casting, 3 AGING) → Checking(4) → Solar(5) → QC Pre Spray(6, **nonaktif**) → Spray(7) → QC Pre Oven(8) → Oven Susun(9) → Oven Bongkar(10) → QC Visual & Dimensi(11) → QC Bilas & Kebocoran(12) → QC Pre Packing(13) → Packing Tangki Closet(14)/Packing Body Closet(15) → Loading Finish Good(16). Departemen: Casting, Solar, Spray, Oven, Packing, QC. (ID proses tidak berurutan — urut pakai `urutan`.) Status aktual: `QC Pre Spray` nonaktif; lainnya aktif (cek Master → Proses).
