<?php
/**
 * @var string|null $message
 */
?>
<section class="section">
    <div class="container center" style="max-width: 620px;">
        <span class="eyebrow" style="justify-content: center;">Sesi kedaluwarsa</span>
        <h1 class="section__title">Formulir perlu dimuat ulang</h1>
        <p class="section__lead" style="margin-inline: auto;">
            Token keamanan formulir tidak valid atau sudah kedaluwarsa. Muat ulang halaman lalu kirim kembali.
        </p>
        <div class="hero__actions" style="justify-content: center; margin-top: 30px;">
            <a href="<?= url('/') ?>" class="btn btn--primary">Ke Beranda</a>
            <a href="javascript:history.back()" class="btn btn--ghost">Kembali</a>
        </div>
    </div>
</section>
