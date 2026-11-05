Project: Simanduk Laravel 13.3 + Inertia/Vue, branch hilangkan_troli.

Perubahan terakhir:
- app/Http/Controllers/ScanController.php: checking, checking_inproses, dan checking_buang sekarang menerima merk_id nullable.
- Handler memakai null-safe access; tanpa merk, produk dan pengerjaan menyimpan merk_id/kualitas_id null.
- warna_id tetap nullable; cacat Buang tetap wajib minimal satu.

Verifikasi:
- php -l app/Http/Controllers/ScanController.php: berhasil.
- vendor/bin/pint --test app/Http/Controllers/ScanController.php: berhasil.
- npm run build: berhasil; hanya warning font runtime yang sudah ada.
- composer test: 53 passed, 1 existing failure (tests/Feature/ExampleTest.php expects GET / = 200 but app returns 404).

Kondisi kerja: jangan commit tanpa permintaan eksplisit. Uncommitted change utama ada di ScanController.php.