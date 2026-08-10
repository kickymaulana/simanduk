<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;

#[Signature('import:server-data {--file= : Path file dump SQL (default: .sql paling baru di storage/app/dumps)}')]
#[Description('Import data dari dump server (phpMyAdmin) ke database simanduk — replace total tabel data.')]
class ImportServerData extends Command
{
    private string $stagingDb = 'simanduk_import';

    /** Tabel yang di-copy dari staging ke simanduk (tanpa `produk` yang ditransform). */
    private array $tables = [
        'users', 'departemen', 'proses', 'shift', 'cacat', 'aturan_penolakan',
        'kualitas', 'warna',
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'sesi_kerja', 'sesi_kerja_member', 'pengerjaan_produk', 'pengerjaan_cacat',
    ];

    public function handle(): int
    {
        $file = $this->resolveDumpFile();
        if (! $file) {
            return self::FAILURE;
        }

        if (! $this->ensureStaging()) {
            return self::FAILURE;
        }

        if (! $this->loadDumpIntoStaging($file)) {
            return self::FAILURE;
        }

        $conn = $this->pdo();
        $conn->exec('SET FOREIGN_KEY_CHECKS=0');

        $this->copyTables($conn);
        $this->copyProdukWithProsesMapping($conn);

        $conn->exec('SET FOREIGN_KEY_CHECKS=1');
        $this->dropStaging();

        $produk = DB::table('produk')->count();
        $pengerjaan = DB::table('pengerjaan_produk')->count();
        $prosesTerisi = DB::table('produk')->whereNotNull('proses_id')->count();

        $this->newLine();
        $this->info('✅ Import selesai.');
        $this->info("Total produk : {$produk}");
        $this->info("Total pengerjaan : {$pengerjaan}");
        $this->info("Produk dgn proses_id : {$prosesTerisi}");

        return self::SUCCESS;
    }

    private function resolveDumpFile(): ?string
    {
        $override = $this->option('file');
        $dir = storage_path('app/dumps');

        if ($override) {
            $path = is_file($override) ? $override : base_path($override);
            if (is_file($path)) {
                $this->info("Memakai file: {$path}");
                return $path;
            }
            $this->error("File tidak ditemukan: {$override}");
            return null;
        }

        if (! is_dir($dir)) {
            $this->error("Folder dumps belum ada: {$dir}");
            return null;
        }

        $files = glob($dir . '/*.sql');
        if (! $files) {
            $this->error("Tidak ada file .sql di {$dir}");
            return null;
        }

        usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $this->info('Memakai dump terbaru: ' . $files[0]);
        return $files[0];
    }

    private function connParams(): array
    {
        return [
            Config::get('database.connections.mariadb.host'),
            Config::get('database.connections.mariadb.port'),
            Config::get('database.connections.mariadb.username'),
            Config::get('database.connections.mariadb.password'),
        ];
    }

    private function pdo(string $db = null): PDO
    {
        [$host, $port, $user, $pass] = $this->connParams();
        $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $host, $port);
        if ($db) {
            $dsn .= ';dbname=' . $db;
        }
        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    private function prodDb(): string
    {
        return Config::get('database.connections.mariadb.database');
    }

    private function ensureStaging(): bool
    {
        try {
            $pdo = $this->pdo();
            $pdo->exec("DROP DATABASE IF EXISTS `{$this->stagingDb}`");
            $pdo->exec("CREATE DATABASE `{$this->stagingDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("DB staging siap: {$this->stagingDb}");
            return true;
        } catch (\Throwable $e) {
            $this->error('Gagal membuat DB staging: ' . $e->getMessage());
            return false;
        }
    }

    private function loadDumpIntoStaging(string $file): bool
    {
        [$host, $port, $user, $pass] = $this->connParams();

        $base = sprintf(
            '"%s" -h%s -P%s -u%s -p%s',
            $this->mysqlBinary(),
            $host,
            $port,
            $user,
            $pass
        );

        $this->info('Mengimpor dump (mungkin beberapa detik)...');
        $fullCommand = $base . ' ' . escapeshellarg($this->stagingDb) . ' < ' . escapeshellarg($file);

        $out = [];
        $code = 0;
        exec($fullCommand, $out, $code);

        if ($code !== 0) {
            $this->error('Gagal mengimpor dump. Output: ' . implode(PHP_EOL, array_slice($out, -8)));
            return false;
        }
        return true;
    }

    private function mysqlBinary(): string
    {
        $env = getenv('MYSQL_BIN');
        if ($env && is_file($env)) {
            return $env;
        }

        $find = shell_exec('where mysql 2>nul');
        if ($find && trim($find)) {
            $first = trim(explode(PHP_EOL, $find)[0]);
            if (is_file($first)) {
                return $first;
            }
        }

        return 'mysql';
    }

    /** Copy tabel identik (kolom yang sama-sama ada) dari staging ke simanduk. */
    private function copyTables(PDO $conn): void
    {
        $prodDb = $this->prodDb();

        foreach ($this->tables as $table) {
            try {
                $columns = $this->sharedColumns($conn, $table);
                if (! $columns) {
                    $this->warn("  ⚠ {$table} dilewati (tidak ada kolom cocok).");
                    continue;
                }

                $list = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

                $conn->exec("TRUNCATE TABLE `{$prodDb}`.`{$table}`");
                $conn->exec(sprintf(
                    'INSERT INTO `%s`.`%s` (%s) SELECT %s FROM `%s`.`%s`',
                    $prodDb,
                    $table,
                    $list,
                    $list,
                    $this->stagingDb,
                    $table
                ));
                $this->line("  ✓ {$table}");
            } catch (\Throwable $e) {
                $this->warn("  ⚠ {$table} dilewati: " . $e->getMessage());
            }
        }
    }

    /** Kolom yang sama-sama ada di staging dan simanduk untuk sebuah tabel. */
    private function sharedColumns(PDO $conn, string $table): array
    {
        $target = $this->tableColumns($conn, $this->prodDb(), $table);
        $source = $this->tableColumns($conn, $this->stagingDb, $table);

        return array_values(array_intersect($target, $source));
    }

    private function tableColumns(PDO $conn, string $db, string $table): array
    {
        $q = $conn->query(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = " . $conn->quote($db)
            . " AND TABLE_NAME = " . $conn->quote($table)
        );
        return $q->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Produk: transform proses_id dari tabel troli (id = troli_id). */
    private function copyProdukWithProsesMapping(PDO $conn): void
    {
        $prodDb = $this->prodDb();

        try {
            $conn->exec("TRUNCATE TABLE `{$prodDb}`.`produk`");
            $conn->exec(sprintf(
                'INSERT INTO `%s`.`produk`
                    (id, nomor_mesin, nomor_mould, asal_slip, qrcode, nama, jenis,
                     status_akhir, sudah_scan, kualitas_id, warna_id, proses_id,
                     created_at, updated_at)
                 SELECT p.id, p.nomor_mesin, p.nomor_mould, p.asal_slip, p.qrcode, p.nama, p.jenis,
                        p.status_akhir, p.sudah_scan, p.kualitas_id, p.warna_id, t.proses_id,
                        p.created_at, p.updated_at
                 FROM `%s`.`produk` p
                 LEFT JOIN `%s`.`troli` t ON t.id = p.troli_id',
                $prodDb,
                $this->stagingDb,
                $this->stagingDb
            ));
            $this->line('  ✓ produk (transform proses_id dari troli)');
        } catch (\Throwable $e) {
            $this->warn('  ⚠ produk gagal: ' . $e->getMessage());
        }
    }

    private function dropStaging(): void
    {
        try {
            $this->pdo()->exec("DROP DATABASE IF EXISTS `{$this->stagingDb}`");
            $this->info('DB staging dihapus.');
        } catch (\Throwable $e) {
            $this->warn('Gagal drop staging: ' . $e->getMessage());
        }
    }
}