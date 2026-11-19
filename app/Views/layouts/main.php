<?php
/**
 * Layout utama halaman pelanggan.
 *
 * @var string $content
 * @var string $title
 * @var array<string, string|null> $settings
 */

use App\Models\Cart;
use App\Models\Product;

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isNavActive = static function (string $prefix) use ($currentPath): string {
    if ($prefix === '/') {
        return $currentPath === '/' ? 'is-active' : '';
    }

    return str_starts_with($currentPath, $prefix) ? 'is-active' : '';
};

$cartCount = Cart::count();
$brandName = setting('business_name', 'BaleBolen');
$brandTagline = setting('business_tagline', 'Artisan Bakery');
$waNumber = setting('whatsapp');
$logo = setting('logo');
$preorderDays = setting('hampers_preorder_days');
$priceFrom = Product::priceFrom('single');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? $brandName) ?></title>
    <meta name="description" content="<?= e($brandName) ?> — bolen pisang artisan. Pesan bolen satuan atau paket hampers secara online.">
    <meta name="theme-color" content="#4B3028">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="site">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <div class="topbar">
        <?php if ($priceFrom !== null): ?>
            Bolen satuan <strong><?= e(rupiah($priceFrom)) ?></strong> per buah
        <?php else: ?>
            Bolen satuan · harga menyesuaikan produk aktif
        <?php endif; ?>
        <?php if ($preorderDays !== null): ?>
            &nbsp;·&nbsp; Pre-order hampers minimal <strong><?= e($preorderDays) ?> hari</strong>
        <?php else: ?>
            &nbsp;·&nbsp; Paket hampers tersedia untuk pre-order
        <?php endif; ?>
    </div>

    <header class="nav">
        <div class="container nav__inner">
            <a href="<?= url('/') ?>" class="brand" aria-label="<?= e($brandName) ?> — Beranda">
                <span class="brand__mark">
                    <?php if ($logo): ?>
                        <img src="<?= url('/uploads/brand/' . $logo) ?>" alt="Logo <?= e($brandName) ?>">
                    <?php else: ?>
                        <span aria-hidden="true">B</span>
                    <?php endif; ?>
                </span>
                <span class="brand__text">
                    <span class="brand__name"><?= e($brandName) ?></span>
                    <span class="brand__tag"><?= e($brandTagline ?? '') ?></span>
                </span>
            </a>

            <nav class="nav__links" id="navLinks" aria-label="Navigasi utama">
                <a class="nav__link <?= $isNavActive('/') ?>" href="<?= url('/') ?>">Beranda</a>
                <a class="nav__link <?= $isNavActive('/menu') ?>" href="<?= url('/menu') ?>">Menu</a>
                <a class="nav__link" href="<?= url('/menu?kategori=paket-hampers') ?>">Hampers</a>
                <a class="nav__link <?= $isNavActive('/tentang') ?>" href="<?= url('/tentang') ?>">Tentang</a>
                <a class="nav__link <?= $isNavActive('/kontak') ?>" href="<?= url('/kontak') ?>">Kontak</a>
            </nav>

            <div class="nav__actions">
                <a href="<?= url('/keranjang') ?>" class="cart-link" aria-label="Keranjang belanja">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 7h12l-1.2 9.2a2 2 0 0 1-2 1.8H9.2a2 2 0 0 1-2-1.8L6 7Z"/>
                        <path d="M9 7V5.8A3 3 0 0 1 12 3a3 3 0 0 1 3 2.8V7"/>
                    </svg>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count"><?= e($cartCount) ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= url('/menu') ?>" class="btn btn--dark btn--small nav__cta">Pesan Sekarang</a>
                <button type="button" class="nav__toggle" id="navToggle" aria-label="Buka menu" aria-expanded="false">
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <?php $flashes = flash(); ?>
    <?php if ($flashes !== []): ?>
        <div class="flash-area" role="status" aria-live="polite">
            <?php foreach ($flashes as $item): ?>
                <div class="flash flash--<?= e($item['type'] ?? 'info') ?>" data-flash>
                    <?= e($item['message'] ?? '') ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main id="main-content" tabindex="-1">
        <?= $content ?>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer__grid">
                <div>
                    <a href="<?= url('/') ?>" class="brand">
                        <span class="brand__mark">
                            <?php if ($logo): ?>
                                <img src="<?= url('/uploads/brand/' . $logo) ?>" alt="">
                            <?php else: ?>
                                <span aria-hidden="true">B</span>
                            <?php endif; ?>
                        </span>
                        <span class="brand__text">
                            <span class="brand__name"><?= e($brandName) ?></span>
                            <span class="brand__tag"><?= e($brandTagline ?? '') ?></span>
                        </span>
                    </a>
                    <p class="footer__about">
                        <?= e(setting('about_intro', 'Bolen pisang dengan empat pilihan rasa. Pesan satuan untuk dinikmati hari ini, atau susun paket hampers untuk hadiah.')) ?>
                    </p>
                </div>

                <div>
                    <h4>Jelajahi</h4>
                    <ul>
                        <li><a href="<?= url('/menu') ?>">Semua menu</a></li>
                        <li><a href="<?= url('/menu?kategori=bolen-satuan') ?>">Bolen satuan</a></li>
                        <li><a href="<?= url('/menu?kategori=paket-hampers') ?>">Paket hampers</a></li>
                        <li><a href="<?= url('/tentang') ?>">Tentang kami</a></li>
                    </ul>
                </div>

                <div>
                    <h4>Kontak</h4>
                    <ul>
                        <li>
                            <?php if ($waNumber): ?>
                                <a href="<?= e(whatsapp_link($waNumber)) ?>" target="_blank" rel="noopener">WhatsApp <?= e($waNumber) ?></a>
                            <?php else: ?>
                                <span class="footer__placeholder">Nomor WhatsApp belum diisi</span>
                            <?php endif; ?>
                        </li>
                        <li>
                            <?php if (setting('address')): ?>
                                <?= e(setting('address')) ?>
                            <?php else: ?>
                                <span class="footer__placeholder">Alamat usaha belum diisi</span>
                            <?php endif; ?>
                        </li>
                        <li>
                            <?php if (setting('opening_hours')): ?>
                                <?= e(setting('opening_hours')) ?>
                            <?php else: ?>
                                <span class="footer__placeholder">Jam operasional belum diisi</span>
                            <?php endif; ?>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4>Ikuti Kami</h4>
                    <ul>
                        <?php if (setting('instagram_url')): ?>
                            <li><a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener">Instagram</a></li>
                        <?php else: ?>
                            <li><span class="footer__placeholder">Instagram belum diisi</span></li>
                        <?php endif; ?>
                        <?php if (setting('tiktok_url')): ?>
                            <li><a href="<?= e(setting('tiktok_url')) ?>" target="_blank" rel="noopener">TikTok</a></li>
                        <?php else: ?>
                            <li><span class="footer__placeholder">TikTok belum diisi</span></li>
                        <?php endif; ?>
                        <li><a href="<?= url('/kontak') ?>">Hubungi &amp; kunjungi</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer__bottom">
                <span>&copy; <?= date('Y') ?> <?= e($brandName) ?>. Semua hak dilindungi.</span>
                <span>
                    <a href="<?= url('/admin/login') ?>">Dashboard Admin</a>
                </span>
            </div>
        </div>
    </footer>

    <?php if ($waNumber): ?>
        <a class="wa-float" href="<?= e(whatsapp_link($waNumber, 'Halo ' . $brandName . ', saya ingin bertanya tentang produk.')) ?>" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5 14.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.95 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.76-1.66-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.08 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.13-.27-.2-.57-.35ZM12.04 21.5h-.01a9.4 9.4 0 0 1-4.79-1.31l-.34-.2-3.56.93.95-3.47-.22-.36a9.38 9.38 0 0 1-1.44-5.02c0-5.19 4.23-9.41 9.42-9.41 2.52 0 4.88.98 6.66 2.76a9.35 9.35 0 0 1 2.76 6.66c0 5.19-4.23 9.42-9.42 9.42Zm8.02-17.44A10.9 10.9 0 0 0 12.04 1C5.97 1 1.03 5.94 1.03 12.01c0 1.94.5 3.83 1.47 5.5L1 23l5.63-1.48a10.99 10.99 0 0 0 5.41 1.42h.01c6.06 0 11-4.94 11-11 0-2.94-1.15-5.7-3.19-7.78Z"/></svg>
        </a>
    <?php endif; ?>

    <script src="<?= asset('js/app.js') ?>" defer></script>
</body>
</html>
