<?php
/**
 * @var string $code
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 */
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <a href="<?= url('/menu') ?>">Menu</a>
            <span class="sep">/</span>
            <span>Pesanan</span>
        </nav>
        <span class="eyebrow">Verifikasi</span>
        <h1>Buka pesanan <?= e($code) ?></h1>
        <p>Demi keamanan data, masukkan empat digit terakhir nomor WhatsApp yang Anda gunakan saat memesan.</p>
    </div>
</section>

<div class="container" style="max-width: 520px; padding-bottom: 60px;">
    <div class="form-card">
        <?php if (isset($errors['last4'])): ?>
            <div class="alert alert--error"><?= e($errors['last4']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= url('/pesanan/' . $code . '/akses') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="code" value="<?= e($code) ?>">

            <div class="field">
                <label class="label" for="last4">4 digit terakhir nomor WhatsApp <span class="req">*</span></label>
                <input class="input <?= isset($errors['last4']) ? 'has-error' : '' ?>"
                       type="text" id="last4" name="last4" inputmode="numeric" pattern="[0-9]{4}"
                       maxlength="4" autocomplete="off" required
                       value="<?= e($old['last4'] ?? '') ?>" placeholder="Contoh: 7890">
                <span class="hint">Contoh: bila nomor Anda 081234567890, masukkan 7890.</span>
            </div>

            <button type="submit" class="btn btn--primary btn--block" style="margin-top: 18px;">Buka pesanan</button>
        </form>

        <p class="tiny muted" style="margin-top: 16px;">
            Tidak ingat nomornya? Hubungi BaleBolen via WhatsApp dengan menyebutkan kode pesanan
            <strong><?= e($code) ?></strong>.
        </p>
        <a href="<?= url('/kontak') ?>" class="link-arrow" style="margin-top: 12px; font-size: 0.85rem;">Halaman kontak →</a>
    </div>
</div>
