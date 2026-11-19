<?php

declare(strict_types=1);

/**
 * Runner migrasi BaleBolen.
 *
 * Jalankan: php database/migrate.php
 * Semua berkas *.sql di database/migrations dijalankan sekali, berurutan,
 * dan dicatat pada tabel `migrations`. Berkas *_rollback.sql dilewati.
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/Env.php';

App\Core\Env::load(BASE_PATH . '/.env');
$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);
App\Core\Database::configure($config['db']);

$pdo = App\Core\Database::connection();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS migrations (
        filename   VARCHAR(190) PRIMARY KEY,
        applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$applied = [];
foreach ($pdo->query('SELECT filename FROM migrations')->fetchAll() as $row) {
    $applied[(string) $row['filename']] = true;
}

$files = glob(BASE_PATH . '/database/migrations/*.sql') ?: [];
sort($files);

$ran = 0;

foreach ($files as $file) {
    $name = basename($file);

    if (str_contains($name, '_rollback')) {
        continue;
    }

    if (isset($applied[$name])) {
        echo "Lewati (sudah diterapkan): {$name}\n";
        continue;
    }

    $sql = (string) file_get_contents($file);
    // Buang baris komentar lalu pecah per titik koma di akhir baris.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = array_filter(
        array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: []),
        static fn (string $statement): bool => $statement !== ''
    );

    echo "Menerapkan: {$name}\n";

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    $stmt = $pdo->prepare('INSERT INTO migrations (filename) VALUES (:f)');
    $stmt->execute(['f' => $name]);
    $ran++;
}

echo $ran === 0 ? "Tidak ada migrasi baru.\n" : "Selesai. {$ran} migrasi diterapkan.\n";
