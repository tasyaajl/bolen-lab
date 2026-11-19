<?php
/**
 * Layout dashboard admin.
 *
 * @var string $content
 * @var string $title
 * @var array<string, string|null> $settings
 */

use App\Core\Auth;
use App\Models\Order;

$admin = Auth::user();
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$isActive = static function (string $prefix, bool $exact = false) use ($currentPath): string {
    if ($exact) {
        return $currentPath === $prefix ? 'is-active' : '';
    }

    return str_starts_with($currentPath, $prefix) ? 'is-active' : '';
};
$newOrders = (int) \App\Core\Database::scalar("SELECT COUNT(*) FROM orders WHERE order_status = 'new'");
$pageTitle = $title ?? 'Dashboard';
$pageTitle = preg_replace('/ — Admin BaleBolen$/', '', $pageTitle) ?? $pageTitle;
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Admin BaleBolen') ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#4B3028">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,400&family=Playfair+Display:ital,wght@0,500;0,600;0,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    <link rel="icon" href="<?= asset('img/favicon.svg') ?>" type="image/svg+xml">
</head>
<body class="admin">
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>

    <div class="admin-shell">
        <aside class="sidebar" id="adminSidebar">
            <a href="<?= url('/admin') ?>" class="sidebar__brand">
                <span class="brand__mark">
                    <?php if (setting('logo')): ?>
                        <img src="<?= url('/uploads/brand/' . setting('logo')) ?>" alt="">
                    <?php else: ?>
                        <span aria-hidden="true">B</span>
                    <?php endif; ?>
                </span>
                <span class="brand__text">
                    <span class="brand__name"><?= e(setting('business_name', 'BaleBolen')) ?></span>
                    <span class="brand__tag">Admin Panel</span>
                </span>
            </a>

            <div class="sidebar__label">Operasional</div>
            <nav class="sidebar__nav">
                <a href="<?= url('/admin') ?>" class="sidebar__link <?= $isActive('/admin', true) ?>">
                    <svg viewBox="0 0 24 24"><path d="M4 13h6V4H4v9Zm0 7h6v-4H4v4Zm10 0h6V11h-6v9Zm0-16v4h6V4h-6Z"/></svg>
                    Dashboard
                </a>
                <a href="<?= url('/admin/pesanan') ?>" class="sidebar__link <?= $isActive('/admin/pesanan') ?>">
                    <svg viewBox="0 0 24 24"><path d="M6 4h12l1 16H5L6 4Z"/><path d="M9 8h6M9 12h6"/></svg>
                    Pesanan
                    <?php if ($newOrders > 0): ?><span class="count"><?= e($newOrders) ?></span><?php endif; ?>
                </a>
                <a href="<?= url('/admin/stok') ?>" class="sidebar__link <?= $isActive('/admin/stok') ?>">
                    <svg viewBox="0 0 24 24"><path d="M4 8l8-4 8 4-8 4-8-4Z"/><path d="M4 8v8l8 4 8-4V8"/><path d="M12 12v8"/></svg>
                    Stok &amp; Varian
                </a>
            </nav>

            <div class="sidebar__label" style="margin-top: 18px;">Katalog</div>
            <nav class="sidebar__nav">
                <a href="<?= url('/admin/produk') ?>" class="sidebar__link <?= $isActive('/admin/produk') ?>">
                    <svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/></svg>
                    Produk Satuan
                </a>
                <a href="<?= url('/admin/hampers') ?>" class="sidebar__link <?= $isActive('/admin/hampers') ?>">
                    <svg viewBox="0 0 24 24"><path d="M4 9h16v11H4V9Z"/><path d="M4 9l2-4h12l2 4M12 9v11"/><path d="M12 5c-2-3-5-2-5 0s3 2 5 2 5 0 5-2-3-3-5 0Z"/></svg>
                    Paket Hampers
                </a>
            </nav>

            <div class="sidebar__label" style="margin-top: 18px;">Sistem</div>
            <nav class="sidebar__nav">
                <a href="<?= url('/admin/pengaturan') ?>" class="sidebar__link <?= $isActive('/admin/pengaturan') ?>">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 3v2m0 14v2M3 12h2m14 0h2M5.6 5.6l1.4 1.4m10 10 1.4 1.4m0-12.8-1.4 1.4m-10 10-1.4 1.4"/></svg>
                    Pengaturan
                </a>
                <a href="<?= url('/') ?>" target="_blank" rel="noopener" class="sidebar__link">
                    <svg viewBox="0 0 24 24"><path d="M14 4h6v6M20 4l-9 9"/><path d="M19 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>
                    Lihat Website
                </a>
            </nav>

            <div class="sidebar__foot">
                <div class="sidebar__user">
                    <span class="sidebar__avatar"><?= e(strtoupper(substr((string) ($admin['name'] ?? 'A'), 0, 1))) ?></span>
                    <div>
                        <strong><?= e($admin['name'] ?? 'Admin') ?></strong>
                        <span><?= e($admin['email'] ?? '') ?></span>
                    </div>
                </div>
                <form method="post" action="<?= url('/admin/logout') ?>">
                    <?= csrf_field() ?>
                    <button type="submit" class="sidebar__logout">Keluar</button>
                </form>
            </div>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar__left">
                    <button type="button" class="admin-toggle" id="adminToggle" aria-label="Buka menu admin"><span></span></button>
                    <div>
                        <h1><?= e($pageTitle) ?></h1>
                        <div class="admin-topbar__meta"><?= date('d/m/Y') ?> · <?= e(Order::statusLabel('new')) ?>: <?= e($newOrders) ?></div>
                    </div>
                </div>
                <div class="admin-topbar__actions">
                    <a href="<?= url('/admin/pesanan?status=new') ?>" class="a-btn a-btn--ghost a-btn--small">Pesanan baru</a>
                    <a href="<?= url('/menu') ?>" target="_blank" rel="noopener" class="a-btn a-btn--primary a-btn--small">Situs</a>
                </div>
            </header>

            <div class="admin-content" id="main-content" tabindex="-1">
                <?php $flashes = flash(); ?>
                <?php if ($flashes !== []): ?>
                    <div class="admin-flash">
                        <?php foreach ($flashes as $item): ?>
                            <div class="a-alert a-alert--<?= e($item['type'] ?? 'info') ?>"><?= e($item['message'] ?? '') ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?= $content ?>
            </div>
        </div>
    </div>

    <script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
