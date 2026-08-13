# AGENTS.md

Panduan struktur aplikasi untuk AI agent (opencode) agar memahami proyek ini tanpa dijelaskan dari nol.

## Identitas & Stack
- **Aplikasi**: Simanduk — sistem manajemen produksi closet duduk berbasis scan QR.
- **Stack**: Laravel 13 + Inertia.js + Vue 3 (TS) + Vite + Tailwind + MariaDB.
- **URL lokal**: `http://localhost/closetduduk_v2/public` (folder proyek bernama `closetduduk_v2`, menunggu di-rename menjadi `simanduk`).
- **Database**: MariaDB, nama DB **`simanduk`** (sudah terpisah dari DB lama `closetduduk_v2`). Kredensial di `.env`.
- **Branch aktif**: `hilangkan_troli`.

> RULE GIT: Jangan jalankan perintah git tulis (add/commit/push/stage/hapus) kecuali diminta eksplisit. Perintah baca (status/log/diff) boleh.

## Menjalankan
- Dev: `npm run dev` (Vite) + backend Apache / `php artisan serve` → `http://localhost/closetduduk_v2/public`
- Build: `npm run build`
- Eksplorasi MCP: `php artisan mcp:start appflow` (didaftarkan di `opencode.json` key `simanduk`). Tools: `list-routes-tool`, `database-schema-tool`, `list-models-tool`.
- **Import data dari server** (`closetdudukv2` / dump lama): `php artisan import:server-data` — ambil `.sql` terbaru di `storage/app/dumps/` (atau `--file="path"`). Import menyalin master + data operasional (Opsi A, ikut produksi penuh). ⚠️ Dump lama TIDAK punya kolom `users.status`, `proses.is_active`, `produk.proses_id` → semua user & proses di-reset aktif. **Urutan aman: import dulu, lalu set ulang proses yang dinonaktifkan.**

## Konsep Bisnis
Produk = **Body** dan **Tangki** closet duduk (produk terpisah). Tiap produk fisik punya **QR 10 char** (mis. `DN0002343`) sebagai identitas; QR sudah dicetak sebelum produk dicetak. `produk.id` (integer) = identitas stabil; `produk.qrcode` = kolom unik yang bisa diganti.

## Alur Kerja
1. **Auth** username+password → dashboard. User baru lewat registrasi berstatus **`antri`** (belum bisa login) → admin setujui di **Master → Persetujuan User** (`Master/UserController@pending/approve/reject`, kolom `users.status` = antri/aktif/ditolak). Role ditentukan admin saat approval. Username bisa diubah sendiri di **Profil** atau oleh admin di Master → Pengguna.
2. **Sesi Kerja** (`SesiKerjaController`): leader buat sesi (shift, proses sesuai departemen, jenis Body/Tangki, anggota tim), lalu **aktifkan** → session `sesi_kerja_id`. Semua scan wajib sesi aktif. Bisa langsung aktifkan sesi lama yang masih sesuai. Dropdown proses hanya menampilkan `is_active = true`.
3. **Scan Produk** (`ScanController`, route `/scan/*`) — bebas troli & bebas urutan, proses dari sesi aktif:
   - `scan/awal` → daftar produk baru (casting), produk dibuat dgn `proses_id` sesi + catat leader & semua anggota. **WAJIB** pilih mesin/mould/asal slip.
   - `scan/validasi` → OK · `scan/inproses` → cacat toleransi · `scan/buang` → cacat buang (min 1) · `scan/checking` (`/{mode?}`) → QC + kualitas/warna.
   - Setiap scan membuat `pengerjaan_produk` (leader + anggota) dan opsional `pengerjaan_cacat` (PJ dari `aturan_penolakan`).
   - ⚠️ **Produk `status_akhir='Buang'` bersifat final — ditolak di semua scan** (di `prosesScan`).
   - Setiap render halaman scan mengirim `scan_counter` (jumlah pengerjaan user di sesi aktif). Sukses scan mengirim flash `scan_qr`+`scan_mode` → ditampilkan komponen **`ScanSuccessOverlay.vue`** (overlay besar fullscreen mirip SweetAlert2 + counter per sesi) di `AuthenticatedLayout`. Saat ganti dropdown mesin/mould/slip di scan awal, kursor otomatis kembali ke kolom scan.
4. **Laporan**: dashboard, riwayat-scan-masuk, total-pengerjaan-user, log-temuan-reject, stok, proses-produksi, periksa, data produk.
5. **QR Belum Discan** (`QrBelumDiscanController`, route `/qr-belum-discan`): daftar kandidat produk yang selesai di proses sebelumnya tapi belum discan di proses ini (kandidat QR lepas/rusak). Kriteria: `produk.proses_id` = **proses aktif terdekat SEBELUM X** (`urutan` lebih kecil & `is_active=true` — proses nonaktif dilewati/loncat), `status_akhir != Buang`, tidak punya `pengerjaan_produk` di proses X, `updated_at` dalam rentang filter (default 7 hari). Hanya menampilkan proses `is_active=true`. Read-only.

## Perubahan Besar (penting, sudah berjalan)
- **Sistem troli DIHAPUS total**: controller (`TroliController`, `Master/MasterTroliController`, `ScanCheckingController`), model (`Troli`, `TroliFisik`) dan tabel (`troli`, `riwayat_ganti_qr`) sudah dihapus. `ProdukController` hanya berisi `dataprodukindex` & `show`.
- Produk memakai **`produk.proses_id`** (posisi sekarang), bukan troli.
- **Proses Aktif/Nonaktif**: kolom `proses.is_active` (default aktif). Admin toggle di Master → Proses (ikon power). Proses nonaktif disembunyikan dari dropdown sesi, ProsesProduksi, Stok, QR Belum Discan. **Tombol hapus proses DIHAPUS** (UI + controller `destroy` + route `proses.destroy`) — proses tidak boleh dihapus permanen, cukup dinonaktifkan + ubah urutan.
- **Stok per departemen**: dikelompokkan per departemen, hanya proses aktif, mengecualikan produk `status_akhir='Buang'`.

## Struktur DB (tabel aktif ±26)
- Master: `users` (kolom `status` = antri/aktif/ditolak), `departemen`, `proses` (departemen_id, urutan, is_active), `shift`, `cacat`, `aturan_penolakan` (cacat_id, proses_pemeriksa + proses_toleransi/proses_buang), `kualitas`, `warna`.
- Sesi: `sesi_kerja` (leader_id, shift_id, proses_id, jenis), `sesi_kerja_member`.
- Produk: `produk` (qrcode unik, jenis, status_akhir, sudah_scan, proses_id, kualitas_id, warna_id, nomor_mesin, nomor_mould, asal_slip).
- Riwayat: `pengerjaan_produk` (produk_id, sesi_kerja_id, user_id, proses_id, status_kondisi) = sumber counter; `pengerjaan_cacat` (pengerjaan_produk_id, cacat_id, user_scan_id, proses_scan_id, user_pj_id, proses_pj_id).
- Framework: migrations, sessions, cache, jobs, dll (standar).

## Catatan Proses Produksi (kondisi aktual ±15 tahap)
Casting(1 Casting, 2 Rework Casting, 3 AGING) → Checking(4) → Solar(5) → QC Pre Spray(6) → Spray(7) → QC Pre Oven(8) → Oven Susun(9)/Bongkar(10) → qc VISUAL(11) → QC Pre Packing(12) → Packing Tanki(13)/Body(14) → Loading FG(15).
**Status aktual**: `QC Pre Spray` nonaktif; proses lain aktif. Departemen: Casting, Solar, Spray, Oven, Packing, QC. (Kondisi bisa berubah karena nonaktif/aktif di Master → Proses.)