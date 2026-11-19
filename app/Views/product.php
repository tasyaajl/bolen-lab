<?php
/**
 * @var array<string, mixed> $product
 * @var list<array<string, mixed>> $variants
 * @var list<array<string, mixed>> $related
 * @var string|null $preorderDays
 * @var int $stock
 * @var array<string, string|null> $settings
 */

use App\Models\Product;

$isHampers = $product['type'] === 'hampers';
$price = $product['price'];
$packageSize = (int) ($product['package_size'] ?? 0);
$canOrder = $stock > 0 && $price !== null && (!$isHampers || $preorderDays !== null);
$minPreorderDate = $preorderDays !== null
    ? date('Y-m-d', strtotime('+' . (int) $preorderDays . ' days'))
    : null;
?>

<section class="page-head" style="padding-bottom: 0; border-bottom: none; background: transparent;">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <a href="<?= url('/menu') ?>">Menu</a>
            <span class="sep">/</span>
            <span><?= e($product['name']) ?></span>
        </nav>
    </div>
</section>

<div class="container">
    <div class="product-detail">
        <div class="product-detail__media" data-reveal>
            <img src="<?= e(Product::imageUrl($product)) ?>" alt="<?= e($product['name']) ?>">
        </div>

        <div class="product-detail__body" data-reveal>
            <span class="eyebrow"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <p class="product-detail__desc"><?= e($product['description'] ?? '') ?></p>

            <div class="product-detail__price">
                <?php if ($price === null): ?>
                    <span style="font-size: 1.3rem; color: var(--terracotta);">Harga belum ditetapkan</span>
                <?php else: ?>
                    <?= e(rupiah($price)) ?>
                    <?php if (!$isHampers): ?><small>per buah</small><?php endif; ?>
                <?php endif; ?>
            </div>

            <div style="margin-top: 14px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
                <span class="badge <?= $stock > 0 ? 'badge--success' : 'badge--danger' ?>">
                    <?= $stock > 0 ? 'Stok tersedia: ' . e($stock) . ' buah' : 'Stok habis' ?>
                </span>
                <?php if ($isHampers): ?>
                    <span class="badge badge--accent">Isi <?= e($packageSize) ?> buah</span>
                    <span class="badge badge--muted">Pre-order</span>
                <?php else: ?>
                    <span class="badge badge--gold">Same-day order</span>
                <?php endif; ?>
            </div>

            <?php if ($isHampers): ?>
                <div class="notice">
                    <div>
                        <strong>Kemasan hampers.</strong>
                        <?= e($product['packaging_info'] ?? 'Kotak premium dengan pita dan kartu ucapan.') ?>
                    </div>
                </div>
                <?php if ($price === null || $preorderDays === null): ?>
                    <div class="notice" style="background: rgba(185,111,85,0.08); border-color: rgba(185,111,85,0.25); color: #8a4630;">
                        <div>
                            <strong>Paket belum dapat dipesan.</strong>
                            <?php if ($price === null): ?>Harga paket belum ditetapkan admin.<?php endif; ?>
                            <?php if ($preorderDays === null): ?> Ketentuan minimal pre-order belum diatur admin.<?php endif; ?>
                            Silakan hubungi BaleBolen untuk informasi lebih lanjut.
                        </div>
                    </div>
                <?php elseif ($minPreorderDate): ?>
                    <p class="hint">Pre-order minimal <?= e($preorderDays) ?> hari. Tanggal penerimaan paling cepat <?= e(tanggal_id($minPreorderDate)) ?>.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($isHampers): ?>
                <form method="post" action="<?= url('/keranjang/tambah') ?>" class="panel" data-hampers-builder data-package-size="<?= e($packageSize) ?>" data-can-order="<?= $canOrder ? '1' : '0' ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                    <span class="panel__label">Susun komposisi rasa · tepat <?= e($packageSize) ?> buah</span>

                    <?php foreach ($variants as $variant): ?>
                        <?php $variantStock = (int) $variant['stock']; ?>
                        <div class="builder__row" data-variant-row>
                            <div class="builder__info">
                                <strong><?= e($variant['name']) ?></strong>
                                <small class="<?= $variantStock <= 0 ? 'out' : '' ?>">
                                    <?= $variantStock <= 0 ? 'Stok habis' : 'Stok ' . $variantStock . ' buah' ?>
                                </small>
                            </div>
                            <div class="qty-stepper">
                                <button type="button" data-step="-1" aria-label="Kurangi <?= e($variant['name']) ?>" <?= $variantStock <= 0 ? 'disabled' : '' ?>>−</button>
                                <input type="number" name="variants[<?= e($variant['id']) ?>]" value="0" min="0" max="<?= e($variantStock) ?>" data-qty readonly aria-label="Jumlah <?= e($variant['name']) ?>">
                                <button type="button" data-step="1" aria-label="Tambah <?= e($variant['name']) ?>" <?= $variantStock <= 0 ? 'disabled' : '' ?>>+</button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="builder__bar">
                        <div class="builder__count" data-builder-count>
                            Total <strong data-builder-total>0</strong> / <?= e($packageSize) ?> buah
                        </div>
                        <?php if ($price !== null): ?>
                            <div class="price" data-builder-price data-unit-price="<?= e((float) $price) ?>"><?= e(rupiah($price)) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="builder__actions">
                        <button type="submit" class="btn btn--primary" data-builder-submit disabled>
                            Tambahkan ke Keranjang
                        </button>
                        <a href="<?= url('/keranjang') ?>" class="btn btn--ghost">Lihat Keranjang</a>
                    </div>

                    <?php if ($canOrder): ?>
                        <p class="hint" data-builder-hint>Jumlah belum sesuai. Lengkapi komposisi hingga <?= e($packageSize) ?> buah untuk melanjutkan.</p>
                    <?php else: ?>
                        <p class="hint">Pemesanan paket dinonaktifkan sampai harga dan ketentuan pre-order diatur admin.</p>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <form method="post" action="<?= url('/keranjang/tambah') ?>" class="panel" data-unit-price="<?= e((float) ($price ?? 0)) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="product_id" value="<?= e($product['id']) ?>">
                    <span class="panel__label">Jumlah</span>

                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 20px; flex-wrap: wrap;">
                        <div class="qty-stepper" data-qty-stepper>
                            <button type="button" data-step="-1" aria-label="Kurangi jumlah" <?= $stock <= 0 ? 'disabled' : '' ?>>−</button>
                            <input type="number" name="quantity" value="<?= $stock > 0 ? 1 : 0 ?>" min="1" max="<?= e($stock) ?>" data-qty <?= $stock <= 0 ? 'disabled' : '' ?> aria-label="Jumlah">
                            <button type="button" data-step="1" aria-label="Tambah jumlah" <?= $stock <= 0 ? 'disabled' : '' ?>>+</button>
                        </div>
                        <div class="price">
                            Subtotal <span data-line-total><?= e(rupiah($price !== null ? (float) $price : 0)) ?></span>
                        </div>
                    </div>

                    <div class="builder__actions">
                        <button type="submit" class="btn btn--primary" <?= $canOrder ? '' : 'disabled' ?>>
                            Tambahkan ke Keranjang
                        </button>
                        <a href="<?= url('/keranjang') ?>" class="btn btn--ghost">Lihat Keranjang</a>
                    </div>

                    <?php if ($stock <= 0): ?>
                        <p class="hint" style="color: var(--terracotta);">Stok sedang habis. Silakan pilih varian lain atau hubungi kami.</p>
                    <?php endif; ?>
                </form>
            <?php endif; ?>

            <div class="panel" style="background: transparent; border-style: dashed;">
                <span class="panel__label">Informasi pesanan</span>
                <ul class="feature-list" style="margin-top: 0;">
                    <li>
                        <span class="dot">✓</span>
                        <div>
                            <strong>Tanpa akun</strong>
                            <span class="desc">Isi nama dan nomor WhatsApp saat checkout.</span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">✓</span>
                        <div>
                            <strong>Stok dicek ulang saat checkout</strong>
                            <span class="desc">Pesanan ditolak bila stok tidak mencukupi, sehingga tidak terjadi overselling.</span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">✓</span>
                        <div>
                            <strong>Total dihitung oleh sistem</strong>
                            <span class="desc">Harga mengikuti data produk di database, bukan input browser.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php if ($related !== []): ?>
    <section class="section" style="padding-top: 0;">
        <div class="container">
            <div class="section__head">
                <div>
                    <span class="eyebrow">Lainnya</span>
                    <h2 class="section__title" style="font-size: 2rem;">Mungkin Anda juga suka.</h2>
                </div>
                <a href="<?= url('/menu') ?>" class="link-arrow">Semua menu <span aria-hidden="true">→</span></a>
            </div>
            <div class="product-grid">
                <?php foreach (array_slice($related, 0, 4) as $product): ?>
                    <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
