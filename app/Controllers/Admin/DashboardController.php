<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;

final class DashboardController extends Controller
{
    public function index(): string
    {
        Auth::requireLogin();

        $stats = Order::statistics();

        return $this->view('admin/dashboard', [
            'title'   => 'Dashboard — Admin BaleBolen',
            'stats'   => $stats,
            'recent'  => Order::recent(6),
            'checklist' => $this->setupChecklist(),
            'stockTotal' => (int) Database::scalar('SELECT COALESCE(SUM(stock), 0) FROM product_variants'),
        ], 'layouts/admin');
    }

    /**
     * Daftar hal yang masih perlu dilengkapi pemilik usaha.
     *
     * @return list<array{label: string, ok: bool, hint: string, link: string, cta: string}>
     */
    private function setupChecklist(): array
    {
        $hampersWithPrice = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE type = "hampers" AND price IS NOT NULL');
        $hampersTotal = (int) Database::scalar('SELECT COUNT(*) FROM products WHERE type = "hampers"');
        $stockTotal = (int) Database::scalar('SELECT COALESCE(SUM(stock), 0) FROM product_variants');

        return [
            [
                'label' => 'Harga Paket Hampers',
                'ok'    => $hampersTotal > 0 && $hampersWithPrice === $hampersTotal,
                'hint'  => $hampersWithPrice . ' dari ' . $hampersTotal . ' paket sudah memiliki harga.',
                'link'  => '/admin/hampers',
                'cta'   => 'Atur harga',
            ],
            [
                'label' => 'Ketentuan Pre-order Hampers',
                'ok'    => Setting::get('hampers_preorder_days') !== null,
                'hint'  => Setting::get('hampers_preorder_days') !== null
                    ? 'Minimal ' . Setting::get('hampers_preorder_days') . ' hari sebelum pengambilan/pengiriman.'
                    : 'Belum diatur. Paket hampers belum bisa dipesan sebelum angka ini diisi.',
                'link'  => '/admin/pengaturan#preorder',
                'cta'   => 'Atur pre-order',
            ],
            [
                'label' => 'Stok Bolen',
                'ok'    => $stockTotal > 0,
                'hint'  => $stockTotal > 0 ? 'Total stok saat ini ' . $stockTotal . ' buah.' : 'Semua stok masih 0. Isi stok agar produk bisa dipesan.',
                'link'  => '/admin/stok',
                'cta'   => 'Isi stok',
            ],
            [
                'label' => 'Nomor WhatsApp & Alamat',
                'ok'    => Setting::get('whatsapp') !== null && Setting::get('address') !== null,
                'hint'  => 'Dipakai pada halaman kontak dan tombol pesan WhatsApp.',
                'link'  => '/admin/pengaturan',
                'cta'   => 'Lengkapi',
            ],
            [
                'label' => 'Informasi Pembayaran',
                'ok'    => Setting::get('bank_number') !== null || Setting::get('qris_note') !== null || Setting::get('qris_image') !== null,
                'hint'  => 'Rekening, QRIS, atau e-wallet yang akan ditampilkan ke pelanggan.',
                'link'  => '/admin/pengaturan#pembayaran',
                'cta'   => 'Lengkapi',
            ],
            [
                'label' => 'Jam Operasional & Media Sosial',
                'ok'    => Setting::get('opening_hours') !== null,
                'hint'  => 'Ditampilkan pada halaman kontak dan footer.',
                'link'  => '/admin/pengaturan',
                'cta'   => 'Lengkapi',
            ],
        ];
    }
}
