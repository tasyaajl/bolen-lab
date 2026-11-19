<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], string $layout = 'layouts/main'): string
    {
        return View::render($view, $data, $layout);
    }

    protected function redirect(string $path, int $status = 302): never
    {
        header('Location: ' . url($path), true, $status);
        exit;
    }

    protected function back(string $fallback = '/'): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $isLocal = $referer !== '' && parse_url($referer, PHP_URL_HOST) === parse_url('http://' . $host, PHP_URL_HOST);

        header('Location: ' . ($isLocal ? $referer : url($fallback)), true, 302);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $messages = Session::get('_flash', []);
        $messages[] = ['type' => $type, 'message' => $message];
        Session::put('_flash', $messages);
    }

    protected function input(string $key, ?string $default = null): ?string
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        if (!is_string($value)) {
            return $default;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * @return mixed
     */
    protected function rawInput(string $key): mixed
    {
        return $_POST[$key] ?? null;
    }

    /**
     * Simpan input lama + error ke session (flash) untuk ditampilkan kembali.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    protected function withErrors(array $errors, array $old = []): void
    {
        Session::put('_errors', $errors);
        Session::put('_old', $old);
    }

    protected function checkCsrf(): void
    {
        if (!Csrf::verify($_POST['_token'] ?? null)) {
            http_response_code(419);
            echo View::render('errors/419', ['title' => 'Sesi Formulir Kedaluwarsa'], 'layouts/main');
            exit;
        }
    }
}
