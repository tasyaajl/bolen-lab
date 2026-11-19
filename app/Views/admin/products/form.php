<?php
/**
 * @var array<string, mixed>|null $product
 * @var list<array<string, mixed>> $variants
 */

use App\Core\Session;

$errors = Session::pull('_errors', []);
$oldInput = Session::pull('_old', []);
$isEdit = $product !== null;
$action = $isEdit ? url('/admin/produk/' . $product['id']) : url('/admin/produk');
$value = static function (string $key, mixed $default = '') use ($oldInput, $product): string {
    if (isset($oldInput[$key])) {
        return (string) $oldInput[$key];
    }

    if ($product !== null && array_key_exists($key, $product)) {
        return (string) ($product[$key] ?? '');
    }

    return (string) $default;
};
$isActive = $oldInput !== [] ? !empty($oldInput['is_active']) : ($isEdit ? (int) $product['is_active'] === 1 : true);
?>

<div class="a-row" style="margin-bottom: 16px;">
    <a href="<?= url('/admin/produk') ?>" class="a-btn a-btn--ghost a-btn--small">← Kembali ke produk</a>
</div>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="a-card">
    <?= csrf_field() ?>

    <div class="a-card__head">
        <div>
            <h2><?= $isEdit ? 'Edit produk' : 'Tambah produk' ?></h2>
            <p>Produk satuan dikaitkan ke satu varian rasa, sehingga stoknya mengikuti varian tersebut.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="a-alert a-alert--error">
            <?= e(reset($errors)) ?>
        </div>
    <?php endif; ?>

    <div class="a-form a-form--2">
        <div class="a-field full">
            <label for="name">Nama produk <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($value('name')) ?>" maxlength="120" required placeholder="Bolen Pisang Cokelat">
            <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <div class="a-field">
            <label for="variant_id">Varian rasa &amp; stok <span class="req">*</span></label>
            <select id="variant_id" name="variant_id" required>
                <option value="">Pilih varian</option>
                <?php foreach ($variants as $variant): ?>
                    <option value="<?= e($variant['id']) ?>" <?= $value('variant_id') === (string) $variant['id'] ? 'selected' : '' ?>>
                        <?= e($variant['name']) ?> — stok <?= e($variant['stock']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['variant_id'])): ?><span class="error"><?= e($errors['variant_id']) ?></span><?php endif; ?>
        </div>

        <div class="a-field">
            <label for="price">Harga per buah (Rp) <span class="req">*</span></label>
            <input type="number" id="price" name="price" value="<?= e($value('price')) ?>" min="0" step="500" required placeholder="5000">
            <?php if (isset($errors['price'])): ?><span class="error"><?= e($errors['price']) ?></span><?php endif; ?>
        </div>

        <div class="a-field full">
            <label for="description">Deskripsi</label>
            <textarea id="description" name="description" maxlength="2000" placeholder="Jelaskan isian dan karakter produk."><?= e($value('description')) ?></textarea>
        </div>

        <div class="a-field">
            <label for="image">Foto produk</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint">JPG, PNG, atau WEBP. Maksimal 3 MB. Kosongkan bila tidak ingin mengubah foto.</span>
            <?php if ($isEdit && !empty($product['image'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?= url('/uploads/products/' . $product['image']) ?>" alt="" style="width: 90px; height: 90px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border);">
                </div>
            <?php elseif ($isEdit): ?>
                <span class="hint">Saat ini memakai ilustrasi placeholder.</span>
            <?php endif; ?>
        </div>

        <div class="a-field">
            <label for="sort_order">Urutan tampil</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= e($value('sort_order', '0')) ?>" min="0">
            <label class="a-check" style="margin-top: 10px;">
                <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                Tampilkan produk di website
            </label>
        </div>
    </div>

    <div class="a-actions">
        <button type="submit" class="a-btn a-btn--primary"><?= $isEdit ? 'Simpan perubahan' : 'Tambah produk' ?></button>
        <a href="<?= url('/admin/produk') ?>" class="a-btn a-btn--ghost">Batal</a>
    </div>
</form>
