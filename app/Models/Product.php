<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

final class Product
{
    private const SELECT = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
        v.name AS variant_name, v.stock AS variant_stock, v.is_active AS variant_active
        FROM products p
        JOIN categories c ON c.id = p.category_id
        LEFT JOIN product_variants v ON v.id = p.variant_id';

    /**
     * @return list<array<string, mixed>>
     */
    public static function active(?string $type = null, ?string $categorySlug = null): array
    {
        $sql = self::SELECT . ' WHERE p.is_active = 1';
        $params = [];

        if ($type !== null) {
            $sql .= ' AND p.type = :type';
            $params['type'] = $type;
        }

        if ($categorySlug !== null) {
            $sql .= ' AND c.slug = :slug';
            $params['slug'] = $categorySlug;
        }

        $sql .= ' ORDER BY p.sort_order, p.id';

        return Database::all($sql, $params);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::all(self::SELECT . ' ORDER BY p.type DESC, p.sort_order, p.id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first(self::SELECT . ' WHERE p.id = :id', ['id' => $id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug): ?array
    {
        return Database::first(self::SELECT . ' WHERE p.slug = :slug', ['slug' => $slug]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function categories(): array
    {
        return Database::all('SELECT * FROM categories ORDER BY sort_order, id');
    }

    public static function categoryId(string $slug): ?int
    {
        $id = Database::scalar('SELECT id FROM categories WHERE slug = :slug', ['slug' => $slug]);

        return $id === null ? null : (int) $id;
    }

    /**
     * Stok tersedia untuk sebuah produk.
     * Satuan: stok variannya. Hampers: total stok seluruh varian aktif.
     */
    public static function availableStock(array $product): int
    {
        if (($product['type'] ?? '') === 'single') {
            return (int) ($product['variant_stock'] ?? 0);
        }

        $total = Database::scalar('SELECT COALESCE(SUM(stock), 0) FROM product_variants WHERE is_active = 1');

        return (int) $total;
    }

    public static function isAvailable(array $product): bool
    {
        return (int) ($product['is_active'] ?? 0) === 1 && self::availableStock($product) > 0;
    }

    /**
     * Harga terendah produk aktif yang harganya sudah ditetapkan.
     * Null berarti belum ada harga sama sekali (jangan mengarang angka).
     */
    public static function priceFrom(?string $type = null): ?float
    {
        $sql = 'SELECT MIN(price) FROM products WHERE is_active = 1 AND price IS NOT NULL';
        $params = [];

        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }

        $value = Database::scalar($sql, $params);

        return $value === null ? null : (float) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        return Database::insert('products', $data + [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = now();
        Database::update('products', $data, ['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $used = (int) Database::scalar('SELECT COUNT(*) FROM order_items WHERE product_id = :id', ['id' => $id]);

        if ($used > 0) {
            throw new RuntimeException('Produk sudah dipakai pada pesanan sehingga tidak dapat dihapus. Nonaktifkan produk ini sebagai gantinya.');
        }

        Database::run('DELETE FROM products WHERE id = :id', ['id' => $id]);
    }

    public static function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text === '' ? 'produk' : $text;
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = self::slugify($name);
        $slug = $base;
        $counter = 1;

        while (true) {
            $sql = 'SELECT id FROM products WHERE slug = :slug';
            $params = ['slug' => $slug];

            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }

            if (Database::scalar($sql, $params) === null) {
                return $slug;
            }

            $slug = $base . '-' . ++$counter;
        }
    }

    public static function imageUrl(array $product): string
    {
        if (!empty($product['image'])) {
            return url('/uploads/products/' . $product['image']);
        }

        return asset('img/placeholder-' . (($product['type'] ?? 'single') === 'hampers' ? 'hampers' : 'unit') . '.svg');
    }
}
