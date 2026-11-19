<?php

declare(strict_types=1);

use App\Core\Database;

/**
 * Seeder BaleBolen.
 * Jalankan setelah schema.sql: php database/seed.php
 *
 * Tidak membuat stok fiktif dan tidak menetapkan harga paket hampers.
 * Harga paket, stok, dan informasi bisnis diisi lewat dashboard admin.
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(static function (string $class): void {
    if (strncmp($class, 'App\\', 4) !== 0) {
        return;
    }

    $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/app/Core/Env.php';

App\Core\Env::load(BASE_PATH . '/.env');
$config = require BASE_PATH . '/config/config.php';
date_default_timezone_set($config['app']['timezone']);
App\Core\Database::configure($config['db']);

echo "== Seeder BaleBolen ==\n";

// --- Admin -------------------------------------------------
$adminEmail = App\Core\Env::get('ADMIN_EMAIL', 'admin@balebolen.id');
$adminPassword = App\Core\Env::get('ADMIN_PASSWORD', 'BaleBolen123!');

if (Database::scalar('SELECT id FROM admins LIMIT 1') === null) {
    Database::insert('admins', [
        'name'          => 'Admin BaleBolen',
        'email'         => $adminEmail,
        'password_hash' => password_hash((string) $adminPassword, PASSWORD_DEFAULT),
    ]);
    echo "Admin dibuat: {$adminEmail} / {$adminPassword}\n";
    echo "SEGERA ganti password lewat menu Pengaturan setelah login.\n";
} else {
    echo "Admin sudah ada, dilewati.\n";
}

// --- Pengaturan bisnis -------------------------------------
$settings = [
    'business_name'          => 'BaleBolen',
    'business_tagline'       => null,
    'about_intro'            => null,
    'about_story'            => null,
    'logo'                   => null,
    'whatsapp'               => '6289688067113', // 0896-8806-7113 (dari pemilik usaha)
    'address'                => null,
    'maps_url'               => null,
    'instagram_url'          => 'https://www.instagram.com/balebolen/',
    'tiktok_url'             => null,
    'opening_hours'          => null,
    'pickup_instructions'    => null,
    'bank_name'              => null,
    'bank_number'            => null,
    'bank_holder'            => null,
    'qris_note'              => null,
    'qris_image'             => null,
    'ewallet_name'           => null,
    'ewallet_number'         => null,
    'shipping_area'          => null,
    'shipping_note'          => null,
    'delivery_note'          => null,
    'hampers_preorder_days'  => null,
    'order_note'             => null,
    'payment_due_hours'      => '24',
    'cancellation_policy'    => null,
];

foreach ($settings as $key => $value) {
    Database::run(
        'INSERT IGNORE INTO business_settings (setting_key, setting_value) VALUES (:k, :v)',
        ['k' => $key, 'v' => $value]
    );
}
echo "Pengaturan awal siap (" . count($settings) . " kunci).\n";

// --- Kategori ----------------------------------------------
$categories = [
    ['slug' => 'bolen-satuan', 'name' => 'Bolen Satuan', 'description' => 'Bolen panggang per buah, bebas pilih varian rasa.', 'sort_order' => 1],
    ['slug' => 'paket-hampers', 'name' => 'Paket Hampers', 'description' => 'Paket hadiah berisi bolen pilihan dengan kemasan premium.', 'sort_order' => 2],
];

foreach ($categories as $category) {
    Database::run(
        'INSERT INTO categories (slug, name, description, sort_order)
         VALUES (:slug, :name, :description, :sort_order)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order)',
        $category
    );
}
echo "Kategori siap.\n";

$categoryIds = [];
foreach (Database::all('SELECT id, slug FROM categories') as $row) {
    $categoryIds[(string) $row['slug']] = (int) $row['id'];
}

// --- Varian rasa (stok awal 0, diisi admin) -----------------
$variants = [
    ['slug' => 'pisang-cokelat', 'name' => 'Pisang Cokelat', 'sort_order' => 1],
    ['slug' => 'pisang-keju', 'name' => 'Pisang Keju', 'sort_order' => 2],
    ['slug' => 'pisang-cokelat-keju', 'name' => 'Pisang Cokelat Keju', 'sort_order' => 3],
    ['slug' => 'pisang-butterscotch', 'name' => 'Pisang Butterscotch', 'sort_order' => 4],
];

foreach ($variants as $variant) {
    Database::run(
        'INSERT INTO product_variants (name, slug, stock, is_active, sort_order)
         VALUES (:name, :slug, 0, 1, :sort_order)
         ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)',
        $variant
    );
}
echo "4 varian rasa siap (stok 0 — silakan isi dari dashboard).\n";

$variantIds = [];
foreach (Database::all('SELECT id, slug FROM product_variants') as $row) {
    $variantIds[(string) $row['slug']] = (int) $row['id'];
}

// --- Produk satuan -----------------------------------------
$units = [
    [
        'slug' => 'bolen-pisang-cokelat',
        'name' => 'Bolen Pisang Cokelat',
        'variant' => 'pisang-cokelat',
        'description' => 'Bolen dengan isian pisang dan cokelat, dibalut pastry berlapis lalu dipanggang hingga kuning keemasan.',
        'sort_order' => 1,
    ],
    [
        'slug' => 'bolen-pisang-keju',
        'name' => 'Bolen Pisang Keju',
        'variant' => 'pisang-keju',
        'description' => 'Bolen dengan isian pisang dan keju, dipanggang hingga renyah di luar dan lembut di dalam.',
        'sort_order' => 2,
    ],
    [
        'slug' => 'bolen-pisang-cokelat-keju',
        'name' => 'Bolen Pisang Cokelat Keju',
        'variant' => 'pisang-cokelat-keju',
        'description' => 'Perpaduan isian pisang, cokelat, dan keju dalam satu bolen dengan kulit pastry berlapis.',
        'sort_order' => 3,
    ],
    [
        'slug' => 'bolen-pisang-butterscotch',
        'name' => 'Bolen Pisang Butterscotch',
        'variant' => 'pisang-butterscotch',
        'description' => 'Bolen dengan isian pisang dan saus butterscotch yang manis dan gurih.',
        'sort_order' => 4,
    ],
];

foreach ($units as $unit) {
    Database::run(
        'INSERT INTO products (category_id, variant_id, name, slug, description, type, price, package_size, is_active, sort_order)
         VALUES (:category_id, :variant_id, :name, :slug, :description, "single", 5000, NULL, 1, :sort_order)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description),
             category_id = VALUES(category_id), variant_id = VALUES(variant_id)',
        [
            'category_id' => $categoryIds['bolen-satuan'],
            'variant_id'  => $variantIds[$unit['variant']],
            'name'        => $unit['name'],
            'slug'        => $unit['slug'],
            'description' => $unit['description'],
            'sort_order'  => $unit['sort_order'],
        ]
    );
}
echo "4 produk bolen satuan siap (Rp5.000/buah).\n";

// --- Paket hampers (harga belum ditetapkan) -----------------
$hampers = [
    [
        'slug' => 'paket-spesial',
        'name' => 'Paket Spesial',
        'size' => 6,
        'description' => 'Paket hampers berisi 6 buah bolen dengan kombinasi rasa pilihan Anda. Dikemas dalam kotak premium dengan pita dan kartu ucapan.',
        'sort_order' => 1,
    ],
    [
        'slug' => 'paket-premium',
        'name' => 'Paket Premium',
        'size' => 12,
        'description' => 'Paket hampers berisi 12 buah bolen dengan kombinasi rasa pilihan Anda. Dikemas dalam kotak premium dengan pita dan kartu ucapan.',
        'sort_order' => 2,
    ],
];

foreach ($hampers as $package) {
    Database::run(
        'INSERT INTO products (category_id, variant_id, name, slug, description, type, price, package_size, packaging_info, is_active, sort_order)
         VALUES (:category_id, NULL, :name, :slug, :description, "hampers", NULL, :package_size, :packaging_info, 1, :sort_order)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description),
             package_size = VALUES(package_size), category_id = VALUES(category_id)',
        [
            'category_id'    => $categoryIds['paket-hampers'],
            'name'           => $package['name'],
            'slug'           => $package['slug'],
            'description'    => $package['description'],
            'package_size'   => $package['size'],
            'packaging_info' => 'Kotak premium, pita, dan kartu ucapan.',
            'sort_order'     => $package['sort_order'],
        ]
    );
}
echo "2 paket hampers siap (harga belum diatur — isi lewat dashboard).\n";

echo "\nSelesai. Login admin: /admin/login\n";
