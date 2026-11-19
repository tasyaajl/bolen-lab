<?php
/**
 * @var array<string, mixed> $order
 * @var list<array<string, mixed>> $items
 */

use App\Models\Order;

$badge = static fn (string $class): string => str_replace('badge--', '', $class);
$waMessage = 'Halo ' . $order['customer_name'] . ', terima kasih telah memesan di BaleBolen. Kami ingin mengonfirmasi pesanan ' . $order['order_code'] . '.';
?>

<div class="a-row" style="justify-content: space-between; margin-bottom: 18px;">
    <a href="<?= url('/admin/pesanan') ?>" class="a-btn a-btn--ghost a-btn--small">← Semua pesanan</a>
    <div class="a-row">
        <span class="a-badge a-badge--<?= e($badge(Order::statusBadgeClass((string) $order['order_status']))) ?>"><?= e(Order::statusLabel((string) $order['order_status'])) ?></span>
        <span class="a-badge a-badge--<?= e(str_replace('badge--', '', Order::paymentBadgeClass((string) $order['payment_status']))) ?>"><?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?></span>
        <?php if (setting('whatsapp')): ?>
            <a href="<?= e(whatsapp_link(setting('whatsapp'), $waMessage)) ?>" target="_blank" rel="noopener" class="a-btn a-btn--ghost a-btn--small">Hubungi pelanggan</a>
        <?php else: ?>
            <a href="<?= e(whatsapp_link($order['whatsapp'], $waMessage)) ?>" target="_blank" rel="noopener" class="a-btn a-btn--ghost a-btn--small">Chat pelanggan</a>
        <?php endif; ?>
    </div>
</div>

<div class="order-layout">
    <div>
        <div class="a-card">
            <div class="a-card__head">
                <div>
                    <h2><?= e($order['order_code']) ?></h2>
                    <p>Dibuat <?= e(tanggal_id((string) $order['created_at'], true)) ?> · terakhir diperbarui <?= e(tanggal_id((string) $order['updated_at'], true)) ?></p>
                </div>
                <div class="a-price" style="font-family: var(--font-display); font-size: 1.5rem;"><?= e(rupiah($order['total'])) ?></div>
            </div>

            <?php foreach ($items as $item): ?>
                <div class="a-order-item">
                    <div>
                        <strong><?= e($item['product_name']) ?> × <?= e($item['quantity']) ?></strong>
                        <?php if (!empty($item['composition_text'])): ?>
                            <span>Komposisi: <?= e($item['composition_text']) ?></span><br>
                        <?php endif; ?>
                        <span><?= e(rupiah($item['unit_price'])) ?> / item</span>
                    </div>
                    <div class="a-price"><?= e(rupiah($item['line_total'])) ?></div>
                </div>
            <?php endforeach; ?>

            <div class="a-sep"></div>
            <dl class="detail-list">
                <div><dt>Subtotal produk</dt><dd><?= e(rupiah($order['subtotal'])) ?></dd></div>
                <div>
                    <dt>Ongkos kirim</dt>
                    <dd><?= $order['shipping_cost'] !== null ? e(rupiah($order['shipping_cost'])) : 'Belum ditetapkan' ?></dd>
                </div>
                <div><dt><strong>Total</strong></dt><dd><strong><?= e(rupiah($order['total'])) ?></strong></dd></div>
            </dl>
        </div>

        <div class="a-grid a-grid--2">
            <div class="a-card">
                <div class="a-card__head"><div><h2>Pelanggan</h2></div></div>
                <dl class="detail-list">
                    <div><dt>Nama</dt><dd><?= e($order['customer_name']) ?></dd></div>
                    <div><dt>WhatsApp</dt><dd><?= e($order['whatsapp']) ?></dd></div>
                    <div><dt>Metode</dt><dd><?= $order['fulfillment'] === 'pickup' ? 'Pickup' : 'Delivery' ?></dd></div>
                    <?php if ($order['fulfillment'] === 'delivery'): ?>
                        <div><dt>Jasa kirim</dt><dd><?= e(Order::deliveryMethodLabel($order['delivery_method'])) ?></dd></div>
                        <div><dt>Alamat</dt><dd><?= e($order['delivery_address']) ?></dd></div>
                    <?php endif; ?>
                    <div><dt>Tanggal terima</dt><dd><?= e(tanggal_id((string) $order['delivery_date'])) ?></dd></div>
                </dl>
            </div>

            <div class="a-card">
                <div class="a-card__head"><div><h2>Catatan</h2></div></div>
                <dl class="detail-list">
                    <div><dt>Penerima hadiah</dt><dd><?= e($order['gift_recipient'] ?: '-') ?></dd></div>
                    <div><dt>Pesan kartu</dt><dd><?= e($order['gift_message'] ?: '-') ?></dd></div>
                    <div><dt>Catatan tambahan</dt><dd><?= e($order['notes'] ?: '-') ?></dd></div>
                </dl>
            </div>
        </div>
    </div>

    <div>
        <div class="a-card">
            <div class="a-card__head"><div><h2>Status pesanan</h2></div></div>
            <form method="post" action="<?= url('/admin/pesanan/' . $order['id'] . '/status') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label for="order_status">Status</label>
                    <?php if ($allowedTransitions === []): ?>
                        <input type="text" id="order_status" value="<?= e(Order::statusLabel((string) $order['order_status'])) ?>" disabled>
                        <span class="hint">Status ini bersifat final dan tidak dapat diubah lagi.</span>
                    <?php else: ?>
                        <select id="order_status" name="order_status">
                            <option value="<?= e((string) $order['order_status']) ?>" selected><?= e(Order::statusLabel((string) $order['order_status'])) ?> (saat ini)</option>
                            <?php foreach ($allowedTransitions as $value): ?>
                                <option value="<?= e($value) ?>" data-status="<?= e($value) ?>"><?= e(Order::statusLabel($value)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint">Hanya transisi yang diizinkan yang ditampilkan.</span>
                    <?php endif; ?>
                </div>
                <?php if ($allowedTransitions !== []): ?>
                    <div class="a-field">
                        <label for="cancel_reason">Alasan pembatalan (opsional)</label>
                        <input type="text" id="cancel_reason" name="cancel_reason" maxlength="255" placeholder="Contoh: stok habis / permintaan pelanggan">
                        <span class="hint">Dipakai bila status diubah menjadi Dibatalkan. Membatalkan pesanan mengembalikan stok varian.</span>
                    </div>
                    <button type="submit" class="a-btn a-btn--dark" data-confirm="Ubah status pesanan ini? Perubahan status berpengaruh pada stok dan data pesanan.">Simpan status</button>
                <?php endif; ?>
            </form>
        </div>

        <div class="a-card">
            <div class="a-card__head"><div><h2>Pembayaran</h2></div></div>
            <dl class="detail-list" style="margin-bottom: 16px;">
                <div><dt>Metode</dt><dd><?= e(Order::paymentMethodLabel((string) $order['payment_method'])) ?></dd></div>
                <div><dt>Status</dt><dd><?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?></dd></div>
                <?php if (!empty($order['payment_due_at'])): ?>
                    <div><dt>Batas bayar</dt><dd><?= e(tanggal_id((string) $order['payment_due_at'], true)) ?></dd></div>
                <?php endif; ?>
                <div>
                    <dt>Bukti bayar</dt>
                    <dd>
                        <?php if (!empty($order['payment_proof'])): ?>
                            <a href="<?= url('/admin/pesanan/' . $order['id'] . '/bukti') ?>" target="_blank" rel="noopener" class="a-btn a-btn--ghost a-btn--small">Lihat bukti</a>
                            <span class="a-tiny a-muted">diunggah <?= e(tanggal_id((string) $order['payment_proof_at'], true)) ?></span>
                        <?php else: ?>
                            <span class="a-tiny a-muted">Belum diunggah pelanggan</span>
                        <?php endif; ?>
                    </dd>
                </div>
            </dl>
            <?php if (!empty($order['payment_proof']) && $order['payment_status'] !== 'paid'): ?>
                <form method="post" action="<?= url('/admin/pesanan/' . $order['id'] . '/bukti/hapus') ?>" style="margin-bottom: 12px;" data-confirm="Hapus bukti pembayaran ini? Status pembayaran akan kembali ke Belum Dibayar.">
                    <?= csrf_field() ?>
                    <button type="submit" class="a-btn a-btn--ghost a-btn--small">Hapus bukti</button>
                </form>
            <?php endif; ?>
            <form method="post" action="<?= url('/admin/pesanan/' . $order['id'] . '/pembayaran') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label for="payment_status">Ubah status pembayaran</label>
                    <select id="payment_status" name="payment_status">
                        <?php foreach (Order::PAYMENT_STATUSES as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= $order['payment_status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="a-btn a-btn--primary" data-confirm="Ubah status pembayaran pesanan ini?">Simpan pembayaran</button>
            </form>
            <p class="a-tiny a-muted" style="margin-top: 10px;">Pelanggan dapat mengunggah bukti transfer/QRIS dari halaman pesanan; admin tetap memverifikasi manual.</p>
        </div>

        <div class="a-card">
            <div class="a-card__head"><div><h2>Ongkos kirim</h2></div></div>
            <form method="post" action="<?= url('/admin/pesanan/' . $order['id'] . '/ongkir') ?>" class="a-form">
                <?= csrf_field() ?>
                <div class="a-field">
                    <label for="shipping_cost">Biaya pengiriman (Rp)</label>
                    <input type="number" id="shipping_cost" name="shipping_cost" min="0" step="500" value="<?= $order['shipping_cost'] !== null ? e((float) $order['shipping_cost']) : '' ?>" placeholder="Belum ditetapkan">
                    <span class="hint">Kosongkan bila belum ditetapkan. Total dihitung otomatis: subtotal + ongkir.</span>
                </div>
                <button type="submit" class="a-btn a-btn--ghost" data-confirm="Perbarui ongkos kirim dan total pesanan?">Perbarui ongkir</button>
            </form>
        </div>
    </div>
</div>
