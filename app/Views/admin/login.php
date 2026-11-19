<?php
/**
 * @var array<string, string> $errors
 * @var array<string, mixed> $old
 * @var bool $locked
 * @var int $lockSeconds
 */
?>
<div class="auth-card">
    <div class="auth-card__brand">
        <span class="brand__mark"><span aria-hidden="true">B</span></span>
        <h1>Admin BaleBolen</h1>
        <p>Masuk untuk mengelola pesanan, produk, dan stok.</p>
    </div>

    <?php if ($locked): ?>
        <div class="a-alert a-alert--error">
            Terlalu banyak percobaan login. Coba lagi dalam <?= (int) ceil($lockSeconds / 60) ?> menit.
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url('/admin/login') ?>">
        <?= csrf_field() ?>

        <div class="a-field">
            <label for="email">Email admin</label>
            <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="username" required>
            <?php if (isset($errors['email'])): ?><span class="error"><?= e($errors['email']) ?></span><?php endif; ?>
        </div>

        <div class="a-field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <?php if (isset($errors['password'])): ?><span class="error"><?= e($errors['password']) ?></span><?php endif; ?>
        </div>

        <button type="submit" class="a-btn a-btn--primary a-btn--block" <?= $locked ? 'disabled' : '' ?>>Masuk Dashboard</button>
    </form>
</div>
