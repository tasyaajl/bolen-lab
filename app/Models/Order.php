<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Session;
use DomainException;
use Throwable;

final class Order
{
    public const STATUSES = [
        'new'               => 'Pesanan Baru',
        'awaiting_payment'  => 'Menunggu Pembayaran',
        'confirmed'         => 'Dikonfirmasi',
        'processing'        => 'Sedang Diproses',
        'ready_for_pickup'  => 'Siap Diambil',
        'shipping'          => 'Dalam Pengiriman',
        'completed'         => 'Selesai',
        'cancelled'         => 'Dibatalkan',
    ];

    public const PAYMENT_STATUSES = [
        'unpaid'                => 'Belum Dibayar',
        'awaiting_verification' => 'Menunggu Verifikasi',
        'paid'                  => 'Dibayar',
        'cancelled'             => 'Dibatalkan',
    ];

    public const PAYMENT_METHODS = [
        'transfer'    => 'Transfer Bank',
        'qris'        => 'QRIS',
        'cod'         => 'COD (Bayar saat diterima)',
        'cash'        => 'Tunai saat pickup',
        'gopay'       => 'GoPay / E-Wallet',
    ];

    public const DELIVERY_METHODS = [
        'own'   => 'Diantar langsung oleh BaleBolen',
        'gojek' => 'Gojek',
        'other' => 'Kurir lainnya',
    ];

    /**
     * Transisi status pesanan yang diizinkan.
     * `completed` dan `cancelled` bersifat final.
     *
     * @var array<string, list<string>>
     */
    public const TRANSITIONS = [
        'new'              => ['awaiting_payment', 'confirmed', 'processing', 'cancelled'],
        'awaiting_payment' => ['confirmed', 'processing', 'cancelled'],
        'confirmed'        => ['processing', 'ready_for_pickup', 'shipping', 'cancelled'],
        'processing'       => ['ready_for_pickup', 'shipping', 'cancelled'],
        'ready_for_pickup' => ['completed', 'cancelled'],
        'shipping'         => ['completed', 'cancelled'],
        'completed'        => [],
        'cancelled'        => [],
    ];

    public const MANUAL_PAYMENT_METHODS = ['transfer', 'qris', 'gopay'];

    /**
     * @return list<string>
     */
    public static function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public static function canTransition(string $from, string $to): bool
    {
        return $from === $to || in_array($to, self::allowedTransitions($from), true);
    }

    public static function statusLabel(string $status): string
    {
        return self::STATUSES[$status] ?? $status;
    }

    public static function paymentStatusLabel(string $status): string
    {
        return self::PAYMENT_STATUSES[$status] ?? $status;
    }

    public static function paymentBadgeClass(string $status): string
    {
        return match ($status) {
            'paid'                  => 'badge--success',
            'awaiting_verification' => 'badge--info',
            'cancelled'             => 'badge--danger',
            default                 => 'badge--warning',
        };
    }

    public static function paymentMethodLabel(string $method): string
    {
        return self::PAYMENT_METHODS[$method] ?? $method;
    }

    public static function deliveryMethodLabel(?string $method): string
    {
        if ($method === null) {
            return '-';
        }

        return self::DELIVERY_METHODS[$method] ?? $method;
    }

    /**
     * Buat pesanan beserta item, komposisi, dan pengurangan stok dalam satu transaksi.
     *
     * @param array<string, mixed> $customer
     * @param list<array<string, mixed>> $lines hasil Cart::detailed()
     * @return array{id: int, code: string}
     */
    public static function create(array $customer, array $lines): array
    {
        if ($lines === []) {
            throw new DomainException('Keranjang masih kosong.');
        }

        Database::beginTransaction();

        try {
            $requirements = self::collectRequirements($lines);
            $locked = self::lockVariants(array_keys($requirements));

            foreach ($requirements as $variantId => $quantity) {
                $variant = $locked[$variantId] ?? null;

                if ($variant === null || (int) $variant['is_active'] !== 1) {
                    throw new DomainException('Salah satu varian rasa tidak tersedia.');
                }

                if ((int) $variant['stock'] < $quantity) {
                    throw new DomainException(sprintf(
                        'Stok %s tidak mencukupi. Tersedia %d, dibutuhkan %d.',
                        $variant['name'],
                        (int) $variant['stock'],
                        $quantity
                    ));
                }
            }

            $subtotal = 0;
            foreach ($lines as $line) {
                if ($line['unit_price'] === null) {
                    throw new DomainException('Harga produk ' . $line['product']['name'] . ' belum diatur admin.');
                }

                $subtotal += (int) $line['line_total'];
            }

            $code = self::generateCode();
            $paymentMethod = (string) $customer['payment_method'];

            $orderId = Database::insert('orders', [
                'order_code'      => $code,
                'access_token'    => bin2hex(random_bytes(32)),
                'customer_name'   => $customer['customer_name'],
                'whatsapp'        => $customer['whatsapp'],
                'fulfillment'     => $customer['fulfillment'],
                'delivery_method' => $customer['delivery_method'] ?? null,
                'delivery_address' => $customer['delivery_address'] ?? null,
                'delivery_date'   => $customer['delivery_date'],
                'notes'           => $customer['notes'] ?? null,
                'gift_recipient'  => $customer['gift_recipient'] ?? null,
                'gift_message'    => $customer['gift_message'] ?? null,
                'subtotal'        => $subtotal,
                'shipping_cost'   => null,
                'total'           => $subtotal,
                'payment_method'  => $paymentMethod,
                'payment_status'  => 'unpaid',
                'payment_due_at'  => self::paymentDueAt($paymentMethod),
                'order_status'    => 'new',
                'stock_restored'  => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            foreach ($lines as $line) {
                $product = $line['product'];
                $compositionText = [];
                $composition = $line['composition'];

                // Produk satuan tetap dicatat per varian agar pengembalian stok
                // saat pembatalan bisa dihitung ulang dengan benar.
                if (($product['type'] ?? '') === 'single') {
                    $composition = [[
                        'variant_id' => (int) $product['variant_id'],
                        'name'       => (string) ($product['variant_name'] ?? $product['name']),
                        'quantity'   => (int) $line['quantity'],
                    ]];
                }

                foreach ($composition as $row) {
                    $compositionText[] = $row['quantity'] . '× ' . $row['name'];
                }

                $itemId = Database::insert('order_items', [
                    'order_id'        => $orderId,
                    'product_id'      => (int) $product['id'],
                    'product_name'    => (string) $product['name'],
                    'product_type'    => (string) $product['type'],
                    'package_size'    => $product['package_size'] === null ? null : (int) $product['package_size'],
                    'unit_price'      => (float) $line['unit_price'],
                    'quantity'        => (int) $line['quantity'],
                    'line_total'      => (int) $line['line_total'],
                    'composition_text' => $compositionText === [] ? null : implode(', ', $compositionText),
                ]);

                foreach ($composition as $row) {
                    Database::insert('order_item_variants', [
                        'order_item_id' => $itemId,
                        'variant_id'    => (int) $row['variant_id'],
                        'variant_name'  => (string) $row['name'],
                        'quantity'      => (int) $row['quantity'],
                    ]);
                }
            }

            foreach ($requirements as $variantId => $quantity) {
                Variant::adjustStock($variantId, -$quantity, 'order', 'Pesanan ' . $code, $orderId, null);
            }

            Database::commit();

            return ['id' => $orderId, 'code' => $code];
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Gabungkan kebutuhan stok per varian dari semua baris pesanan.
     *
     * @param list<array<string, mixed>> $lines
     * @return array<int, int>
     */
    private static function collectRequirements(array $lines): array
    {
        $requirements = [];

        foreach ($lines as $line) {
            $product = $line['product'];

            if (($product['type'] ?? '') === 'single') {
                $variantId = (int) ($product['variant_id'] ?? 0);

                if ($variantId <= 0) {
                    throw new DomainException('Produk ' . $product['name'] . ' belum memiliki varian rasa.');
                }

                $requirements[$variantId] = ($requirements[$variantId] ?? 0) + (int) $line['quantity'];
                continue;
            }

            $total = 0;
            foreach ($line['composition'] as $row) {
                $variantId = (int) $row['variant_id'];
                $quantity = (int) $row['quantity'];
                $total += $quantity;
                $requirements[$variantId] = ($requirements[$variantId] ?? 0) + $quantity;
            }

            if ($total !== (int) $product['package_size']) {
                throw new DomainException(sprintf(
                    'Komposisi %s harus tepat %d buah.',
                    $product['name'],
                    (int) $product['package_size']
                ));
            }
        }

        return $requirements;
    }

    /**
     * @param list<int> $variantIds
     * @return array<int, array<string, mixed>>
     */
    private static function lockVariants(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        $ids = array_map('intval', $variantIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = Database::all(
            'SELECT id, name, stock, is_active FROM product_variants WHERE id IN (' . $placeholders . ') FOR UPDATE',
            $ids
        );

        $locked = [];
        foreach ($rows as $row) {
            $locked[(int) $row['id']] = $row;
        }

        return $locked;
    }

    /**
     * Tenggat pembayaran untuk metode bayar manual. Null bila tidak berlaku
     * atau bila admin mengosongkan pengaturan (0 jam = tanpa tenggat).
     */
    private static function paymentDueAt(string $paymentMethod): ?string
    {
        if (!in_array($paymentMethod, self::MANUAL_PAYMENT_METHODS, true)) {
            return null;
        }

        $hours = (int) (setting('payment_due_hours', '24') ?? '24');

        if ($hours <= 0) {
            return null;
        }

        return date('Y-m-d H:i:s', strtotime('+' . $hours . ' hours'));
    }

    private static function generateCode(): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = 'BB' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

            if (Database::scalar('SELECT id FROM orders WHERE order_code = :code', ['code' => $code]) === null) {
                return $code;
            }
        }

        return 'BB' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        return Database::first('SELECT * FROM orders WHERE order_code = :code', ['code' => $code]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM orders WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function items(int $orderId): array
    {
        $items = Database::all(
            'SELECT * FROM order_items WHERE order_id = :id ORDER BY id',
            ['id' => $orderId]
        );

        foreach ($items as &$item) {
            $item['variants'] = Database::all(
                'SELECT * FROM order_item_variants WHERE order_item_id = :id ORDER BY id',
                ['id' => $item['id']]
            );
        }

        return $items;
    }

    /**
     * @param array<string, string|null> $filters
     * @return array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int}
     */
    public static function paginate(array $filters, int $page = 1, int $perPage = 15): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'order_status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['payment_status'])) {
            $where[] = 'payment_status = :payment_status';
            $params['payment_status'] = $filters['payment_status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(order_code LIKE :q_code OR customer_name LIKE :q_name OR whatsapp LIKE :q_wa)';
            $params['q_code'] = '%' . $filters['q'] . '%';
            $params['q_name'] = '%' . $filters['q'] . '%';
            $params['q_wa'] = '%' . $filters['q'] . '%';
        }

        $whereSql = implode(' AND ', $where);

        $total = (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE ' . $whereSql, $params);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;

        $rows = Database::all(
            'SELECT * FROM orders WHERE ' . $whereSql . ' ORDER BY created_at DESC, id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset,
            $params
        );

        return [
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $pages,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function recent(int $limit = 5): array
    {
        return Database::all('SELECT * FROM orders ORDER BY created_at DESC, id DESC LIMIT ' . max(1, $limit));
    }

    /**
     * @return array<string, int|float>
     */
    public static function statistics(): array
    {
        $counts = [];
        foreach (array_keys(self::STATUSES) as $status) {
            $counts[$status] = (int) Database::scalar(
                'SELECT COUNT(*) FROM orders WHERE order_status = :status',
                ['status' => $status]
            );
        }

        $counts['total_orders'] = (int) Database::scalar('SELECT COUNT(*) FROM orders');
        $counts['paid_revenue'] = (float) Database::scalar(
            "SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'paid' AND order_status <> 'cancelled'"
        );
        $counts['today_orders'] = (int) Database::scalar('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()');

        return $counts;
    }

    public static function updateStatus(int $orderId, string $status, ?string $reason = null): void
    {
        if (!array_key_exists($status, self::STATUSES)) {
            throw new DomainException('Status pesanan tidak valid.');
        }

        Database::beginTransaction();

        try {
            $order = Database::first('SELECT * FROM orders WHERE id = :id FOR UPDATE', ['id' => $orderId]);

            if ($order === null) {
                throw new DomainException('Pesanan tidak ditemukan.');
            }

            $current = (string) $order['order_status'];

            if (!self::canTransition($current, $status)) {
                throw new DomainException(sprintf(
                    'Status tidak dapat diubah dari "%s" ke "%s".',
                    self::statusLabel($current),
                    self::statusLabel($status)
                ));
            }

            $changes = [
                'order_status' => $status,
                'updated_at'   => now(),
            ];

            if ($status === 'cancelled' && $current !== 'cancelled') {
                if ((int) $order['stock_restored'] === 0) {
                    self::restoreStock($orderId);
                    $changes['stock_restored'] = 1;
                }

                $changes['cancel_reason'] = $reason !== null && trim($reason) !== ''
                    ? mb_substr(trim($reason), 0, 255)
                    : null;

                // Status pembayaran ikut dibatalkan, kecuali sudah dibayar
                // (refund ditangani manual oleh pemilik usaha).
                if ((string) $order['payment_status'] !== 'paid') {
                    $changes['payment_status'] = 'cancelled';
                }
            }

            Database::update('orders', $changes, ['id' => $orderId]);

            Database::commit();
        } catch (Throwable $e) {
            Database::rollback();
            throw $e;
        }
    }

    /**
     * Batalkan pesanan manual yang melewati tenggat pembayaran dan
     * kembalikan stoknya. Aman dijalankan berulang.
     */
    public static function releaseExpired(): int
    {
        $rows = Database::all(
            "SELECT id FROM orders
             WHERE payment_status = 'unpaid'
               AND order_status NOT IN ('cancelled', 'completed')
               AND payment_due_at IS NOT NULL
               AND payment_due_at < NOW()"
        );

        $released = 0;

        foreach ($rows as $row) {
            try {
                self::updateStatus((int) $row['id'], 'cancelled', 'Batas waktu pembayaran terlampaui');
                $released++;
            } catch (Throwable) {
                // Pesanan yang berubah bersamaan dilewati; jalankan ulang command.
                continue;
            }
        }

        return $released;
    }

    /**
     * Simpan bukti pembayaran yang sudah diunggah ke storage privat.
     */
    public static function recordPaymentProof(int $orderId, string $filename): void
    {
        $order = self::find($orderId);

        if ($order === null) {
            throw new DomainException('Pesanan tidak ditemukan.');
        }

        if (in_array((string) $order['order_status'], ['cancelled', 'completed'], true)) {
            throw new DomainException('Pesanan ini sudah final sehingga bukti tidak dapat diunggah.');
        }

        if ((string) $order['payment_status'] === 'paid') {
            throw new DomainException('Pembayaran pesanan ini sudah diverifikasi admin.');
        }

        $changes = [
            'payment_proof'    => $filename,
            'payment_proof_at' => now(),
            'updated_at'       => now(),
        ];

        if ((string) $order['payment_status'] === 'unpaid') {
            $changes['payment_status'] = 'awaiting_verification';
        }

        Database::update('orders', $changes, ['id' => $orderId]);
    }

    public static function clearPaymentProof(int $orderId): void
    {
        Database::update('orders', [
            'payment_proof'    => null,
            'payment_proof_at' => null,
            'payment_status'   => 'unpaid',
            'updated_at'       => now(),
        ], ['id' => $orderId]);
    }

    /**
     * Akses halaman pesanan: session yang sudah diverifikasi, admin, atau token panjang.
     */
    public static function hasSessionAccess(array $order): bool
    {
        $access = Session::get('order_access', []);

        return is_array($access) && !empty($access[(int) $order['id']]);
    }

    public static function grantSessionAccess(array $order): void
    {
        $access = Session::get('order_access', []);
        $access = is_array($access) ? $access : [];
        $access[(int) $order['id']] = time();

        // Simpan maksimal 20 pesanan terakhir agar session tidak membengkak.
        if (count($access) > 20) {
            $access = array_slice($access, -20, 20, true);
        }

        Session::put('order_access', $access);
    }

    public static function updatePaymentStatus(int $orderId, string $status): void
    {
        if (!array_key_exists($status, self::PAYMENT_STATUSES)) {
            throw new DomainException('Status pembayaran tidak valid.');
        }

        Database::update('orders', [
            'payment_status' => $status,
            'updated_at'     => now(),
        ], ['id' => $orderId]);
    }

    public static function updateShippingCost(int $orderId, ?float $cost): void
    {
        $order = self::find($orderId);

        if ($order === null) {
            return;
        }

        $subtotal = (float) $order['subtotal'];
        $total = $cost === null ? $subtotal : $subtotal + $cost;

        Database::update('orders', [
            'shipping_cost' => $cost,
            'total'         => $total,
            'updated_at'    => now(),
        ], ['id' => $orderId]);
    }

    private static function restoreStock(int $orderId): void
    {
        $rows = Database::all(
            'SELECT oiv.variant_id, SUM(oiv.quantity) AS quantity
             FROM order_item_variants oiv
             JOIN order_items oi ON oi.id = oiv.order_item_id
             WHERE oi.order_id = :order_id
             GROUP BY oiv.variant_id',
            ['order_id' => $orderId]
        );

        foreach ($rows as $row) {
            if ($row['variant_id'] === null) {
                continue;
            }

            Variant::adjustStock(
                (int) $row['variant_id'],
                (int) $row['quantity'],
                'cancel',
                'Pembatalan pesanan #' . $orderId,
                $orderId,
                null
            );
        }
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'new', 'awaiting_payment' => 'badge--warning',
            'confirmed', 'processing' => 'badge--info',
            'ready_for_pickup'        => 'badge--accent',
            'shipping'                => 'badge--accent',
            'completed'               => 'badge--success',
            'cancelled'               => 'badge--danger',
            default                   => 'badge--muted',
        };
    }
}
