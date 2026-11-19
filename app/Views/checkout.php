<?php
/**
 * @var list<array<string, mixed>> $lines
 * @var int $subtotal
 * @var bool $hasHampers
 * @var string|null $preorderDays
 * @var string $minDate
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 */

use App\Models\Order;
use App\Models\Product;

$fulfillment = $old['fulfillment'] ?? 'pickup';
$payment = $old['payment_method'] ?? 'transfer';
$errorClass = static fn (string $field): string => isset($errors[$field]) ? 'has-error' : '';
$paymentOptions = [
    'transfer' => ['label' => 'Transfer Bank', 'desc' => 'Nomor rekening diberikan admin saat konfirmasi.'],
    'qris'     => ['label' => 'QRIS', 'desc' => 'Scan QRIS dari admin saat konfirmasi.'],
    'gopay'    => ['label' => 'GoPay / E-Wallet', 'desc' => 'Instruksi pembayaran diberikan admin.'],
    'cod'      => ['label' => 'COD', 'desc' => 'Bayar saat pesanan diterima. Khusus delivery.'],
    'cash'     => ['label' => 'Tunai saat pickup', 'desc' => 'Bayar di toko saat mengambil pesanan.'],
];
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <a href="<?= url('/keranjang') ?>">Keranjang</a>
            <span class="sep">/</span>
            <span>Checkout</span>
        </nav>
        <span class="eyebrow">Langkah terakhir</span>
        <h1>Checkout</h1>
        <p>Isi data pemesan, pilih cara penerimaan dan pembayaran. Tidak perlu membuat akun.</p>
    </div>
</section>

<div class="container">
    <div class="checkout-layout">
        <form method="post" action="<?= url('/checkout') ?>" id="checkoutForm" novalidate>
            <?= csrf_field() ?>

            <?php /* Honeypot anti-spam: tersembunyi dari pengguna, diisi bot. */ ?>
            <div aria-hidden="true" style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <?php if (isset($errors['cart'])): ?>
                <div class="alert alert--error"><?= e($errors['cart']) ?> <a href="<?= url('/keranjang') ?>" class="link-arrow" style="margin-left: 4px;">Perbarui keranjang</a></div>
            <?php endif; ?>
            <?php if ($errors !== [] && !isset($errors['cart'])): ?>
                <div class="alert alert--error">Beberapa data belum lengkap. Periksa kembali field yang ditandai.</div>
            <?php endif; ?>

            <div class="form-card">
                <div class="form-card__head">
                    <span class="step-num">1</span>
                    <h2>Data pemesan</h2>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label class="label" for="customer_name">Nama pelanggan <span class="req">*</span></label>
                        <input class="input <?= $errorClass('customer_name') ?>" type="text" id="customer_name" name="customer_name" value="<?= old('customer_name') ?>" maxlength="100" required autocomplete="name">
                        <?php if (isset($errors['customer_name'])): ?><span class="error-text"><?= e($errors['customer_name']) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label class="label" for="whatsapp">Nomor WhatsApp <span class="req">*</span></label>
                        <input class="input <?= $errorClass('whatsapp') ?>" type="tel" id="whatsapp" name="whatsapp" value="<?= old('whatsapp') ?>" placeholder="081234567890" maxlength="20" required autocomplete="tel">
                        <?php if (isset($errors['whatsapp'])): ?><span class="error-text"><?= e($errors['whatsapp']) ?></span><?php endif; ?>
                        <span class="hint">Dipakai admin untuk mengonfirmasi pesanan.</span>
                    </div>
                </div>
            </div>

            <div class="form-card">
                <div class="form-card__head">
                    <span class="step-num">2</span>
                    <h2>Penerimaan pesanan</h2>
                </div>

                <div class="radio-grid" style="margin-bottom: 22px;">
                    <label class="radio-card">
                        <input type="radio" name="fulfillment" value="pickup" data-fulfillment <?= $fulfillment === 'pickup' ? 'checked' : '' ?>>
                        <span class="radio-card__dot"></span>
                        <span class="radio-card__text">
                            <strong>Ambil sendiri (pickup)</strong>
                            <span>Ambil pesanan langsung di lokasi BaleBolen.</span>
                        </span>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="fulfillment" value="delivery" data-fulfillment <?= $fulfillment === 'delivery' ? 'checked' : '' ?>>
                        <span class="radio-card__dot"></span>
                        <span class="radio-card__text">
                            <strong>Delivery</strong>
                            <span>Diantar oleh BaleBolen, Gojek, atau kurir lain.</span>
                        </span>
                    </label>
                </div>
                <?php if (isset($errors['fulfillment'])): ?><span class="error-text" style="margin-top: -12px; margin-bottom: 14px;"><?= e($errors['fulfillment']) ?></span><?php endif; ?>

                <div data-delivery-fields class="<?= $fulfillment === 'delivery' ? '' : 'is-hidden' ?>">
                    <div class="field">
                        <label class="label" for="delivery_method">Jasa pengiriman <span class="req">*</span></label>
                        <select class="select <?= $errorClass('delivery_method') ?>" id="delivery_method" name="delivery_method">
                            <option value="">Pilih pengiriman</option>
                            <?php foreach (Order::DELIVERY_METHODS as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= ($old['delivery_method'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['delivery_method'])): ?><span class="error-text"><?= e($errors['delivery_method']) ?></span><?php endif; ?>
                        <span class="hint">Ongkos kirim dikonfirmasi admin secara manual. Belum ada tarif otomatis.</span>
                    </div>
                    <div class="field">
                        <label class="label" for="delivery_address">Alamat pengiriman <span class="req">*</span></label>
                        <textarea class="textarea <?= $errorClass('delivery_address') ?>" id="delivery_address" name="delivery_address" maxlength="500" placeholder="Nama jalan, nomor rumah, kelurahan, kecamatan, kota, dan patokan."><?= old('delivery_address') ?></textarea>
                        <?php if (isset($errors['delivery_address'])): ?><span class="error-text"><?= e($errors['delivery_address']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="field">
                    <label class="label" for="delivery_date">Tanggal penerimaan <span class="req">*</span></label>
                    <input class="input <?= $errorClass('delivery_date') ?>" type="date" id="delivery_date" name="delivery_date" value="<?= old('delivery_date') ?>" min="<?= e($minDate) ?>" required>
                    <?php if (isset($errors['delivery_date'])): ?><span class="error-text"><?= e($errors['delivery_date']) ?></span><?php endif; ?>
                    <?php if ($hasHampers && $preorderDays !== null): ?>
                        <span class="hint">Keranjang berisi paket hampers, sehingga tanggal paling cepat <?= e(tanggal_id($minDate)) ?> (pre-order <?= e($preorderDays) ?> hari).</span>
                    <?php else: ?>
                        <span class="hint">Untuk bolen satuan, pesanan dapat diambil/dikirim hari yang sama selama stok tersedia.</span>
                    <?php endif; ?>
                </div>

                <div class="field">
                    <label class="label" for="notes">Catatan tambahan</label>
                    <textarea class="textarea <?= $errorClass('notes') ?>" id="notes" name="notes" maxlength="1000" placeholder="Contoh: tanpa kismis, hubungi sebelum tiba, dll."><?= old('notes') ?></textarea>
                    <?php if (isset($errors['notes'])): ?><span class="error-text"><?= e($errors['notes']) ?></span><?php endif; ?>
                </div>

                <?php if ($hasHampers): ?>
                    <div style="border-top: 1px solid var(--border); margin: 22px 0; padding-top: 22px;">
                        <span class="panel__label">Kartu ucapan hampers</span>
                        <div class="field-row">
                            <div class="field">
                                <label class="label" for="gift_recipient">Nama penerima hadiah</label>
                                <input class="input <?= $errorClass('gift_recipient') ?>" type="text" id="gift_recipient" name="gift_recipient" value="<?= old('gift_recipient') ?>" maxlength="100" placeholder="Untuk: ...">
                                <?php if (isset($errors['gift_recipient'])): ?><span class="error-text"><?= e($errors['gift_recipient']) ?></span><?php endif; ?>
                            </div>
                            <div class="field">
                                <label class="label" for="gift_message">Pesan khusus</label>
                                <input class="input <?= $errorClass('gift_message') ?>" type="text" id="gift_message" name="gift_message" value="<?= old('gift_message') ?>" maxlength="300" placeholder="Selamat ulang tahun!">
                                <?php if (isset($errors['gift_message'])): ?><span class="error-text"><?= e($errors['gift_message']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <span class="hint">Pesan ini akan dicetak pada kartu ucapan yang disertakan dalam kotak hampers.</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-card">
                <div class="form-card__head">
                    <span class="step-num">3</span>
                    <h2>Metode pembayaran</h2>
                </div>

                <div class="radio-grid">
                    <?php foreach ($paymentOptions as $value => $option): ?>
                        <label class="radio-card" data-payment-card data-for="<?= e($value === 'cod' ? 'delivery' : ($value === 'cash' ? 'pickup' : 'both')) ?>">
                            <input type="radio" name="payment_method" value="<?= e($value) ?>" <?= $payment === $value ? 'checked' : '' ?>>
                            <span class="radio-card__dot"></span>
                            <span class="radio-card__text">
                                <strong><?= e($option['label']) ?></strong>
                                <span><?= e($option['desc']) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['payment_method'])): ?><span class="error-text"><?= e($errors['payment_method']) ?></span><?php endif; ?>

                <p class="hint" style="margin-top: 14px;">
                    Status awal pesanan adalah <strong>Belum Dibayar</strong>. Setelah pesanan dibuat, Anda dapat
                    mengunggah bukti transfer/QRIS dari halaman pesanan agar admin lebih cepat memverifikasi.
                </p>
            </div>

            <button type="submit" class="btn btn--primary btn--block" style="margin-top: 4px;">Buat Pesanan</button>
            <p class="hint center" style="margin-top: 12px;">Dengan membuat pesanan, Anda menyetujui data di atas dikirim ke BaleBolen untuk diproses.</p>
        </form>

        <aside class="summary-card">
            <h2>Ringkasan pesanan</h2>

            <?php foreach ($lines as $line): ?>
                <?php $product = $line['product']; ?>
                <div class="summary-line">
                    <div class="summary-thumb">
                        <img src="<?= e(Product::imageUrl($product)) ?>" alt="">
                    </div>
                    <div>
                        <div class="summary-line__name"><?= e($product['name']) ?></div>
                        <div class="summary-line__meta">
                            <?= e($line['quantity']) ?> ×
                            <?= $line['unit_price'] !== null ? e(rupiah($line['unit_price'])) : 'harga belum diatur' ?>
                        </div>
                        <?php if ($line['composition'] !== []): ?>
                            <div class="summary-line__meta">
                                <?php
                                $parts = [];
                                foreach ($line['composition'] as $row) {
                                    $parts[] = $row['quantity'] . '× ' . $row['name'];
                                }
                                echo e(implode(', ', $parts));
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="summary-line__price"><?= $line['line_total'] !== null ? e(rupiah($line['line_total'])) : '—' ?></div>
                </div>
            <?php endforeach; ?>

            <div class="summary-row" style="margin-top: 10px;">
                <span>Subtotal</span>
                <span><?= e(rupiah($subtotal)) ?></span>
            </div>
            <div class="summary-row">
                <span>Ongkos kirim</span>
                <span class="tiny muted">Belum dikonfirmasi</span>
            </div>
            <div class="summary-row summary-row--total">
                <span>Total saat ini</span>
                <strong><?= e(rupiah($subtotal)) ?></strong>
            </div>
            <p class="summary-note">
                Total final ditambahkan setelah admin mengonfirmasi ongkos kirim. Tidak ada biaya
                pengiriman yang dihitung otomatis.
            </p>

            <a href="<?= url('/keranjang') ?>" class="link-arrow" style="margin-top: 18px; font-size: 0.85rem;">← Edit keranjang</a>
        </aside>
    </div>
</div>
