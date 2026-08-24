# TASKS — Hasil Analisis Legacy Decoder (Simanduk)

> Prioritas: high/medium/low. Diperbarui 2026-08-22.

## Selesai (sudah berjalan)
- ✅ Cek jenis produk (Body/Tangki) + form perbaikan inline (`produk.fix_jenis`).
- ✅ Backfill tanggal sesi: command `sesi:backfill-tanggal-masuk`.
- ✅ Fix jenis produk lama: command `produk:fix-jenis` (±1293).
- ✅ Master Oven (CRUD admin) + `produk.oven_id` + select di scan validasi (proses Oven Susun).
- ✅ Laporan Scan di-rename (`laporan-scan`) + filter jenis + fix actual `COUNT(DISTINCT ...)`.
- ✅ Laporan Kualitas (`laporan-kualitas`).
- ✅ Laporan Produk Buang (`laporan-produk-buang`).
- ✅ Temuan Reject QC (`temuan-reject-qc`, khusus user departemen QC).
- ✅ Laporan Trace Operator & Mesin (`laporan-trace-operator`, `laporan-trace-mesin`).
- ✅ Kolom Scan Terakhir di QR Belum Discan (`Produk::latestPengerjaan`).

## High
1. **Input data Visco / Oven Bongkar / Temp** — 3 kolom di Laporan Trace Mesin masih kosong (belum ada capture). Perlu desain input (di scan Spray untuk Visco? di scan Oven untuk Temp/Oven Bongkar?) + simpan ke `pengerjaan_produk` atau `produk`.

## Medium
2. **Uji `import:server-data`** — dump server lama harus masih punya tabel `troli` untuk memetakan `produk.proses_id`; pastikan staging `simanduk_import` + reset aktif user/proses bekerja.
3. **Periksa `PeriksaController`** (`/periksa`) — flow periksa/QC masih aktif dan tidak memakai sisa logika troli.
4. **Cek drop proses nonaktif di dropdown sesi, ProsesProduksi, Stok, QR Belum Discan** — konsisten filter `is_active=true`.

## Low
5. **Tambahkan test untuk laporan-laporan baru** — `LaporanScan`, `LaporanKualitas`, `LaporanProdukBuang`, `LaporanTrace`, `TemuanRejectQc` belum punya test.
6. **Format PHP via `vendor/bin/pint`** — tidak ada script lint di composer.json; seragamkan style jika akan dipakai.
