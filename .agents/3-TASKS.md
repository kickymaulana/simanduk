# TASKS — Hasil Analisis Legacy Decoder (Simanduk)

> Prioritas: high/medium/low. Verifikasi tiap temuan sebelum dikerjakan.

## High
1. **Validasi rework QC berfungsi penuh** — aturan duplicate-scan hanya izinkan `In Proses→In Proses` dan `In Proses→OK` di departemen QC (`ScanController::prosesScan`). Pastikan tidak ada celah jalur lain (mis. `OK→OK`).

## Medium
2. **Uji `import:server-data`** — dump server lama harus masih punya tabel `troli` untuk memetakan `produk.proses_id`; pastikan staging `simanduk_import` + reset aktif user/proses bekerja, dan urutan aman (import dulu, lalu nonaktifkan proses).
3. **Periksa `PeriksaController`** (`/periksa`) — flow periksa/QC masih aktif dan tidak memakai sisa logika troli.
4. **Cek drop proses nonaktif di dropdown sesi, ProsesProduksi, Stok, QR Belum Discan** — konsisten filter `is_active=true`.

## Low
5. **Tambah test untuk `LaporanScanPerbulanController`** — fitur laporan baru, belum ada test.
6. **Format PHP via `vendor/bin/pint`** — tidak ada script lint di composer.json; seragamkan style jika akan dipakai.
7. **Dokumentasi** — pertimbangkan `2-TECH-SPEC.md` dari hasil analisis (tanya user).
