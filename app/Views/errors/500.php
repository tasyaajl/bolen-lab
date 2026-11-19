<?php
/**
 * @var string|null $message
 * @var string|null $trace
 */
?>
<section class="section">
    <div class="container" style="max-width: 820px;">
        <span class="eyebrow">500</span>
        <h1 class="section__title">Terjadi kesalahan pada server</h1>
        <p class="section__lead">
            Permintaan Anda tidak dapat diproses. Silakan coba lagi. Bila masalah berlanjut, hubungi admin.
        </p>

        <?php if (!empty($message)): ?>
            <div class="panel">
                <span class="panel__label">Detail (mode debug)</span>
                <p style="font-family: monospace; font-size: 0.85rem; color: var(--terracotta); word-break: break-word;"><?= e($message) ?></p>
                <?php if (!empty($trace)): ?>
                    <pre style="overflow-x: auto; font-size: 0.72rem; line-height: 1.5; margin-top: 16px; color: rgba(45,33,29,0.65);"><?= e($trace) ?></pre>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="hero__actions" style="margin-top: 26px;">
            <a href="<?= url('/') ?>" class="btn btn--primary">Ke Beranda</a>
        </div>
    </div>
</section>
