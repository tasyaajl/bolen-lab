<?php
/**
 * @var array<string, string|null> $filters
 * @var array{rows: list<array<string, mixed>>, total: int, page: int, per_page: int, pages: int} $result
 */

use App\Models\Order;

$buildUrl = static function (int $page) use ($filters): string {
    $params = array_filter($filters, static fn ($value): bool => $value !== null && $value !== '');
    if ($page > 1) {
        $params['page'] = $page;
    }

    return url('/admin/pesanan') . ($params !== [] ? '?' . http_build_query($params) : '');
};
$badge = static fn (string $class): string => str_replace('badge--', '', $class);
?>

<div class="a-card">
    <div class="a-card__head">
        <div>
            <h2>Daftar pesanan</h2>
            <p><?= e($result['total']) ?> pesanan ditemukan.</p>
        </div>
        <a href="<?= url('/admin/pesanan') ?>" class="a-btn a-btn--ghost a-btn--small">Reset filter</a>
    </div>

    <form method="get" action="<?= url('/admin/pesanan') ?>" class="a-filters" style="margin-bottom: 20px;">
        <div class="a-field a-field--search">
            <label for="q">Cari</label>
            <input type="text" id="q" name="q" value="<?= e($filters['q'] ?? '') ?>" placeholder="Kode pesanan, nama, WhatsApp">
        </div>
        <div class="a-field">
            <label for="status">Status pesanan</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <?php foreach (Order::STATUSES as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="a-field">
            <label for="payment_status">Pembayaran</label>
            <select id="payment_status" name="payment_status">
                <option value="">Semua</option>
                <?php foreach (Order::PAYMENT_STATUSES as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($filters['payment_status'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="a-field">
            <label for="date_from">Dari tanggal</label>
            <input type="date" id="date_from" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
        </div>
        <div class="a-field">
            <label for="date_to">Sampai</label>
            <input type="date" id="date_to" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
        </div>
        <div class="a-actions">
            <button type="submit" class="a-btn a-btn--dark">Terapkan</button>
        </div>
    </form>

    <?php if ($result['rows'] === []): ?>
        <div class="a-empty">Tidak ada pesanan yang sesuai filter.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Penerimaan</th>
                        <th>Total</th>
                        <th>Pembayaran</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['rows'] as $order): ?>
                        <tr>
                            <td><strong><?= e($order['order_code']) ?></strong></td>
                            <td>
                                <?= e($order['customer_name']) ?>
                                <div class="a-tiny a-muted"><?= e($order['whatsapp']) ?></div>
                            </td>
                            <td>
                                <span class="a-badge a-badge--muted"><?= $order['fulfillment'] === 'pickup' ? 'Pickup' : 'Delivery' ?></span>
                                <div class="a-tiny a-muted"><?= e(tanggal_id((string) $order['delivery_date'])) ?></div>
                            </td>
                            <td class="a-price"><?= e(rupiah($order['total'])) ?></td>
                            <td>
                                <span class="a-badge a-badge--<?= e($order['payment_status'] === 'paid' ? 'success' : ($order['payment_status'] === 'cancelled' ? 'danger' : 'warning')) ?>">
                                    <?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="a-badge a-badge--<?= e($badge(Order::statusBadgeClass((string) $order['order_status']))) ?>">
                                    <?= e(Order::statusLabel((string) $order['order_status'])) ?>
                                </span>
                            </td>
                            <td class="a-tiny a-muted"><?= e(tanggal_id((string) $order['created_at'], true)) ?></td>
                            <td style="text-align: right;">
                                <a href="<?= url('/admin/pesanan/' . $order['id']) ?>" class="a-btn a-btn--ghost a-btn--small">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result['pages'] > 1): ?>
            <div class="pagination">
                <?php if ($result['page'] > 1): ?>
                    <a href="<?= e($buildUrl($result['page'] - 1)) ?>">←</a>
                <?php else: ?>
                    <span class="is-disabled">←</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
                    <?php if ($i === $result['page']): ?>
                        <span class="is-current"><?= e($i) ?></span>
                    <?php else: ?>
                        <a href="<?= e($buildUrl($i)) ?>"><?= e($i) ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($result['page'] < $result['pages']): ?>
                    <a href="<?= e($buildUrl($result['page'] + 1)) ?>">→</a>
                <?php else: ?>
                    <span class="is-disabled">→</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
