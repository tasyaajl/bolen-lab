<?php

declare(strict_types=1);

/**
 * Ekspor halaman publik menjadi HTML statis untuk pratinjau di Vercel.
 *
 * Pemakaian:
 *   php bin/export-static.php [base-url] [folder-keluaran]
 *
 * Contoh:
 *   php -S 127.0.0.1:8000 -t public public/router.php
 *   php bin/export-static.php http://127.0.0.1:8000 static-preview
 *
 * Catatan: hasilnya hanya pratinjau tampilan. Keranjang, checkout, pesanan,
 * dan admin tetap memerlukan PHP + MySQL dan tidak ikut diekspor.
 */

require dirname(__DIR__) . '/bootstrap/app.php';

use App\Core\Database;

$base = rtrim((string) ($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$outDir = rtrim((string) ($argv[2] ?? dirname(__DIR__) . '/static-preview'), '/');

$blocked = ['/keranjang', '/checkout', '/pesanan', '/admin'];

/**
 * @param array<int, string> $slugs
 * @return array{0: string, 1: bool}
 */
function rewrite_url(string $value, array $slugs, array $blocked): array
{
    $fragment = '';
    $hashPos = strpos($value, '#');
    if ($hashPos !== false) {
        $fragment = substr($value, $hashPos);
        $value = substr($value, 0, $hashPos);
    }

    $query = '';
    $queryPos = strpos($value, '?');
    if ($queryPos !== false) {
        $query = substr($value, $queryPos + 1);
        $value = substr($value, 0, $queryPos);
    }

    if ($value === '/menu' && $query !== '') {
        parse_str($query, $params);
        $kategori = (string) ($params['kategori'] ?? '');
        if ($kategori !== '') {
            return ['/menu/' . $kategori . $fragment, false];
        }
    }

    if (preg_match('~^/menu/([a-z0-9-]+)$~', $value, $match) === 1 && in_array($match[1], $slugs, true)) {
        return ['/produk/' . $match[1] . $fragment, false];
    }

    foreach ($blocked as $prefix) {
        if ($value === $prefix || str_starts_with($value, $prefix . '/')) {
            return ['/kontak', true];
        }
    }

    // Query string tidak bermakna pada salinan statis.
    return [$value . $fragment, false];
}

function fetch_page(string $url): string
{
    $context = stream_context_create([
        'http' => ['ignore_errors' => true, 'timeout' => 20],
    ]);

    $body = @file_get_contents($url, false, $context);

    if ($body === false) {
        fwrite(STDERR, "Gagal mengambil {$url}. Pastikan server lokal berjalan.\n");
        exit(1);
    }

    $status = 0;
    if (isset($http_response_header[0]) && preg_match('~\s(\d{3})\s~', $http_response_header[0], $match) === 1) {
        $status = (int) $match[1];
    }

    if ($status !== 200) {
        fwrite(STDERR, "HTTP {$status} untuk {$url}. Hentikan ekspor.\n");
        exit(1);
    }

    return $body;
}

function copy_tree(string $source, string $destination): void
{
    if (!is_dir($source)) {
        return;
    }

    if (!is_dir($destination) && !mkdir($destination, 0775, true) && !is_dir($destination)) {
        fwrite(STDERR, "Tidak dapat membuat folder {$destination}.\n");
        exit(1);
    }

    foreach (scandir($source) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        $from = $source . '/' . $entry;
        $to = $destination . '/' . $entry;

        if (is_dir($from)) {
            copy_tree($from, $to);
        } elseif (!copy($from, $to)) {
            fwrite(STDERR, "Tidak dapat menyalin {$from}.\n");
            exit(1);
        }
    }
}

function write_page(string $path, string $html): void
{
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        fwrite(STDERR, "Tidak dapat membuat folder {$dir}.\n");
        exit(1);
    }

    if (file_put_contents($path, $html) === false) {
        fwrite(STDERR, "Tidak dapat menulis {$path}.\n");
        exit(1);
    }
}

$productSlugs = array_map(
    static fn (array $row): string => (string) $row['slug'],
    Database::all('SELECT slug FROM products ORDER BY id')
);

$pages = [
    '/' => 'index.html',
    '/menu' => 'menu.html',
    '/menu?kategori=bolen-satuan' => 'menu/bolen-satuan.html',
    '/menu?kategori=paket-hampers' => 'menu/paket-hampers.html',
    '/tentang' => 'tentang.html',
    '/kontak' => 'kontak.html',
];

foreach ($productSlugs as $slug) {
    $pages['/menu/' . $slug] = 'produk/' . $slug . '.html';
}

$banner = <<<'HTML'
<div class="static-preview-bar" role="note">
  <span><strong>Pratinjau statis</strong> — keranjang, checkout, pesanan, dan admin tidak aktif di sini.</span>
  <a href="https://wa.me/6289688067113?text=Halo%20BaleBolen%2C%20saya%20ingin%20memesan." target="_blank" rel="noopener">Pesan via WhatsApp</a>
</div>
<style>
  .static-preview-bar{position:fixed;left:0;right:0;bottom:0;z-index:9999;display:flex;flex-wrap:wrap;gap:8px 16px;align-items:center;justify-content:center;padding:12px 18px;background:#2f2a24;color:#f7f2e8;font:500 14px/1.5 system-ui,-apple-system,"Segoe UI",sans-serif;text-align:center;box-shadow:0 -2px 12px rgba(0,0,0,.18)}
  .static-preview-bar a{color:#f0c98a;text-decoration:underline;font-weight:700}
  .static-preview-bar.is-flash{background:#8a4a2b}
  body{padding-bottom:72px}
</style>
<script>
  (function () {
    function notice() {
      var bar = document.querySelector('.static-preview-bar');
      if (!bar) return;
      bar.classList.add('is-flash');
      window.setTimeout(function () { bar.classList.remove('is-flash'); }, 900);
    }
    document.addEventListener('click', function (event) {
      var link = event.target.closest('a[data-static-block]');
      if (link) { event.preventDefault(); notice(); }
    }, true);
    document.addEventListener('submit', function (event) {
      var form = event.target;
      if (form && form.matches('form[data-static-block]')) { event.preventDefault(); notice(); }
    }, true);
  })();
</script>
HTML;

$count = 0;

foreach ($pages as $url => $relativePath) {
    $html = fetch_page($base . $url);

    $html = str_replace($base, '', $html);

    $html = preg_replace_callback(
        '~(href|action)="(/[^"]*)"~i',
        static function (array $match) use ($productSlugs, $blocked): string {
            [$newValue, $isBlocked] = rewrite_url($match[2], $productSlugs, $blocked);

            return $match[1] . '="' . htmlspecialchars($newValue, ENT_QUOTES) . '"' . ($isBlocked ? ' data-static-block' : '');
        },
        $html
    ) ?? $html;

    // Token CSRF tidak berguna pada salinan statis.
    $html = preg_replace('~<input[^>]*name="_token"[^>]*>\s*~i', '', $html) ?? $html;

    $html = str_replace('</body>', $banner . "\n</body>", $html);

    write_page($outDir . '/' . $relativePath, $html);
    $count++;
}

copy_tree(dirname(__DIR__) . '/public/assets', $outDir . '/assets');
copy_tree(dirname(__DIR__) . '/public/uploads', $outDir . '/uploads');

echo "Ekspor selesai: {$count} halaman -> {$outDir}\n";
echo "Deploy: vercel --prod (outputDirectory diatur di vercel.json)\n";
