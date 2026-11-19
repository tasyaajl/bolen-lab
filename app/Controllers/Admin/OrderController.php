<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageUploader;
use App\Models\Order;
use DomainException;

final class OrderController extends Controller
{
    public function index(): string
    {
        Auth::requireLogin();

        $filters = [
            'q'              => $this->input('q'),
            'status'         => $this->input('status'),
            'payment_status' => $this->input('payment_status'),
            'date_from'      => $this->input('date_from'),
            'date_to'        => $this->input('date_to'),
        ];

        if ($filters['status'] !== null && !array_key_exists($filters['status'], Order::STATUSES)) {
            $filters['status'] = null;
        }

        if ($filters['payment_status'] !== null && !array_key_exists($filters['payment_status'], Order::PAYMENT_STATUSES)) {
            $filters['payment_status'] = null;
        }

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $result = Order::paginate($filters, $page);

        return $this->view('admin/orders/index', [
            'title'   => 'Pesanan — Admin BaleBolen',
            'filters' => $filters,
            'result'  => $result,
        ], 'layouts/admin');
    }

    public function show(string $id): string
    {
        Auth::requireLogin();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        return $this->view('admin/orders/show', [
            'title'      => 'Pesanan ' . $order['order_code'] . ' — Admin BaleBolen',
            'order'      => $order,
            'items'      => Order::items((int) $order['id']),
            'allowedTransitions' => Order::allowedTransitions((string) $order['order_status']),
            'paymentDueHours'    => (int) (setting('payment_due_hours', '24') ?? '24'),
        ], 'layouts/admin');
    }

    public function updateStatus(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        $status = (string) ($this->input('order_status') ?? '');
        $reason = $this->input('cancel_reason');

        if (!array_key_exists($status, Order::STATUSES)) {
            $this->flash('error', 'Status pesanan tidak valid.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        try {
            Order::updateStatus((int) $order['id'], $status, $reason);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        $this->flash('success', 'Status pesanan diperbarui menjadi "' . Order::statusLabel($status) . '".');

        if ($status === 'cancelled') {
            $this->flash('info', 'Stok varian pada pesanan ini dikembalikan otomatis.');
        }

        return $this->redirect('/admin/pesanan/' . $order['id']);
    }

    public function updatePayment(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        $status = (string) ($this->input('payment_status') ?? '');

        if (!array_key_exists($status, Order::PAYMENT_STATUSES)) {
            $this->flash('error', 'Status pembayaran tidak valid.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        Order::updatePaymentStatus((int) $order['id'], $status);
        $this->flash('success', 'Status pembayaran diperbarui menjadi "' . Order::paymentStatusLabel($status) . '".');

        return $this->redirect('/admin/pesanan/' . $order['id']);
    }

    /**
     * Tampilkan berkas bukti pembayaran dari storage privat.
     */
    public function proof(string $id): string
    {
        Auth::requireLogin();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        $path = ImageUploader::path(
            (string) App::configValue('uploads.proof_path'),
            $order['payment_proof'] ?? null
        );

        if ($path === null) {
            $this->flash('error', 'Belum ada bukti pembayaran untuk pesanan ini.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($path) ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="bukti-' . $order['order_code'] . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
        readfile($path);
        exit;
    }

    public function deleteProof(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        if ($order['payment_status'] === 'paid') {
            $this->flash('error', 'Bukti pembayaran tidak dapat dihapus setelah pembayaran diverifikasi.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        ImageUploader::delete(
            (string) App::configValue('uploads.proof_path'),
            $order['payment_proof'] ?? null
        );
        Order::clearPaymentProof((int) $order['id']);

        $this->flash('success', 'Bukti pembayaran dihapus. Status kembali menjadi Belum Dibayar.');

        return $this->redirect('/admin/pesanan/' . $order['id']);
    }

    public function updateShipping(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $order = Order::find((int) $id);

        if ($order === null) {
            $this->flash('error', 'Pesanan tidak ditemukan.');
            return $this->redirect('/admin/pesanan');
        }

        $cost = $this->input('shipping_cost');

        if ($cost === null) {
            Order::updateShippingCost((int) $order['id'], null);
            $this->flash('success', 'Ongkos kirim dikosongkan. Total kembali ke subtotal produk.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        if (!is_numeric($cost) || (float) $cost < 0) {
            $this->flash('error', 'Ongkos kirim harus berupa angka 0 atau lebih.');
            return $this->redirect('/admin/pesanan/' . $order['id']);
        }

        Order::updateShippingCost((int) $order['id'], (float) $cost);
        $this->flash('success', 'Ongkos kirim diperbarui.');

        return $this->redirect('/admin/pesanan/' . $order['id']);
    }
}
