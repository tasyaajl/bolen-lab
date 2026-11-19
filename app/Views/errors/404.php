<?php
/**
 * @var string|null $message
 */
?>
<section class="section">
    <div class="container center" style="max-width: 620px;">
        <span class="eyebrow" style="justify-content: center;">404</span>
        <h1 class="section__title">Halaman tidak ditemukan</h1>
        <p class="section__lead" style="margin-inline: auto;">
            <?= e($message ?? 'Halaman yang Anda cari mungkin sudah dipindahkan atau tautannya tidak tepat.') ?>
        </p>
        <div class="hero__actions" style="justify-content: center; margin-top: 30px;">
            <a href="<?= url('/') ?>" class="btn btn--primary">Ke Beranda</a>
            <a href="<?= url('/menu') ?>" class="btn btn--ghost">Lihat Menu</a>
        </div>
    </div>
</section>
