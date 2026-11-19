<?php

declare(strict_types=1);

use App\Core\Env;

$root = BASE_PATH;

return [
    'app' => [
        'name'     => Env::get('APP_NAME', 'BaleBolen'),
        'env'      => Env::get('APP_ENV', 'production'),
        'debug'    => Env::bool('APP_DEBUG', false),
        'url'      => rtrim((string) Env::get('APP_URL', 'http://localhost:8000'), '/'),
        'base_path' => '',
        'timezone' => Env::get('APP_TIMEZONE', 'Asia/Jakarta'),
        'force_https' => Env::bool('FORCE_HTTPS', false),
    ],
    'security' => [
        // Idle timeout dashboard admin (menit). 0 = nonaktif.
        'admin_idle_minutes' => Env::int('ADMIN_IDLE_MINUTES', 30),
    ],
    'limits' => [
        // Batas wajar untuk mencegah penyalahgunaan checkout.
        'max_item_qty'        => Env::int('MAX_ITEM_QTY', 50),
        'checkout_per_window' => Env::int('CHECKOUT_PER_10MIN', 5),
        'checkout_window'     => 600,
        'order_access_per_window' => 5,
        'order_access_window'     => 600,
    ],
    'db' => [
        'host'     => Env::get('DB_HOST', '127.0.0.1'),
        'port'     => Env::get('DB_PORT', '3306'),
        'database' => Env::get('DB_DATABASE', 'balebolen'),
        'username' => Env::get('DB_USERNAME', 'root'),
        'password' => Env::get('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],
    'uploads' => [
        'product_path' => $root . '/public/uploads/products',
        'product_url'  => '/uploads/products',
        'brand_path'   => $root . '/public/uploads/brand',
        'brand_url'    => '/uploads/brand',
        'max_bytes'    => Env::int('UPLOAD_MAX_BYTES', 3 * 1024 * 1024),
        'allowed_mime' => [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ],
        // Bukti pembayaran disimpan di storage privat (tidak dapat diakses langsung).
        'proof_path'   => $root . '/storage/proofs',
        'proof_mime'   => [
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
            'application/pdf' => 'pdf',
        ],
        'image_max_dimension' => 1600,
    ],
];
