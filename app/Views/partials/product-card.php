<?php
/**
 * Kartu produk katalog.
 *
 * @var array<string, mixed> $product
 * @var string|null $preorderDays
 */

use App\Models\Product;

$stock = Product::availableStock($product);
$isHampers = $product['type'] === 'hampers';
$price = $product['price'];
$canOrder = $stock > 0 && $price !== null && (!$isHampers || $preorderDays !== null);
$detailUrl = url('/menu/' . $product['slug']);

$stockClass = $stock <= 0 ? 'stock-pill--out' : ($stock <= 5 ? 'stock-pill--low' : '');
$stockLabel = $stock <= 0
    ? 'Stok habis'
    : ($isHampers ? 'Stok tersedia ' . $stock . ' buah' : 'Tersedia ' . $stock . ' buah');
?>
<article class="product-card" data-reveal>
    <a href="<?= e($detailUrl) ?>" class="product-card__media" aria-label="Lihat <?= e($product['name']) ?>">
        <img src="<?= e(Product::imageUrl($product)) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <?php if (empty($product['image'])): ?>
            <span class="product-card__badge">Ilustrasi</span>
        <?php elseif ($isHampers): ?>
            <span class="product-card__badge">Hampers</span>
        <?php endif; ?>
    </a>
    <div class="product-card__body">
        <a href="<?= e($detailUrl) ?>">
            <h3 class="product-card__name"><?= e($product['name']) ?></h3>
        </a>
        <p class="product-card__desc"><?= e($product['description'] ?? '') ?></p>

        <div class="product-card__foot">
            <?php if ($price === null): ?>
                <span class="price--muted">Harga belum ditetapkan</span>
            <?php else: ?>
                <span class="price">
                    <?= e(rupiah($price)) ?>
                    <?php if (!$isHampers): ?><small>/ buah</small><?php endif; ?>
                </span>
            <?php endif; ?>
            <span class="stock-pill <?= e($stockClass) ?>"><?= e($stockLabel) ?></span>
        </div>

        <div class="product-card__actions">
            <a href="<?= e($detailUrl) ?>" class="btn btn--ghost btn--small">Detail</a>
            <?php if ($canOrder): ?>
                <a href="<?= e($detailUrl) ?>" class="btn btn--primary btn--small">Pesan</a>
            <?php else: ?>
                <span class="btn btn--ghost btn--small btn--disabled">Pesan</span>
            <?php endif; ?>
        </div>
    </div>
</article>
