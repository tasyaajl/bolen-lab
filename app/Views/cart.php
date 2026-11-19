<?php
/**
 * @var list<array<string, mixed>> $lines
 * @var int $subtotal
 * @var bool $hasHampers
 * @var string|null $preorderDays
 */

use App\Models\Product;
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <a href="<?= url('/menu') ?>">Menu</a>
            <span class="sep">/</span>
            <span>Keranjang</span>
        </nav>
        <span class="eyebrow">Pesanan Anda</span>
        <h1>Keranjang</h1>
    </div>
</section>

<?php if ($lines === []): ?>
    <div class="container">
        <div class="empty-state">
            <h2>Keranjang masih kosong</h2>
            <p>Mulai pilih bolen satuan atau susun paket hampers untuk hadiah.</p>
            <a href="<?= url('/menu') ?>" class="btn btn--primary">Lihat Menu</a>
        </div>
    </div>
<?php else: ?>
    <div class="container">
        <div class="cart-layout">
            <div>
                <div class="cart-list">
                    <?php foreach ($lines as $line): ?>
                        <?php $product = $line['product']; ?>
                        <div class="cart-item">
                            <div class="cart-item__thumb">
                                <img src="<?= e(Product::imageUrl($product)) ?>" alt="<?= e($product['name']) ?>">
                            </div>
                            <div class="cart-item__info">
                                <h3><?= e($product['name']) ?></h3>
                                <div class="cart-item__meta">
                                    <?= $product['type'] === 'hampers' ? 'Paket isi ' . e($product['package_size']) . ' buah' : 'Bolen satuan' ?>
                                    · <?= e($line['quantity']) ?> ×
                                    <?= $line['unit_price'] !== null ? e(rupiah($line['unit_price'])) : 'harga belum diatur' ?>
                                </div>
                                <?php if ($line['composition'] !== []): ?>
                                    <div class="cart-item__comp">
                                        <strong>Komposisi:</strong>
                                        <?php
                                        $parts = [];
                                        foreach ($line['composition'] as $row) {
                                            $parts[] = e($row['quantity']) . '× ' . e($row['name']);
                                        }
                                        echo implode(' · ', $parts);
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="cart-item__side">
                                <span class="price">
                                    <?= $line['line_total'] !== null ? e(rupiah($line['line_total'])) : '—' ?>
                                </span>

                                <?php if ($product['type'] !== 'hampers'): ?>
                                    <?php $maxQty = max(1, min(50, Product::availableStock($product))); ?>
                                    <form method="post" action="<?= url('/keranjang/ubah') ?>" class="cart-qty">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="key" value="<?= e($line['key']) ?>">
                                        <label class="tiny muted" for="qty-<?= e($line['key']) ?>">Jumlah</label>
                                        <input class="input" type="number" id="qty-<?= e($line['key']) ?>" name="quantity"
                                               value="<?= e($line['quantity']) ?>" min="1" max="<?= e($maxQty) ?>" inputmode="numeric">
                                        <button type="submit" class="link-arrow">Perbarui</button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?= url('/menu/' . $product['slug']) ?>" class="tiny muted">Ubah komposisi</a>
                                <?php endif; ?>

                                <form method="post" action="<?= url('/keranjang/hapus') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="key" value="<?= e($line['key']) ?>">
                                    <button type="submit" class="link-danger">Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 20px; display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
                    <a href="<?= url('/menu') ?>" class="link-arrow">← Tambah produk lain</a>
                    <?php if ($hasHampers && $preorderDays !== null): ?>
                        <span class="tiny muted">Hampers dalam keranjang perlu dipesan minimal <?= e($preorderDays) ?> hari sebelumnya.</span>
                    <?php endif; ?>
                </div>
            </div>

            <aside class="summary-card">
                <h2>Ringkasan</h2>
                <div class="summary-row">
                    <span>Jumlah item</span>
                    <span><?= e(\App\Models\Cart::count()) ?> produk</span>
                </div>
                <div class="summary-row">
                    <span>Subtotal produk</span>
                    <span><?= e(rupiah($subtotal)) ?></span>
                </div>
                <div class="summary-row">
                    <span>Ongkos kirim</span>
                    <span class="tiny muted">Dikonfirmasi admin</span>
                </div>
                <div class="summary-row summary-row--total">
                    <span>Perkiraan total</span>
                    <strong><?= e(rupiah($subtotal)) ?></strong>
                </div>
                <p class="summary-note">
                    Total belum termasuk ongkos kirim. Biaya pengiriman akan dikonfirmasi admin
                    sesuai alamat dan metode pengiriman yang dipilih.
                </p>
                <a href="<?= url('/checkout') ?>" class="btn btn--primary btn--block">Lanjut ke Checkout</a>
            </aside>
        </div>
    </div>
<?php endif; ?>
