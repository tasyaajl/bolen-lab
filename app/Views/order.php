<?php
/**
 * @var array<string, mixed> $order
 * @var list<array<string, mixed>> $items
 * @var array<string, string|null> $settings
 */

use App\Models\Order;

$waNumber = setting('whatsapp');
$waMessage = 'Halo BaleBolen, saya sudah membuat pesanan ' . $order['order_code']
    . ' atas nama ' . $order['customer_name'] . '. Mohon dibantu konfirmasinya.';
$method = (string) $order['payment_method'];
$isManualPayment = in_array($method, ['transfer', 'qris', 'gopay'], true);
?>

<div class="container order-page">
    <div class="order-hero">
        <div>
            <span class="order-hero__code">Kode pesanan</span>
            <h1><?= e($order['order_code']) ?></h1>
            <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px;">
                <span class="badge <?= e(Order::statusBadgeClass((string) $order['order_status'])) ?>"><?= e(Order::statusLabel((string) $order['order_status'])) ?></span>
                <span class="badge <?= e(Order::paymentBadgeClass((string) $order['payment_status'])) ?>">Pembayaran: <?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?></span>
            </div>
        </div>
        <div class="order-hero__actions">
            <button type="button" class="btn btn--ghost btn--small" onclick="window.print()">Cetak pesanan</button>
            <?php if ($waNumber): ?>
                <a href="<?= e(whatsapp_link($waNumber, $waMessage)) ?>" target="_blank" rel="noopener" class="btn btn--primary btn--small">Konfirmasi via WhatsApp</a>
            <?php endif; ?>
            <a href="<?= url('/menu') ?>" class="btn btn--ghost btn--small">Pesan lagi</a>
        </div>
    </div>

    <p class="muted" style="margin-top: 14px; font-size: 0.86rem;">
        Simpan halaman ini atau tautannya untuk memeriksa status pesanan Anda.
        Halaman ini dilindungi: untuk membukanya di perangkat lain, masukkan empat digit terakhir nomor WhatsApp Anda.
        Dibuat <?= e(tanggal_id((string) $order['created_at'], true)) ?>.
    </p>

    <?php if (!empty($order['payment_due_at']) && !in_array((string) $order['order_status'], ['cancelled', 'completed'], true)): ?>
        <div class="notice" style="margin-top: 14px;">
            <div>
                <strong>Batas waktu pembayaran:</strong>
                <?= e(tanggal_id((string) $order['payment_due_at'], true)) ?>.
                Pesanan yang belum dibayar sampai batas waktu tersebut akan dibatalkan otomatis
                dan stoknya dikembalikan.
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($order['cancel_reason'])): ?>
        <div class="notice notice--sage" style="margin-top: 14px;">
            <div><strong>Alasan pembatalan:</strong> <?= e($order['cancel_reason']) ?></div>
        </div>
    <?php endif; ?>

    <?php if (setting('order_note')): ?>
        <div class="notice notice--sage" style="margin-top: 14px;">
            <div><?= e(setting('order_note')) ?></div>
        </div>
    <?php endif; ?>

    <div class="order-grid">
        <div>
            <div class="info-card">
                <h2>Detail pesanan</h2>

                <?php foreach ($items as $item): ?>
                    <div class="order-item-row">
                        <div>
                            <h3>
                                <?= e($item['product_name']) ?>
                                <span class="muted" style="font-weight: 400;">· <?= e($item['quantity']) ?>×</span>
                            </h3>
                            <?php if (!empty($item['composition_text'])): ?>
                                <p>Komposisi: <?= e($item['composition_text']) ?></p>
                            <?php endif; ?>
                            <p><?= e(rupiah($item['unit_price'])) ?> / item</p>
                        </div>
                        <div class="price"><?= e(rupiah($item['line_total'])) ?></div>
                    </div>
                <?php endforeach; ?>

                <div class="summary-row" style="margin-top: 14px;">
                    <span>Subtotal produk</span>
                    <span><?= e(rupiah($order['subtotal'])) ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos kirim</span>
                    <span>
                        <?= $order['shipping_cost'] !== null ? e(rupiah($order['shipping_cost'])) : '<span class="tiny muted">Menunggu konfirmasi admin</span>' ?>
                    </span>
                </div>
                <div class="summary-row summary-row--total">
                    <span><?= $order['shipping_cost'] !== null ? 'Total pembayaran' : 'Total sementara' ?></span>
                    <strong><?= e(rupiah($order['total'])) ?></strong>
                </div>
                <?php if ($order['shipping_cost'] === null && $order['fulfillment'] === 'delivery'): ?>
                    <p class="summary-note">Ongkos kirim belum ditetapkan. Admin akan mengonfirmasi biaya pengiriman sebelum pembayaran final.</p>
                <?php endif; ?>
            </div>

            <div class="info-card">
                <h2>Penerimaan</h2>
                <dl class="info-list">
                    <div>
                        <dt>Metode</dt>
                        <dd><?= $order['fulfillment'] === 'pickup' ? 'Ambil sendiri (pickup)' : 'Delivery' ?></dd>
                    </div>
                    <?php if ($order['fulfillment'] === 'delivery'): ?>
                        <div>
                            <dt>Jasa pengiriman</dt>
                            <dd><?= e(Order::deliveryMethodLabel($order['delivery_method'])) ?></dd>
                        </div>
                        <div>
                            <dt>Alamat</dt>
                            <dd><?= e($order['delivery_address']) ?></dd>
                        </div>
                    <?php endif; ?>
                    <div>
                        <dt>Tanggal penerimaan</dt>
                        <dd><?= e(tanggal_id((string) $order['delivery_date'])) ?></dd>
                    </div>
                    <div>
                        <dt>Nama pelanggan</dt>
                        <dd><?= e($order['customer_name']) ?></dd>
                    </div>
                    <div>
                        <dt>WhatsApp</dt>
                        <dd><?= e($order['whatsapp']) ?></dd>
                    </div>
                </dl>

                <?php if ($order['fulfillment'] === 'pickup' && setting('address')): ?>
                    <div class="payment-instructions">
                        <strong>Lokasi pickup</strong><br>
                        <?= e(setting('address')) ?>
                        <?php if (setting('pickup_instructions')): ?>
                            <br><span class="tiny"><?= e(setting('pickup_instructions')) ?></span>
                        <?php endif; ?>
                    </div>
                <?php elseif ($order['fulfillment'] === 'pickup'): ?>
                    <div class="payment-instructions">
                        Alamat pickup akan dikonfirmasi admin melalui WhatsApp.
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($order['gift_recipient']) || !empty($order['gift_message']) || !empty($order['notes'])): ?>
                <div class="info-card">
                    <h2>Catatan &amp; kartu ucapan</h2>
                    <dl class="info-list">
                        <?php if (!empty($order['gift_recipient'])): ?>
                            <div>
                                <dt>Penerima hadiah</dt>
                                <dd><?= e($order['gift_recipient']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($order['gift_message'])): ?>
                            <div>
                                <dt>Pesan kartu ucapan</dt>
                                <dd><?= e($order['gift_message']) ?></dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($order['notes'])): ?>
                            <div>
                                <dt>Catatan tambahan</dt>
                                <dd><?= nl2br(e($order['notes'])) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>
        </div>

        <aside>
            <div class="total-box">
                <span class="order-hero__code" style="color: rgba(255,247,236,0.6);">Pembayaran</span>
                <div class="row" style="padding-top: 6px;">
                    <span>Metode</span>
                    <strong style="color: #fff;"><?= e(Order::paymentMethodLabel($method)) ?></strong>
                </div>
                <div class="row">
                    <span>Status</span>
                    <span><?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?></span>
                </div>
                <div class="row">
                    <span>Subtotal</span>
                    <span><?= e(rupiah($order['subtotal'])) ?></span>
                </div>
                <div class="row">
                    <span>Ongkos kirim</span>
                    <span><?= $order['shipping_cost'] !== null ? e(rupiah($order['shipping_cost'])) : 'Belum ditetapkan' ?></span>
                </div>
                <div class="row row--grand">
                    <span><?= $order['shipping_cost'] !== null ? 'Total' : 'Total sementara' ?></span>
                    <strong><?= e(rupiah($order['total'])) ?></strong>
                </div>

                <?php if ($isManualPayment): ?>
                    <div class="payment-instructions" style="background: rgba(255,247,236,0.08); border-color: rgba(255,247,236,0.2); color: rgba(255,247,236,0.75);">
                        <strong style="color: #fff;">Instruksi pembayaran</strong>
                        <?php if ($method === 'transfer' && setting('bank_number')): ?>
                            <dl>
                                <div><dt>Bank</dt><dd style="color:#fff;"><?= e(setting('bank_name', '-')) ?></dd></div>
                                <div><dt>Nomor rekening</dt><dd style="color:#fff;"><?= e(setting('bank_number')) ?></dd></div>
                                <div><dt>Atas nama</dt><dd style="color:#fff;"><?= e(setting('bank_holder', '-')) ?></dd></div>
                            </dl>
                        <?php elseif ($method === 'qris' && (setting('qris_image') || setting('qris_note'))): ?>
                            <?php if (setting('qris_note')): ?><p style="margin-top: 8px; color: rgba(255,247,236,0.8);"><?= e(setting('qris_note')) ?></p><?php endif; ?>
                            <?php if (setting('qris_image')): ?>
                                <img src="<?= url('/uploads/brand/' . setting('qris_image')) ?>" alt="QRIS BaleBolen">
                            <?php endif; ?>
                        <?php elseif ($method === 'gopay' && setting('ewallet_number')): ?>
                            <dl>
                                <div><dt>E-Wallet</dt><dd style="color:#fff;"><?= e(setting('ewallet_name', '-')) ?></dd></div>
                                <div><dt>Nomor</dt><dd style="color:#fff;"><?= e(setting('ewallet_number')) ?></dd></div>
                            </dl>
                        <?php else: ?>
                            <p style="margin-top: 8px;">Detail pembayaran akan dikirim admin melalui WhatsApp setelah pesanan diperiksa.</p>
                        <?php endif; ?>
                    </div>
                <?php elseif ($method === 'cod'): ?>
                    <p class="hint">Pembayaran dilakukan saat pesanan diterima (COD).</p>
                <?php else: ?>
                    <p class="hint">Pembayaran dilakukan secara tunai saat mengambil pesanan di toko.</p>
                <?php endif; ?>
            </div>

            <?php if ($canUploadProof && !in_array((string) $order['order_status'], ['cancelled', 'completed'], true)): ?>
                <div class="info-card" style="margin-top: 20px;">
                    <h2>Bukti pembayaran</h2>

                    <?php if (!empty($order['payment_proof'])): ?>
                        <p class="tiny muted">
                            Bukti sudah diunggah <?= e(tanggal_id((string) $order['payment_proof_at'], true)) ?>.
                            Status: <strong><?= e(Order::paymentStatusLabel((string) $order['payment_status'])) ?></strong>.
                        </p>
                        <a href="<?= url('/pesanan/' . $order['order_code'] . '/bukti') ?>" target="_blank" rel="noopener" class="btn btn--ghost btn--small" style="margin-top: 10px;">Lihat bukti</a>
                    <?php endif; ?>

                    <?php if ((string) $order['payment_status'] !== 'paid'): ?>
                        <form method="post" action="<?= url('/pesanan/' . $order['order_code'] . '/bukti') ?>" enctype="multipart/form-data" style="margin-top: 14px;">
                            <?= csrf_field() ?>
                            <div class="field">
                                <label class="label" for="proof"><?= !empty($order['payment_proof']) ? 'Ganti bukti pembayaran' : 'Unggah bukti pembayaran' ?></label>
                                <input class="input" type="file" id="proof" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                                <span class="hint">JPG, PNG, WEBP, atau PDF. Maksimal <?= e($maxUploadMb) ?> MB.</span>
                            </div>
                            <button type="submit" class="btn btn--primary btn--block" style="margin-top: 12px;">Kirim bukti</button>
                        </form>
                    <?php else: ?>
                        <p class="tiny muted" style="margin-top: 10px;">Pembayaran sudah diverifikasi admin.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (setting('cancellation_policy')): ?>
                <div class="info-card" style="margin-top: 20px;">
                    <h2>Kebijakan pembatalan</h2>
                    <p class="tiny muted"><?= nl2br(e(setting('cancellation_policy'))) ?></p>
                </div>
            <?php endif; ?>

            <div class="info-card" style="margin-top: 20px;">
                <h2>Butuh bantuan?</h2>
                <p class="tiny muted" style="margin-bottom: 14px;">
                    Hubungi BaleBolen dan sebutkan kode pesanan <strong><?= e($order['order_code']) ?></strong>.
                </p>
                <?php if ($waNumber): ?>
                    <a href="<?= e(whatsapp_link($waNumber, $waMessage)) ?>" target="_blank" rel="noopener" class="btn btn--dark btn--block">Chat WhatsApp</a>
                <?php else: ?>
                    <p class="tiny muted">Nomor WhatsApp belum diisi admin.</p>
                <?php endif; ?>
                <a href="<?= url('/kontak') ?>" class="link-arrow" style="margin-top: 16px; font-size: 0.85rem;">Halaman kontak</a>
            </div>
        </aside>
    </div>
</div>
