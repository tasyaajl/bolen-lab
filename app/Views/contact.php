<?php
/**
 * @var array<string, string|null> $settings
 */

$waNumber = setting('whatsapp');
$address = setting('address');
$maps = setting('maps_url');
?>

<section class="page-head">
    <div class="container">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?= url('/') ?>">Beranda</a>
            <span class="sep">/</span>
            <span>Kontak</span>
        </nav>
        <span class="eyebrow">Kontak</span>
        <h1>Hubungi BaleBolen</h1>
        <p>Tanyakan ketersediaan, paket hampers, atau status pesanan. Kami membalas melalui WhatsApp.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-card" data-reveal>
                <h2>WhatsApp</h2>
                <?php if ($waNumber): ?>
                    <p>Nomor resmi BaleBolen: <strong><?= e($waNumber) ?></strong></p>
                    <a href="<?= e(whatsapp_link($waNumber, 'Halo BaleBolen, saya ingin bertanya.')) ?>" target="_blank" rel="noopener" class="link-arrow">Buka chat WhatsApp <span aria-hidden="true">→</span></a>
                <?php else: ?>
                    <p class="muted">Nomor WhatsApp belum diisi. Silakan lengkapi melalui dashboard admin.</p>
                <?php endif; ?>
            </div>

            <div class="contact-card" data-reveal>
                <h2>Jam operasional</h2>
                <?php if (setting('opening_hours')): ?>
                    <p><?= nl2br(e(setting('opening_hours'))) ?></p>
                <?php else: ?>
                    <p class="muted">Jam operasional belum diisi. Informasi akan diperbarui oleh pemilik usaha.</p>
                <?php endif; ?>
            </div>

            <div class="contact-card" data-reveal>
                <h2>Alamat usaha</h2>
                <?php if ($address): ?>
                    <p><?= nl2br(e($address)) ?></p>
                    <?php if ($maps): ?>
                        <a href="<?= e($maps) ?>" target="_blank" rel="noopener" class="link-arrow">Buka di Google Maps <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="muted">Alamat usaha belum diisi. Silakan lengkapi melalui dashboard admin.</p>
                <?php endif; ?>
            </div>

            <div class="contact-card" data-reveal>
                <h2>Media sosial</h2>
                <ul class="feature-list" style="margin-top: 0;">
                    <li>
                        <span class="dot">IG</span>
                        <div>
                            <?php if (setting('instagram_url')): ?>
                                <a href="<?= e(setting('instagram_url')) ?>" target="_blank" rel="noopener" class="link-arrow">Instagram</a>
                            <?php else: ?>
                                <span class="desc">Instagram belum diisi.</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li>
                        <span class="dot">TT</span>
                        <div>
                            <?php if (setting('tiktok_url')): ?>
                                <a href="<?= e(setting('tiktok_url')) ?>" target="_blank" rel="noopener" class="link-arrow">TikTok</a>
                            <?php else: ?>
                                <span class="desc">TikTok belum diisi.</span>
                            <?php endif; ?>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="contact-card contact-card--wide" data-reveal>
                <h2>Lokasi</h2>
                <div class="map-embed">
                    <?php if ($maps): ?>
                        <iframe src="<?= e($maps) ?>" loading="lazy" title="Lokasi BaleBolen" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    <?php else: ?>
                        <div>
                            <strong>Peta belum tersedia.</strong><br>
                            Tautan Google Maps dapat ditambahkan admin melalui menu Pengaturan.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>
