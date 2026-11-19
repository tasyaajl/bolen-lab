<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Varian rasa bolen. Stok dikelola per varian agar pemesanan satuan
 * dan paket hampers memakai sumber stok yang sama.
 */
final class Variant
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function active(): array
    {
        return Database::all(
            'SELECT * FROM product_variants WHERE is_active = 1 ORDER BY sort_order, id'
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::all('SELECT * FROM product_variants ORDER BY sort_order, id');
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM product_variants WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function movements(int $limit = 100): array
    {
        return Database::all(
            'SELECT sm.*, v.name AS variant_name
             FROM stock_movements sm
             JOIN product_variants v ON v.id = sm.variant_id
             ORDER BY sm.created_at DESC, sm.id DESC
             LIMIT ' . max(1, $limit)
        );
    }

    /**
     * Ubah stok dengan alasan tertentu dan catat riwayatnya.
     */
    public static function adjustStock(int $variantId, int $change, string $reason, ?string $note = null, ?int $orderId = null, ?int $adminId = null): int
    {
        Database::run(
            'UPDATE product_variants SET stock = stock + :change WHERE id = :id',
            ['change' => $change, 'id' => $variantId]
        );

        $stockAfter = (int) Database::scalar(
            'SELECT stock FROM product_variants WHERE id = :id',
            ['id' => $variantId]
        );

        Database::insert('stock_movements', [
            'variant_id'  => $variantId,
            'change_qty'  => $change,
            'stock_after' => $stockAfter,
            'reason'      => $reason,
            'note'        => $note,
            'order_id'    => $orderId,
            'admin_id'    => $adminId,
        ]);

        return $stockAfter;
    }

    public static function setStock(int $variantId, int $newStock, ?string $note = null, ?int $adminId = null): void
    {
        $current = self::find($variantId);

        if ($current === null) {
            return;
        }

        $change = $newStock - (int) $current['stock'];

        if ($change === 0) {
            return;
        }

        self::adjustStock($variantId, $change, 'restock', $note, null, $adminId);
    }

    public static function update(int $id, string $name, string $slug, bool $isActive, int $sortOrder): void
    {
        Database::update('product_variants', [
            'name'       => $name,
            'slug'       => $slug,
            'is_active'  => $isActive ? 1 : 0,
            'sort_order' => $sortOrder,
        ], ['id' => $id]);
    }
}
