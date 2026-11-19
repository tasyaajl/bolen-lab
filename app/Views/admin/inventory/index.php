<?php
/**
 * @var list<array<string, mixed>> $variants
 * @var list<array<string, mixed>> $movements
 */

use App\Core\Session;

$errors = Session::pull('_errors', []);

$reasonLabel = static function (string $reason): string {
    return match ($reason) {
        'restock'    => 'Isi ulang',
        'order'      => 'Pesanan masuk',
        'cancel'     => 'Pembatalan pesanan',
        'adjustment' => 'Penyesuaian',
        default      => $reason,
    };
};
?>

<div class="a-grid a-grid--2">
    <?php foreach ($variants as $variant): ?>
        <?php $stock = (int) $variant['stock']; ?>
        <div class="a-card" style="margin: 0;">
            <div class="a-card__head">
                <div>
                    <h2><?= e($variant['name']) ?></h2>
                    <p><?= (int) $variant['is_active'] === 1 ? 'Aktif' : 'Nonaktif' ?> · dipakai bolen satuan &amp; hampers</p>
                </div>
                <span class="a-badge a-badge--<?= $stock > 0 ? 'success' : 'danger' ?>" style="font-size: 0.8rem;">
                    <?= e($stock) ?> buah
                </span>
            </div>

            <form method="post" action="<?= url('/admin/stok/varian/' . $variant['id']) ?>" class="a-form" style="gap: 12px;">
                <?= csrf_field() ?>
                <div class="a-form a-form--2" style="gap: 10px;">
                    <div class="a-field">
                        <label for="mode-<?= e($variant['id']) ?>">Mode</label>
                        <select id="mode-<?= e($variant['id']) ?>" name="mode">
                            <option value="set">Set stok baru</option>
                            <option value="adjust">Tambah / kurangi</option>
                        </select>
                    </div>
                    <div class="a-field">
                        <label for="amount-<?= e($variant['id']) ?>">Jumlah (buah)</label>
                        <input type="number" id="amount-<?= e($variant['id']) ?>" name="amount" value="0" step="1" required>
                    </div>
                </div>
                <div class="a-field">
                    <label for="note-<?= e($variant['id']) ?>">Catatan</label>
                    <input type="text" id="note-<?= e($variant['id']) ?>" name="note" maxlength="255" placeholder="Contoh: produksi pagi ini">
                </div>
                <button type="submit" class="a-btn a-btn--primary a-btn--small">Perbarui stok</button>
            </form>

            <div class="a-sep"></div>

            <form method="post" action="<?= url('/admin/stok/varian/' . $variant['id'] . '/data') ?>" class="a-form" style="gap: 10px;">
                <?= csrf_field() ?>
                <div class="a-form a-form--2" style="gap: 10px;">
                    <div class="a-field">
                        <label for="name-<?= e($variant['id']) ?>">Nama varian</label>
                        <input type="text" id="name-<?= e($variant['id']) ?>" name="name" value="<?= e($variant['name']) ?>" maxlength="80" required>
                    </div>
                    <div class="a-field">
                        <label>Status</label>
                        <label class="a-check">
                            <input type="checkbox" name="is_active" value="1" <?= (int) $variant['is_active'] === 1 ? 'checked' : '' ?>>
                            Tersedia untuk dipesan
                        </label>
                    </div>
                </div>
                <button type="submit" class="a-btn a-btn--ghost a-btn--small">Simpan varian</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($errors !== []): ?>
    <div class="a-alert a-alert--error" style="margin-top: 18px;"><?= e(reset($errors)) ?></div>
<?php endif; ?>

<div class="a-card" style="margin-top: 18px;">
    <div class="a-card__head">
        <div>
            <h2>Riwayat perubahan stok</h2>
            <p>Delapan puluh perubahan terakhir. Stok awal sengaja 0 dan diisi oleh admin.</p>
        </div>
    </div>

    <?php if ($movements === []): ?>
        <div class="a-empty">Belum ada pergerakan stok.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Varian</th>
                        <th>Perubahan</th>
                        <th>Stok akhir</th>
                        <th>Alasan</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $movement): ?>
                        <tr>
                            <td class="a-tiny a-muted"><?= e(tanggal_id((string) $movement['created_at'], true)) ?></td>
                            <td><?= e($movement['variant_name']) ?></td>
                            <td>
                                <span class="a-badge a-badge--<?= (int) $movement['change_qty'] >= 0 ? 'success' : 'accent' ?>">
                                    <?= (int) $movement['change_qty'] > 0 ? '+' : '' ?><?= e($movement['change_qty']) ?>
                                </span>
                            </td>
                            <td><?= e($movement['stock_after']) ?></td>
                            <td class="a-tiny"><?= e($reasonLabel((string) $movement['reason'])) ?></td>
                            <td class="a-tiny a-muted"><?= e($movement['note'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
