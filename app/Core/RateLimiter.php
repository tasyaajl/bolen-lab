<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pembatas laju berbasis database (fixed window).
 *
 * Dipakai untuk mencegah spam checkout, brute force verifikasi akses
 * pesanan, dan penyalahgunaan unggah bukti pembayaran. Karena disimpan
 * di database, batas tidak dapat dilewati hanya dengan membuka sesi baru.
 */
final class RateLimiter
{
    /**
     * Catat satu percobaan dan kembalikan true bila masih diizinkan.
     */
    public static function attempt(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
    {
        self::prune();

        $hash = hash('sha256', $key);
        $row = Database::first(
            'SELECT hits, window_start FROM rate_limits WHERE bucket = :bucket AND key_hash = :hash',
            ['bucket' => $bucket, 'hash' => $hash]
        );

        $now = time();
        $windowStart = $row === null ? false : strtotime((string) $row['window_start']);

        if ($row === null || $windowStart === false || ($now - $windowStart) >= $windowSeconds) {
            Database::run(
                'INSERT INTO rate_limits (bucket, key_hash, hits, window_start)
                 VALUES (:bucket, :hash, 1, NOW())
                 ON DUPLICATE KEY UPDATE hits = 1, window_start = NOW()',
                ['bucket' => $bucket, 'hash' => $hash]
            );

            return true;
        }

        if ((int) $row['hits'] >= $maxAttempts) {
            return false;
        }

        Database::run(
            'UPDATE rate_limits SET hits = hits + 1 WHERE bucket = :bucket AND key_hash = :hash',
            ['bucket' => $bucket, 'hash' => $hash]
        );

        return true;
    }

    /**
     * Sisa waktu (detik) sampai jendela pembatas direset. 0 bila tidak terblokir.
     */
    public static function availableIn(string $bucket, string $key, int $maxAttempts, int $windowSeconds): int
    {
        $row = Database::first(
            'SELECT hits, window_start FROM rate_limits WHERE bucket = :bucket AND key_hash = :hash',
            ['bucket' => $bucket, 'hash' => hash('sha256', $key)]
        );

        if ($row === null || (int) $row['hits'] < $maxAttempts) {
            return 0;
        }

        $elapsed = time() - (int) strtotime((string) $row['window_start']);

        return max(0, $windowSeconds - $elapsed);
    }

    public static function clientIp(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        return is_string($ip) && $ip !== '' ? $ip : '0.0.0.0';
    }

    private static function prune(): void
    {
        // Bersihkan baris lama sesekali saja agar tidak membebani setiap request.
        if (random_int(1, 200) !== 1) {
            return;
        }

        Database::run('DELETE FROM rate_limits WHERE window_start < (NOW() - INTERVAL 2 DAY)');
    }
}
