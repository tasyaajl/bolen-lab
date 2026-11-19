<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function ensureToken(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::KEY];
    }

    public static function token(): string
    {
        return self::ensureToken();
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(?string $token): bool
    {
        $sessionToken = $_SESSION[self::KEY] ?? null;

        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }

    /**
     * Hentikan request POST yang tidak memiliki token valid.
     */
    public static function validateRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        if (self::verify($_POST['_token'] ?? null)) {
            return;
        }

        http_response_code(419);
        echo View::render('errors/419', ['title' => 'Sesi Formulir Kedaluwarsa'], 'layouts/main');
        exit;
    }
}
