<?php
/**
 * @var list<array<string, mixed>> $products
 */

use App\Models\Product;
?>

<div class="a-card">
    <div class="a-card__head">
        <div>
            <h2>Produk bolen satuan</h2>
            <p>Empat varian rasa dengan harga per buah. Harga dan ketersediaan dapat diubah kapan saja.</p>
        </div>
        <a href="<?= url('/admin/produk/baru') ?>" class="a-btn a-btn--primary">+ Tambah produk</a>
    </div>

    <?php if ($products === []): ?>
        <div class="a-empty">Belum ada produk. Tambahkan produk pertama Anda.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Varian</th>
                        <th>Harga</th>
                        <th>Stok varian</th>
                        <th>Status</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="table__product">
                                    <div class="table__thumb">
                                        <img src="<?= e(Product::imageUrl($product)) ?>" alt="">
                                    </div>
                                    <div>
                                        <strong><?= e($product['name']) ?></strong>
                                        <span>/menu/<?= e($product['slug']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($product['variant_name'] ?? '-') ?></td>
                            <td class="a-price"><?= $product['price'] !== null ? e(rupiah($product['price'])) : '<span class="a-badge a-badge--warning">Belum diatur</span>' ?></td>
                            <td>
                                <?php $stock = (int) ($product['variant_stock'] ?? 0); ?>
                                <span class="a-badge a-badge--<?= $stock > 0 ? 'success' : 'danger' ?>"><?= e($stock) ?> buah</span>
                            </td>
                            <td>
                                <span class="a-badge a-badge--<?= (int) $product['is_active'] === 1 ? 'success' : 'muted' ?>">
                                    <?= (int) $product['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?>
                                </span>
                            </td>
                            <td>
                                <div class="table__actions">
                                    <a href="<?= url('/admin/produk/' . $product['id'] . '/edit') ?>" class="a-btn a-btn--ghost a-btn--small">Edit</a>
                                    <form method="post" action="<?= url('/admin/produk/' . $product['id'] . '/hapus') ?>" data-confirm="Hapus produk <?= e((string) $product['name']) ?>? Produk yang sudah dipakai pesanan tidak dapat dihapus.">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="a-btn a-btn--danger a-btn--small">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="a-note" style="margin-top: 18px;">
    <strong>Stok dikelola per varian.</strong> Mengubah stok dapat dilakukan di menu
    <a href="<?= url('/admin/stok') ?>" style="color: var(--gold); font-weight: 600;">Stok &amp; Varian</a>,
    sehingga stok untuk bolen satuan dan paket hampers selalu sama.
</div>
