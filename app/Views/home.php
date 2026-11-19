<?php
/**
 * @var list<array<string, mixed>> $units
 * @var list<array<string, mixed>> $hampers
 * @var list<array<string, mixed>> $variants
 * @var string|null $preorderDays
 * @var array<string, string|null> $settings
 */

use App\Models\Product;

$heroProduct = $units[0] ?? null;
$featured = array_slice($units, 0, 4);
$hampersImage = Product::imageUrl($hampers[0] ?? ['type' => 'hampers']);
$waNumber = setting('whatsapp');

// Semua angka diambil dari database; tidak ada harga/jumlah karangan.
$priceFrom = Product::priceFrom('single');
$priceLabel = $priceFrom !== null ? rupiah($priceFrom) : null;
$variantCount = count($variants);
$variantNames = array_map(static fn (array $variant): string => (string) $variant['name'], $variants);
$hampersCount = count($hampers);
$hampersSizes = array_values(array_filter(array_map(
    static fn (array $package): ?int => $package['package_size'] === null ? null : (int) $package['package_size'],
    $hampers
)));
$hampersSizesText = $hampersSizes === [] ? null : implode(' atau ', array_unique($hampersSizes)) . ' buah';
?>

<section class="hero">
    <div class="container hero__inner">
        <div>
            <span class="eyebrow">BaleBolen · Artisan Bakery</span>
            <h1 class="hero__title">Bolen pisang,<br><em><?= e($variantCount > 0 ? $variantCount : 'beragam') ?> rasa</em> dalam satu meja.</h1>
            <p class="hero__text">
                Pilih bolen satuan untuk dinikmati hari ini, atau susun sendiri paket hampers
                untuk hadiah. Setiap pesanan disiapkan sesuai varian rasa yang Anda pilih.
            </p>

            <div class="hero__actions">
                <a href="<?= url('/menu') ?>" class="btn btn--primary">Lihat Menu</a>
                <a href="<?= url('/menu?kategori=paket-hampers') ?>" class="btn btn--ghost">Pesan Hampers</a>
            </div>

            <div class="hero__meta">
                <div>
                    <strong><?= e($variantCount) ?></strong>
                    <span>Varian rasa</span>
                </div>
                <div>
                    <strong><?= $priceLabel !== null ? e($priceLabel) : 'Belum diatur' ?></strong>
                    <span>Per buah</span>
                </div>
                <div>
                    <strong><?= e($hampersCount) ?></strong>
                    <span>Paket hampers</span>
                </div>
            </div>
        </div>

        <div class="hero__media">
            <div class="hero__ring" aria-hidden="true"></div>
            <div class="hero__frame">
                <img src="<?= e($heroProduct ? Product::imageUrl($heroProduct) : asset('img/placeholder-unit.svg')) ?>" alt="Ilustrasi bolen pisang BaleBolen">
            </div>
            <div class="hero__stamp">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 12h18M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18"/>
                    <circle cx="12" cy="12" r="9"/>
                </svg>
                <div>
                    <strong>Pesan online</strong>
                    <span>Ambil di toko atau dikirim</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section--tight">
    <div class="container">
        <div class="facts" data-reveal>
            <div class="fact">
                <span>Pilihan Rasa</span>
                <strong><?= $variantNames !== [] ? e(implode(' · ', $variantNames)) : 'Belum ada varian aktif' ?></strong>
                <p><?= e($variantCount) ?> varian bolen pisang yang bisa dipilih satu per satu.</p>
            </div>
            <div class="fact">
                <span>Pemesanan</span>
                <strong>Satuan &amp; Paket Hampers</strong>
                <p>Bolen satuan untuk hari ini, hampers untuk hadiah dan momen spesial.</p>
            </div>
            <div class="fact">
                <span>Penerimaan</span>
                <strong>Pickup atau Delivery</strong>
                <p>Ambil langsung di toko atau minta diantar sesuai konfirmasi admin.</p>
            </div>
        </div>
    </div>
</section>

<section class="section" id="kategori">
    <div class="container">
        <div class="section__head" data-reveal>
            <div>
                <span class="eyebrow">Kategori</span>
                <h2 class="section__title">Dua cara menikmati <em>BaleBolen</em>.</h2>
            </div>
            <a href="<?= url('/menu') ?>" class="link-arrow">Lihat semua menu <span aria-hidden="true">→</span></a>
        </div>

        <div class="cat-grid">
            <a href="<?= url('/menu?kategori=bolen-satuan') ?>" class="cat-card" data-reveal>
                <div>
                    <h3>Bolen Satuan</h3>
                    <p><?= e($variantCount) ?> varian rasa, dijual per buah. Cocok untuk camilan atau oleh-oleh.</p>
                    <span class="link-arrow"><?= $priceLabel !== null ? 'Mulai ' . e($priceLabel) . ' / buah' : 'Harga menyusul' ?> <span aria-hidden="true">→</span></span>
                </div>
                <div class="cat-card__media">
                    <img src="<?= e(asset('img/placeholder-unit.svg')) ?>" alt="Ilustrasi bolen satuan" loading="lazy">
                </div>
            </a>

            <a href="<?= url('/menu?kategori=paket-hampers') ?>" class="cat-card cat-card--cocoa" data-reveal>
                <div>
                    <h3>Paket Hampers</h3>
                    <p><?= $hampersSizesText !== null ? 'Susun ' . e($hampersSizesText) . ' sesuai selera' : 'Susun paket sesuai selera' ?>, dikemas dalam kotak premium dengan pita dan kartu ucapan.</p>
                    <span class="link-arrow">Susun paket <span aria-hidden="true">→</span></span>
                </div>
                <div class="cat-card__media">
                    <img src="<?= e($hampersImage) ?>" alt="Ilustrasi paket hampers" loading="lazy">
                </div>
            </a>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="section__head" data-reveal>
            <div>
                <span class="eyebrow">Pilihan rasa</span>
                <h2 class="section__title">Bolen satuan.</h2>
                <p class="section__lead"><?= $priceLabel !== null ? 'Harga per buah ' . e($priceLabel) . '.' : 'Harga per buah belum ditetapkan admin.' ?> Ketersediaan mengikuti stok harian di dapur kami.</p>
            </div>
        </div>

        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php require BASE_PATH . '/app/Views/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--cocoa">
    <div class="container">
        <div class="hampers-promo" data-reveal>
            <div class="hampers-promo__body">
                <span class="eyebrow">Hampers</span>
                <h2>Hadiah yang disusun sendiri,<br>satu rasa demi satu rasa.</h2>
                <p>
                    <?php if ($hampers !== []): ?>
                        <?= e(implode(' dan ', array_map(
                            static fn (array $package): string => (string) $package['name'],
                            array_slice($hampers, 0, 2)
                        ))) ?> siap disusun sesuai selera.
                    <?php else: ?>
                        Paket hampers sedang disiapkan pemilik usaha.
                    <?php endif; ?>
                    Pilih kombinasi rasanya, tuliskan pesan untuk kartu ucapan, lalu tentukan tanggal penerimaan.
                </p>
                <?php if ($preorderDays !== null): ?>
                    <p class="tiny" style="margin-top: 14px; opacity: 0.7;">Pre-order minimal <?= e($preorderDays) ?> hari sebelum tanggal penerimaan.</p>
                <?php else: ?>
                    <p class="tiny" style="margin-top: 14px; opacity: 0.7;">Ketentuan pre-order dan harga paket sedang dilengkapi oleh pemilik usaha.</p>
                <?php endif; ?>
                <a href="<?= url('/menu?kategori=paket-hampers') ?>" class="btn btn--primary">Lihat Paket Hampers</a>
            </div>
            <div class="hampers-promo__media">
                <img src="<?= e($hampersImage) ?>" alt="Ilustrasi kotak hampers dengan pita" loading="lazy">
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="split">
            <div class="split__media split__media--arch" data-reveal>
                <img src="<?= e(asset('img/placeholder-unit.svg')) ?>" alt="Ilustrasi bolen pisang" loading="lazy">
            </div>
            <div data-reveal>
                <span class="eyebrow">Mengapa BaleBolen</span>
                <h2 class="section__title">Pengalaman pesan yang <em>sederhana</em>.</h2>
                <ul class="feature-list">
                    <li>
                        <span class="dot">01</span>
                        <div>
                            <strong>Pilih varian satu per satu</strong>
                            <span class="desc">Tentukan jumlah tiap rasa. Total dihitung otomatis oleh sistem.</span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">02</span>
                        <div>
                            <strong>Stok diperiksa sebelum pesanan masuk</strong>
                            <span class="desc">Produk yang habis tidak dapat dipesan, dan jumlah pesanan dibatasi stok tersedia.</span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">03</span>
                        <div>
                            <strong>Pickup atau delivery</strong>
                            <span class="desc">Pilih ambil sendiri di toko atau pengiriman. Ongkos kirim dikonfirmasi admin.</span>
                        </div>
                    </li>
                    <li>
                        <span class="dot">04</span>
                        <div>
                            <strong>Pembayaran fleksibel</strong>
                            <span class="desc">Transfer bank, QRIS, e-wallet, COD, atau tunai saat pickup.</span>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="section" style="padding-top: 0;">
    <div class="container">
        <div class="cta-band" data-reveal>
            <span class="eyebrow" style="justify-content: center;">Siap memesan?</span>
            <h2>Bolen hangat untuk hari ini,<br>hampers untuk momen spesial.</h2>
            <p>Pesanan Anda langsung tercatat dan diproses oleh BaleBolen. Tidak perlu membuat akun.</p>
            <div class="hero__actions">
                <a href="<?= url('/menu') ?>" class="btn btn--primary">Pesan Sekarang</a>
                <?php if ($waNumber): ?>
                    <a href="<?= e(whatsapp_link($waNumber, 'Halo BaleBolen, saya ingin bertanya.')) ?>" class="btn btn--ghost" target="_blank" rel="noopener">Tanya via WhatsApp</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
