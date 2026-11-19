<?php
/**
 * Layout halaman login admin.
 *
 * @var string $content
 * @var string $title
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Login Admin') ?></title>
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
    <div class="auth-wrap">
        <div style="width: 100%; max-width: 420px;">
            <?php $flashes = flash(); ?>
            <?php if ($flashes !== []): ?>
                <div style="position: relative; margin-bottom: -34px; top: -34px;">
                    <?php foreach ($flashes as $item): ?>
                        <div class="a-alert a-alert--<?= e($item['type'] ?? 'info') ?>"><?= e($item['message'] ?? '') ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?= $content ?>

            <p class="auth-foot">
                <a href="<?= url('/') ?>">← Kembali ke website BaleBolen</a>
            </p>
        </div>
    </div>
</body>
</html>
