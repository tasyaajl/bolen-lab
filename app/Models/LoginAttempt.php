<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Riwayat percobaan login admin.
 *
 * Disimpan di database (bukan session) agar brute force lintas
 * session/cookie tetap terhambat.
 */
final class LoginAttempt
{
    private const WINDOW_SECONDS = 600;
    private const MAX_PER_EMAIL = 5;
    private const MAX_PER_IP = 20;

    public static function record(string $email, string $ip, bool $successful): void
    {
        Database::insert('login_attempts', [
            'email'        => mb_substr(strtolower($email), 0, 150),
            'ip'           => mb_substr($ip, 0, 45),
            'successful'   => $successful ? 1 : 0,
            'attempted_at' => now(),
        ]);

        self::prune();
    }

    public static function tooMany(string $email, string $ip): bool
    {
        return self::retryAfter($email, $ip) > 0;
    }

    /**
     * Detik tersisa sampai percobaan berikutnya diizinkan. 0 = tidak terkunci.
     */
    public static function retryAfter(string $email, string $ip): int
    {
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);

        $emailCount = (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts
             WHERE successful = 0 AND email = :email AND attempted_at >= :since',
            ['email' => strtolower($email), 'since' => $since]
        );

        $ipCount = (int) Database::scalar(
            'SELECT COUNT(*) FROM login_attempts
             WHERE successful = 0 AND ip = :ip AND attempted_at >= :since',
            ['ip' => $ip, 'since' => $since]
        );

        $blocked = $emailCount >= self::MAX_PER_EMAIL || $ipCount >= self::MAX_PER_IP;

        if (!$blocked) {
            return 0;
        }

        $oldest = Database::scalar(
            'SELECT MIN(attempted_at) FROM login_attempts
             WHERE successful = 0 AND (email = :email OR ip = :ip) AND attempted_at >= :since',
            ['email' => strtolower($email), 'ip' => $ip, 'since' => $since]
        );

        $elapsed = $oldest === null ? 0 : time() - (int) strtotime((string) $oldest);

        return max(0, self::WINDOW_SECONDS - $elapsed);
    }

    /**
     * Login berhasil: hapus riwayat gagal untuk email tersebut saja,
     * sehingga akun lain dari IP yang sama tidak terkunci.
     */
    public static function clearFailures(string $email): void
    {
        Database::run(
            'DELETE FROM login_attempts WHERE email = :email AND successful = 0',
            ['email' => strtolower($email)]
        );
    }

    private static function prune(): void
    {
        if (random_int(1, 100) !== 1) {
            return;
        }

        Database::run('DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)');
    }
}
