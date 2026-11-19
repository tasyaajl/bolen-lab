<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageUploader;
use App\Core\RateLimiter;
use App\Core\Session;
use App\Models\Order;
use DomainException;

final class OrderController extends Controller
{
    private const ACCESS_PER_WINDOW = 5;
    private const ACCESS_WINDOW = 600;

    public function show(string $code): string
    {
        $order = $this->findOrder($code);

        if ($order === null) {
            return $this->notFound();
        }

        // Tautan panjang dengan token acak boleh membuka halaman tanpa verifikasi.
        $token = $_GET['t'] ?? null;

        if (is_string($token) && $token !== '' && !empty($order['access_token'])
            && hash_equals((string) $order['access_token'], $token)) {
            Order::grantSessionAccess($order);
            $this->redirect('/pesanan/' . $order['order_code']);
        }

        if (!$this->canView($order)) {
            return $this->view('order-gate', [
                'title'      => 'Verifikasi Pesanan — BaleBolen',
                'code'       => $order['order_code'],
                'errors'     => Session::pull('_form_errors', []),
                'old'        => Session::pull('_form_old', []),
            ]);
        }

        return $this->view('order', [
            'title'         => 'Pesanan ' . $order['order_code'] . ' — BaleBolen',
            'order'         => $order,
            'items'         => Order::items((int) $order['id']),
            'canUploadProof' => in_array((string) $order['payment_method'], Order::MANUAL_PAYMENT_METHODS, true),
            'maxUploadMb'   => round((int) App::configValue('uploads.max_bytes', 3 * 1024 * 1024) / 1048576, 1),
        ]);
    }

    public function verifyAccess(string $code): string
    {
        $this->checkCsrf();

        $order = $this->findOrder($code);

        if ($order === null) {
            return $this->notFound();
        }

        $key = RateLimiter::clientIp() . '|' . $order['order_code'];

        if (!RateLimiter::attempt('order_access', $key, self::ACCESS_PER_WINDOW, self::ACCESS_WINDOW)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn('order_access', $key, self::ACCESS_PER_WINDOW, self::ACCESS_WINDOW) / 60));
            $this->flash('error', 'Terlalu banyak percobaan. Coba lagi dalam ' . $minutes . ' menit.');
            return $this->redirect('/pesanan/' . $order['order_code']);
        }

        $input = preg_replace('/\D+/', '', (string) ($this->rawInput('last4') ?? '')) ?? '';
        $expected = substr(preg_replace('/\D+/', '', (string) $order['whatsapp']) ?? '', -4);

        if (strlen($input) !== 4 || $expected === '' || !hash_equals($expected, $input)) {
            Session::put('_form_errors', ['last4' => 'Empat digit terakhir nomor WhatsApp tidak cocok.']);
            Session::put('_form_old', ['last4' => $input]);
            $this->flash('error', 'Verifikasi gagal. Periksa kembali nomor WhatsApp yang dipakai saat memesan.');
            return $this->redirect('/pesanan/' . $order['order_code']);
        }

        Order::grantSessionAccess($order);
        $this->flash('success', 'Pesanan berhasil dibuka.');

        return $this->redirect('/pesanan/' . $order['order_code']);
    }

    public function uploadProof(string $code): string
    {
        $this->checkCsrf();

        $order = $this->findOrder($code);

        if ($order === null) {
            return $this->notFound();
        }

        if (!$this->canView($order)) {
            $this->flash('error', 'Buka dan verifikasi pesanan terlebih dahulu sebelum mengunggah bukti.');
            return $this->redirect('/pesanan/' . $order['order_code']);
        }

        $key = RateLimiter::clientIp() . '|' . $order['order_code'];

        if (!RateLimiter::attempt('proof_upload', $key, 10, 3600)) {
            $this->flash('error', 'Terlalu banyak unggahan. Coba lagi nanti.');
            return $this->redirect('/pesanan/' . $order['order_code']);
        }

        if (!in_array((string) $order['payment_method'], Order::MANUAL_PAYMENT_METHODS, true)) {
            $this->flash('error', 'Pesanan ini tidak memerlukan bukti pembayaran.');
            return $this->redirect('/pesanan/' . $order['order_code']);
        }

        try {
            $filename = ImageUploader::store(
                $_FILES['proof'] ?? null,
                (string) App::configValue('uploads.proof_path'),
                (int) App::configValue('uploads.max_bytes'),
                (array) App::configValue('uploads.proof_mime'),
                'JPG, PNG, WEBP, atau PDF'
            );

            if ($filename === null) {
                throw new DomainException('Pilih berkas bukti pembayaran terlebih dahulu.');
            }

            $previous = $order['payment_proof'];
            Order::recordPaymentProof((int) $order['id'], $filename);
            ImageUploader::delete((string) App::configValue('uploads.proof_path'), $previous);

            $this->flash('success', 'Bukti pembayaran diterima. Admin akan memverifikasi pesanan Anda.');
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect('/pesanan/' . $order['order_code']);
    }

    /**
     * Tampilkan bukti pembayaran milik pesanan yang sudah diverifikasi.
     */
    public function proof(string $code): string
    {
        $order = $this->findOrder($code);

        if ($order === null || !$this->canView($order)) {
            return $this->notFound();
        }

        $path = ImageUploader::path(
            (string) App::configValue('uploads.proof_path'),
            $order['payment_proof'] ?? null
        );

        if ($path === null) {
            return $this->notFound();
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store');
        header('Content-Disposition: inline; filename="bukti-' . $order['order_code'] . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
        readfile($path);
        exit;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findOrder(string $code): ?array
    {
        $code = strtoupper(preg_replace('/[^A-Za-z0-9-]/', '', $code) ?? '');

        return $code === '' ? null : Order::findByCode($code);
    }

    private function canView(array $order): bool
    {
        return Auth::check() || Order::hasSessionAccess($order);
    }

    private function notFound(): string
    {
        http_response_code(404);

        return $this->view('errors/404', [
            'title'   => 'Pesanan Tidak Ditemukan',
            'message' => 'Kode pesanan tidak ditemukan. Periksa kembali tautan atau hubungi kami via WhatsApp.',
        ]);
    }
}
