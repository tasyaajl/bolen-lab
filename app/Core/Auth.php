<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Admin;
use App\Models\LoginAttempt;

final class Auth
{
    private const SESSION_KEY = 'admin_id';
    private const SEEN_KEY = 'admin_last_seen';

    public static function check(): bool
    {
        $id = (int) Session::get(self::SESSION_KEY, 0);

        if ($id <= 0) {
            return false;
        }

        $idleMinutes = (int) App::configValue('security.admin_idle_minutes', 30);

        if ($idleMinutes > 0) {
            $lastSeen = (int) Session::get(self::SEEN_KEY, 0);

            if ($lastSeen > 0 && (time() - $lastSeen) > $idleMinutes * 60) {
                Session::forget(self::SESSION_KEY);
                Session::forget(self::SEEN_KEY);
                Session::regenerate();

                $messages = Session::get('_flash', []);
                $messages[] = ['type' => 'info', 'message' => 'Sesi Anda berakhir karena tidak ada aktivitas. Silakan login kembali.'];
                Session::put('_flash', $messages);

                return false;
            }
        }

        Session::put(self::SEEN_KEY, time());

        return true;
    }

    public static function user(): ?array
    {
        $id = (int) Session::get(self::SESSION_KEY, 0);

        return $id > 0 ? Admin::find($id) : null;
    }

    public static function attempt(string $email, string $password): bool
    {
        if (self::isLocked($email)) {
            return false;
        }

        $ip = RateLimiter::clientIp();
        $admin = Admin::findByEmail($email);

        if ($admin === null || !password_verify($password, $admin['password_hash'])) {
            LoginAttempt::record($email, $ip, false);

            return false;
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            Admin::updatePassword((int) $admin['id'], $password);
        }

        LoginAttempt::record($email, $ip, true);
        LoginAttempt::clearFailures($email);

        Session::regenerate();
        Session::put(self::SESSION_KEY, (int) $admin['id']);
        Session::put(self::SEEN_KEY, time());

        return true;
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget(self::SEEN_KEY);
        Session::regenerate();
    }

    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        Session::put('_intended', $_SERVER['REQUEST_URI'] ?? '/admin');
        header('Location: ' . url('/admin/login'), true, 302);
        exit;
    }

    /**
     * Status terkunci untuk email tertentu (dan IP request saat ini).
     */
    public static function isLocked(string $email = ''): bool
    {
        if ($email === '') {
            return false;
        }

        return LoginAttempt::tooMany($email, RateLimiter::clientIp());
    }

    public static function remainingLockSeconds(string $email = ''): int
    {
        if ($email === '') {
            return 0;
        }

        return LoginAttempt::retryAfter($email, RateLimiter::clientIp());
    }
}
