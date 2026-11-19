<?php
/**
 * @var array<string, string|null> $settings
 */

use App\Models\Product;
use App\Models\Variant;

$brandName = setting('business_name', 'BaleBolen');
$priceFrom = Product::priceFrom('single');
$variants = Variant::active();
$hampers = Product::active('hampers');
$variantNames = implode(', ', array_map(static fn (array $v): string => (string) $v['name'], $variants));
$intro = setting('about_intro');
$story = setting('about_story');
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <span>Tentang</span>
        </nav>
        <span class="eyebrow">Tentang kami</span>
        <h1>BaleBolen</h1>
        <p>Bolen pisang dengan <?= e(count($variants) > 0 ? count($variants) : 'beragam') ?> pilihan rasa, disiapkan untuk dinikmati sendiri maupun sebagai hadiah.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="story">
            <div class="story__media" data-reveal>
                <img src="<?= e(asset('img/placeholder-unit.svg')) ?>" alt="Ilustrasi bolen BaleBolen" loading="lazy">
            </div>
            <div class="story__body" data-reveal>
                <span class="eyebrow">Cerita kami</span>
                <h2 class="section__title" style="font-size: 2.2rem; margin-bottom: 20px;">Bakery rumahan yang tumbuh dari satu resep bolen.</h2>

                <?php if ($intro || $story): ?>
                    <?php if ($intro): ?><p><?= nl2br(e($intro)) ?></p><?php endif; ?>
                    <?php if ($story): ?><p><?= nl2br(e($story)) ?></p><?php endif; ?>
                <?php else: ?>
                    <div class="notice notice--sage" style="margin-top: 0;">
                        <div>
                            <strong>Cerita BaleBolen akan dilengkapi.</strong>
                            Sejarah dan profil usaha sedang disiapkan oleh pemilik usaha. Konten ini
                            dapat diisi melalui dashboard admin tanpa mengubah kode.
                        </div>
                    </div>
                <?php endif; ?>

                <ul class="feature-list">
                    <li>
                        <span class="dot">01</span>
                        <div>
                            <strong><?= e(count($variants)) ?> varian bolen pisang</strong>
                            <span class="desc"><?= $variantNames !== '' ? e($variantNames) : 'Daftar varian sedang dilengkapi admin.' ?></span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">02</span>
                        <div>
                            <strong>Hampers yang bisa disusun sendiri</strong>
                            <span class="desc">
                                <?php if ($hampers !== []): ?>
                                    Pilih kombinasi rasa untuk
                                    <?= e(implode(' atau ', array_map(
                                        static fn (array $p): string => (string) $p['name']
                                            . ($p['package_size'] !== null ? ' (' . (int) $p['package_size'] . ' buah)' : ''),
                                        $hampers
                                    ))) ?>.
                                <?php else: ?>
                                    Paket hampers sedang disiapkan oleh pemilik usaha.
                                <?php endif; ?>
                            </span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">03</span>
                        <div>
                            <strong>Pesan langsung dari website</strong>
                            <span class="desc">Tanpa akun, cukup isi nama dan nomor WhatsApp. Pesanan langsung masuk ke dashboard.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight" style="padding-top: 0;">
    <div class="container">
        <div class="value-grid">
            <div class="value-card" data-reveal>
                <div class="value-card__num">01</div>
                <h3>Satuan</h3>
                <p>
                    <?= $priceFrom !== null
                        ? 'Bolen dijual per buah dengan harga mulai ' . e(rupiah($priceFrom)) . '.'
                        : 'Harga bolen satuan belum ditetapkan admin.' ?>
                    Cocok untuk camilan harian atau oleh-oleh.
                </p>
            </div>
            <div class="value-card" data-reveal>
                <div class="value-card__num">02</div>
                <h3>Hampers</h3>
                <p>Kotak premium dengan pita dan kartu ucapan. Tersedia pilihan isi <?= $hampers !== [] ? e(implode(' atau ', array_map(
                    static fn (array $p): string => (string) ($p['package_size'] ?? '-'),
                    $hampers
                ))) : 'paket' ?> buah.</p>
            </div>
            <div class="value-card" data-reveal>
                <div class="value-card__num">03</div>
                <h3>Pesanan tercatat</h3>
                <p>Setiap pesanan tersimpan dengan detail varian, metode penerimaan, dan status pembayaran.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="cta-band" data-reveal>
            <h2>Mau mencoba bolen kami?</h2>
            <p>Lihat semua varian dan susun pesanan Anda. Untuk pertanyaan, hubungi kami via WhatsApp.</p>
            <div class="hero__actions">
                <a href="<?= url('/menu') ?>" class="btn btn--primary">Lihat Menu</a>
                <a href="<?= url('/kontak') ?>" class="btn btn--ghost">Hubungi Kami</a>
            </div>
        </div>
    </div>
</section>
