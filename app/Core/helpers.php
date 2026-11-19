<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Csrf;
use App\Core\Session;
use App\Models\Setting;

if (!function_exists('e')) {
    /**
     * Escape HTML untuk mencegah XSS.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = (string) App::configValue('app.base_path', '');
        $path = '/' . ltrim($path, '/');

        return $base . ($path === '/' ? '/' : rtrim($path, '/'));
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('rupiah')) {
    /**
     * Format angka menjadi Rupiah tanpa desimal, contoh: Rp5.000
     */
    function rupiah(int|float|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '-';
        }

        return 'Rp' . number_format((float) $amount, 0, ',', '.');
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        $old = Session::get('_old', []);

        return e($old[$key] ?? $default);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

if (!function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('flash')) {
    /**
     * Ambil pesan flash lalu hapus dari session.
     *
     * @return list<array{type: string, message: string}>
     */
    function flash(): array
    {
        $messages = Session::get('_flash', []);
        Session::forget('_flash');

        return $messages;
    }
}

if (!function_exists('now')) {
    function now(bool $withTime = true): string
    {
        return date($withTime ? 'Y-m-d H:i:s' : 'Y-m-d');
    }
}

if (!function_exists('tanggal_id')) {
    /**
     * Format tanggal ramah: 19 November 2026
     */
    function tanggal_id(?string $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return '-';
        }

        $text = (int) date('j', $timestamp) . ' ' . $bulan[(int) date('n', $timestamp)] . ' ' . date('Y', $timestamp);

        return $withTime ? $text . ', ' . date('H:i', $timestamp) : $text;
    }
}

if (!function_exists('normalize_phone')) {
    /**
     * Normalisasi nomor Indonesia ke format 62xxxxxxxxxx.
     */
    function normalize_phone(?string $number): string
    {
        $digits = preg_replace('/\D+/', '', (string) $number) ?? '';

        if (str_starts_with($digits, '0')) {
            return '62' . substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62' . $digits;
        }

        return $digits;
    }
}

if (!function_exists('whatsapp_link')) {
    /**
     * Tautan wa.me dari nomor yang sudah dinormalisasi.
     */
    function whatsapp_link(?string $number, string $message = ''): string
    {
        $digits = normalize_phone($number);

        $link = 'https://wa.me/' . $digits;

        if ($message !== '') {
            $link .= '?text=' . rawurlencode($message);
        }

        return $link;
    }
}

if (!function_exists('is_admin_request')) {
    function is_admin_request(): bool
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return str_starts_with($path, '/admin');
    }
}
