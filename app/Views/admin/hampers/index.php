<?php
/**
 * @var list<array<string, mixed>> $hampers
 * @var string|null $preorderDays
 */

use App\Models\Product;
?>

<?php if ($preorderDays === null): ?>
    <div class="a-alert a-alert--info" style="display: flex; justify-content: space-between; gap: 16px; align-items: center; flex-wrap: wrap;">
        <span>
            <strong>Ketentuan pre-order belum diatur.</strong>
            Paket hampers tidak dapat dipesan sampai minimal hari pre-order ditetapkan.
        </span>
        <a href="<?= url('/admin/pengaturan#preorder') ?>" class="a-btn a-btn--dark a-btn--small">Atur sekarang</a>
    </div>
<?php endif; ?>

<div class="a-card">
    <div class="a-card__head">
        <div>
            <h2>Paket hampers</h2>
            <p>Harga, jumlah isi, dan kemasan paket. Harga kosong berarti paket belum dapat dipesan.</p>
        </div>
        <div class="a-row">
            <span class="a-badge a-badge--<?= $preorderDays !== null ? 'success' : 'warning' ?>">
                Pre-order: <?= $preorderDays !== null ? e($preorderDays) . ' hari' : 'belum diatur' ?>
            </span>
            <a href="<?= url('/admin/pengaturan#preorder') ?>" class="a-btn a-btn--ghost a-btn--small">Ubah pre-order</a>
        </div>
    </div>

    <?php if ($hampers === []): ?>
        <div class="a-empty">Belum ada paket hampers. Jalankan seeder atau tambahkan paket.</div>
    <?php else: ?>
        <div class="a-grid a-grid--2">
            <?php foreach ($hampers as $package): ?>
                <div class="a-card" style="margin: 0; background: #FFFBF5;">
                    <div class="a-row" style="align-items: flex-start; gap: 16px; flex-wrap: nowrap;">
                        <div class="table__thumb" style="width: 84px; height: 84px;">
                            <img src="<?= e(Product::imageUrl($package)) ?>" alt="">
                        </div>
                        <div style="flex: 1;">
                            <h3 style="font-size: 1.15rem;"><?= e($package['name']) ?></h3>
                            <p class="a-tiny a-muted">Isi <?= e($package['package_size']) ?> buah · <?= (int) $package['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></p>
                            <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                <?php if ($package['price'] === null): ?>
                                    <span class="a-badge a-badge--warning">Harga belum ditetapkan</span>
                                <?php else: ?>
                                    <span class="a-price"><?= e(rupiah($package['price'])) ?></span>
                                <?php endif; ?>
                                <span class="a-badge a-badge--muted"><?= e($package['packaging_info'] ?? 'Kemasan belum diisi') ?></span>
                            </div>
                        </div>
                    </div>
                    <p class="a-tiny a-muted" style="margin-top: 14px;"><?= e($package['description'] ?? '') ?></p>
                    <div class="a-actions">
                        <a href="<?= url('/admin/hampers/' . $package['id'] . '/edit') ?>" class="a-btn a-btn--dark a-btn--small">Edit paket</a>
                        <a href="<?= url('/menu/' . $package['slug']) ?>" target="_blank" rel="noopener" class="a-btn a-btn--ghost a-btn--small">Lihat di website</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<div class="a-note" style="margin-top: 18px;">
    <strong>Komposisi rasa diatur pelanggan.</strong> Admin hanya menetapkan jumlah isi paket dan harga.
    Sistem akan menolak pesanan bila total komposisi belum tepat sesuai jumlah isi, atau bila stok varian tidak cukup.
</div>
