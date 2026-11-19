<?php
/**
 * @var array<string, mixed> $package
 * @var string|null $preorderDays
 */

use App\Core\Session;

$errors = Session::pull('_errors', []);
$oldInput = Session::pull('_old', []);
$value = static function (string $key, mixed $default = '') use ($oldInput, $package): string {
    if (isset($oldInput[$key])) {
        return (string) $oldInput[$key];
    }

    return (string) ($package[$key] ?? $default);
};
$isActive = $oldInput !== [] ? !empty($oldInput['is_active']) : (int) $package['is_active'] === 1;
?>

<div class="a-row" style="margin-bottom: 16px;">
    <a href="<?= url('/admin/hampers') ?>" class="a-btn a-btn--ghost a-btn--small">← Kembali ke paket hampers</a>
</div>

<form method="post" action="<?= url('/admin/hampers/' . $package['id']) ?>" enctype="multipart/form-data" class="a-card">
    <?= csrf_field() ?>

    <div class="a-card__head">
        <div>
            <h2>Edit <?= e($package['name']) ?></h2>
            <p>Paket ini berisi tepat <?= e($package['package_size']) ?> buah bolen dengan komposisi pilihan pelanggan.</p>
        </div>
    </div>

    <?php if ($errors !== []): ?>
        <div class="a-alert a-alert--error"><?= e(reset($errors)) ?></div>
    <?php endif; ?>

    <div class="a-form a-form--2">
        <div class="a-field full">
            <label for="name">Nama paket <span class="req">*</span></label>
            <input type="text" id="name" name="name" value="<?= e($value('name')) ?>" maxlength="120" required>
            <?php if (isset($errors['name'])): ?><span class="error"><?= e($errors['name']) ?></span><?php endif; ?>
        </div>

        <div class="a-field">
            <label for="price">Harga paket (Rp)</label>
            <input type="number" id="price" name="price" value="<?= e($value('price')) ?>" min="0" step="1000" placeholder="Belum ditetapkan">
            <span class="hint">Kosongkan bila pemilik usaha belum menetapkan harga. Paket tanpa harga tidak dapat dipesan.</span>
            <?php if (isset($errors['price'])): ?><span class="error"><?= e($errors['price']) ?></span><?php endif; ?>
        </div>

        <div class="a-field">
            <label for="package_size">Jumlah isi (buah) <span class="req">*</span></label>
            <input type="number" id="package_size" name="package_size" value="<?= e($value('package_size')) ?>" min="1" max="100" required>
            <?php if (isset($errors['package_size'])): ?><span class="error"><?= e($errors['package_size']) ?></span><?php endif; ?>
            <span class="hint">Sistem memaksa pelanggan memilih komposisi tepat sebanyak angka ini.</span>
        </div>

        <div class="a-field full">
            <label for="description">Deskripsi paket</label>
            <textarea id="description" name="description" maxlength="2000"><?= e($value('description')) ?></textarea>
        </div>

        <div class="a-field full">
            <label for="packaging_info">Informasi kemasan</label>
            <input type="text" id="packaging_info" name="packaging_info" value="<?= e($value('packaging_info')) ?>" maxlength="255" placeholder="Kotak premium, pita, dan kartu ucapan.">
        </div>

        <div class="a-field">
            <label for="image">Foto paket</label>
            <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp">
            <span class="hint">JPG, PNG, atau WEBP. Maksimal 3 MB.</span>
            <?php if (!empty($package['image'])): ?>
                <div style="margin-top: 10px;">
                    <img src="<?= url('/uploads/products/' . $package['image']) ?>" alt="" style="width: 90px; height: 90px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border);">
                </div>
            <?php endif; ?>
        </div>

        <div class="a-field">
            <label for="sort_order">Urutan tampil</label>
            <input type="number" id="sort_order" name="sort_order" value="<?= e($value('sort_order', '0')) ?>" min="0">
            <label class="a-check" style="margin-top: 10px;">
                <input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>>
                Tampilkan paket di website
            </label>
        </div>
    </div>

    <div class="a-note" style="margin-top: 18px;">
        <strong>Pre-order:</strong>
        <?= $preorderDays !== null
            ? 'minimal ' . e($preorderDays) . ' hari sebelum tanggal penerimaan (diatur di halaman Pengaturan).'
            : 'belum diatur, sehingga paket belum dapat dipesan.' ?>
    </div>

    <div class="a-actions">
        <button type="submit" class="a-btn a-btn--primary">Simpan perubahan</button>
        <a href="<?= url('/admin/hampers') ?>" class="a-btn a-btn--ghost">Batal</a>
    </div>
</form>
