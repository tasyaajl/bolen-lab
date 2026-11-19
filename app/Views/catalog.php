<?php
/**
 * @var list<array<string, mixed>> $units
 * @var list<array<string, mixed>> $hampers
 * @var string|null $filter
 * @var string|null $preorderDays
 */

use App\Models\Product;
use App\Models\Variant;

$priceFrom = Product::priceFrom('single');
$variantCount = count(Variant::active());
$hampersSizes = array_values(array_unique(array_filter(array_map(
    static fn (array $package): ?int => $package['package_size'] === null ? null : (int) $package['package_size'],
    $hampers
))));
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <span>Menu</span>
        </nav>
        <span class="eyebrow">Katalog</span>
        <h1>Menu BaleBolen</h1>
        <p>Bolen satuan dijual per buah, sedangkan paket hampers disusun sesuai selera dan dipesan lebih awal.</p>

        <div style="margin-top: 26px;">
            <div class="filter-tabs">
                <a href="<?= url('/menu') ?>" class="<?= $filter === null ? 'is-active' : '' ?>">Semua</a>
                <a href="<?= url('/menu?kategori=bolen-satuan') ?>" class="<?= $filter === 'bolen-satuan' ? 'is-active' : '' ?>">Bolen Satuan</a>
                <a href="<?= url('/menu?kategori=paket-hampers') ?>" class="<?= $filter === 'paket-hampers' ? 'is-active' : '' ?>">Paket Hampers</a>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if ($filter === null || $filter === 'bolen-satuan'): ?>
            <div class="catalog-section" id="bolen-satuan">
                <div class="catalog-section__head" data-reveal>
                    <div>
                        <h2>Bolen Satuan</h2>
                        <p>
                            <?= e($variantCount) ?> varian rasa,
                            <?= $priceFrom !== null ? 'harga mulai ' . e(rupiah($priceFrom)) . ' per buah.' : 'harga per buah belum ditetapkan admin.' ?>
                            Pesan hari ini selama stok tersedia.
                        </p>
                    </div>
                    <span class="badge badge--gold">Same-day order</span>
                </div>

                <?php if ($units === []): ?>
                    <div class="notice">Belum ada produk satuan yang aktif.</div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($units as $product): ?>
                            <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($filter === null || $filter === 'paket-hampers'): ?>
            <div class="catalog-section" id="paket-hampers">
                <div class="catalog-section__head" data-reveal>
                    <div>
                        <h2>Paket Hampers</h2>
                        <p>
                            <?php if ($hampers !== []): ?>
                                <?= e(implode(' dan ', array_map(
                                    static fn (array $package): string => (string) $package['name']
                                        . ($package['package_size'] !== null ? ' ' . (int) $package['package_size'] . ' buah' : ''),
                                    $hampers
                                ))) ?>, dengan kombinasi rasa pilihan Anda.
                            <?php else: ?>
                                Paket hampers akan ditampilkan setelah diaktifkan admin.
                            <?php endif; ?>
                        </p>
                    </div>
                    <span class="badge badge--accent">Pre-order</span>
                </div>

                <?php if ($preorderDays === null): ?>
                    <div class="notice">
                        <div>
                            <strong>Pre-order hampers belum diaktifkan.</strong>
                            Ketentuan minimal hari pre-order dan harga paket belum ditetapkan pemilik usaha.
                            Silakan hubungi kami untuk informasi paket.
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($hampers === []): ?>
                    <div class="notice">Belum ada paket hampers yang aktif.</div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($hampers as $product): ?>
                            <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
