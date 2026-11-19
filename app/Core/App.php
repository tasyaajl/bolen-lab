<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

final class App
{
    /** @var array<string, mixed> */
    private static array $config = [];

    private static Router $router;

    /**
     * @param array<string, mixed> $config
     */
    public static function boot(array $config): void
    {
        self::$config = $config;
        self::$router = new Router();

        Database::configure($config['db']);
        Session::start();
        Csrf::ensureToken();

        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/admin.php';
    }

    public static function router(): Router
    {
        return self::$router;
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(): array
    {
        return self::$config;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function configValue(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    public static function run(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $basePath = (string) self::configValue('app.base_path', '');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        }

        if (self::shouldForceHttps()) {
            $target = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . $uri;
            header('Location: ' . $target, true, 301);
            return;
        }

        self::sendSecurityHeaders();

        if ($method !== 'GET' && $method !== 'POST') {
            http_response_code(405);
            echo View::render('errors/404', ['title' => 'Metode Tidak Diizinkan'], 'layouts/main');
            return;
        }

        try {
            echo self::$router->dispatch($method, $path);
        } catch (Throwable $e) {
            self::handleException($e);
        }
    }

    private static function shouldForceHttps(): bool
    {
        if (!(bool) self::configValue('app.force_https', false)) {
            return false;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        if ($https) {
            return false;
        }

        // Jangan paksa pada lingkungan lokal agar dev server tetap bisa dipakai.
        $host = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);

        return !in_array($host, ['localhost', '127.0.0.1', '::1'], true);
    }

    private static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header("Content-Security-Policy-Report-Only: default-src 'self'; img-src 'self' data:; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; script-src 'self' 'unsafe-inline'; "
            . "frame-src 'self' https://www.google.com https://maps.google.com; "
            . "object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
    }

    private static function handleException(Throwable $e): void
    {
        http_response_code(500);

        $logDir = BASE_PATH . '/storage/logs';
        if (is_dir($logDir) || @mkdir($logDir, 0775, true)) {
            $line = sprintf(
                "[%s] %s: %s in %s:%d\n%s\n\n",
                date('Y-m-d H:i:s'),
                $e::class,
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
                $e->getTraceAsString()
            );
            @file_put_contents($logDir . '/app.log', $line, FILE_APPEND);
        }

        $debug = (bool) self::configValue('app.debug', false);

        try {
            echo View::render('errors/500', [
                'title'   => 'Terjadi Kesalahan',
                'message' => $debug ? $e->getMessage() : null,
                'trace'   => $debug ? $e->getTraceAsString() : null,
            ], 'layouts/main');
        } catch (Throwable) {
            echo 'Terjadi kesalahan pada server.';
        }
    }
}
