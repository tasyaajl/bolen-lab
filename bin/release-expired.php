<?php

declare(strict_types=1);

/**
 * Batalkan pesanan yang melewati tenggat pembayaran dan kembalikan stoknya.
 *
 * Jalankan manual: php bin/release-expired.php
 * Jadwalkan via cron (contoh tiap 15 menit):
 *   0,15,30,45 * * * * php /path/ke/balebolen/bin/release-expired.php
 *
 * Hanya pesanan berstatus pembayaran "unpaid" yang diproses. Pesanan yang
 * sudah mengunggah bukti (awaiting_verification) menunggu verifikasi admin.
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

require BASE_PATH . '/app/Core/helpers.php';

App\Core\Env::load(BASE_PATH . '/.env');
$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);
App\Core\Database::configure($config['db']);

$released = App\Models\Order::releaseExpired();

$line = sprintf("[%s] release-expired: %d pesanan dibatalkan, stok dikembalikan.\n", now(), $released);
echo $line;

@file_put_contents(BASE_PATH . '/storage/logs/app.log', $line, FILE_APPEND);
