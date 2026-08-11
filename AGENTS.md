# AGENTS.md

Panduan struktur aplikasi untuk AI agent (opencode) agar memahami proyek ini tanpa dijelaskan dari nol.

## Identitas & Stack
- **Aplikasi**: Simanduk — sistem manajemen produksi closet duduk berbasis scan QR.
- **Stack**: Laravel 13 + Inertia.js + Vue 3 (TS) + Vite + Tailwind + MariaDB.
- **URL lokal**: `http://localhost/closetduduk_v2/public` (folder proyek bernama `closetduduk_v2`, menunggu di-rename menjadi `simanduk`).
- **Database**: MariaDB, nama DB **`simanduk`** (sudah terpisah dari DB lama). Kredensial di `.env`.
- **Branch aktif**: `hilangkan_troli`.

> RULE GIT: Jangan jalankan perintah git tulis (add/commit/push/stage/hapus) kecuali diminta eksplisit. Perintah baca (status/log/diff) boleh.

## Menjalankan
- Dev: `npm run dev` (Vite) + backend Apache / `php artisan serve` → `http://localhost/closetduduk_v2/public`
- Build: `npm run build`
- Eksplorasi MCP: `php artisan mcp:start appflow` (didaftarkan di `opencode.json` key `simanduk`). Tools: `list-routes-tool`, `database-schema-tool`, `list-models-tool`.

## Konsep Bisnis
Produk = **Body** dan **Tangki** closet duduk (produk terpisah). Tiap produk fisik punya **QR 10 char** (mis. `DN0002343`) sebagai identitas; QR sudah dicetak sebelum produk dicetak. `produk.id` (integer) = identitas stabil; `produk.qrcode` = kolom unik yang bisa diganti.

## Alur Kerja
1. **Auth** username+password → dashboard.
2. **Sesi Kerja** (`SesiKerjaController`): leader buat sesi (shift, proses sesuai departemen, jenis Body/Tangki, anggota tim), lalu **aktifkan** → session `sesi_kerja_id`. Semua scan wajib sesi aktif.
3. **Scan Produk** (`ScanController`, route `/scan/*`) — bebas troli, proses dari sesi aktif:
   - `scan/awal` → daftar produk baru (casting), produk dibuat dgn `proses_id` sesi + catat leader & semua anggota.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking` (`/{mode?}`) → QC + kualitas/warna.
   - Setiap scan membuat `pengerjaan_produk` (leader + anggota) dan opsional `pengerjaan_cacat` (PJ dari `aturan_penolakan`).
4. **Laporan**: dashboard, riwayat-scan-masuk, total-pengerjaan-user, log-temuan-reject, stok, proses-produksi, periksa, data produk.
5. **QR Belum Discan** (`QrBelumDiscanController`, route `/qr-belum-discan`): daftar produk yang sudah selesai di proses SEBELUM X tapi belum tercatat discan di proses X (kandidat QR lepas/rusak). Kriteria: `produk.proses_id` = proses sebelum X (berdasar `urutan`), `status_akhir != Buang`, tidak punya `pengerjaan_produk` di proses X, `updated_at` dalam rentang filter (default 7 hari). Read-only.

## Perubahan Besar (penting, sudah berjalan)
- **Sistem troli DIHAPUS total**: controller (`TroliController`, `Master/MasterTroliController`, `ScanCheckingController`), model (`Troli`, `TroliFisik`) dan tabel (`troli`, `riwayat_ganti_qr`) sudah dihapus. `ProdukController` hanya berisi `dataprodukindex` & `show`.
- Produk memakai **`produk.proses_id`** (posisi sekarang), bukan troli.
- **Fitur Ganti QR** (QR rusak/hilang): DITUNDA, belum implementasi. Ide: cari produk kandidat via tanggal+proses, ganti `qrcode`, log. Tabel log `riwayat_ganti_qr` akan dibuat ulang saat fitur dibuat.

## Struktur DB (tabel aktif ±26)
- Master: `users`, `departemen`, `proses` (departemen_id, urutan), `shift`, `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang), `kualitas`, `warna`.
- Sesi: `sesi_kerja` (leader_id, shift_id, proses_id, jenis), `sesi_kerja_member`.
- Produk: `produk` (qrcode unik, jenis, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, nomor_mesin, nomor_mould, asal_slip).
- Riwayat: `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter; `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id).
- Framework: migrations, sessions, cache, jobs, dll (standar).

## Catatan Proses Produksi (15 tahap)
Casting(1-3: Casting, Rework Casting, AGING) → Checking(4) → Solar(5) → QC Pre Spray(6) → Spray(7) → QC Pre Oven(8) → Oven Susun/Bongkar(9-10) → qc VISUAL(11) → QC Pre Packing(12) → Packing Tanki/Body(13-14) → Loading FG(15). Departemen Oven belum punya user terdaftar.