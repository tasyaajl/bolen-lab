<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Session;

/**
 * Keranjang berbasis session. Harga selalu diambil ulang dari database,
 * bukan dari data yang dikirim browser.
 */
final class Cart
{
    private const KEY = 'cart_items';

    /**
     * @return list<array<string, mixed>>
     */
    public static function raw(): array
    {
        $items = Session::get(self::KEY, []);

        return is_array($items) ? array_values($items) : [];
    }

    /**
     * @param list<array{key: string, product_id: int, quantity: int, composition: list<array{variant_id: int, quantity: int}>}>|null $items
     */
    private static function persist(array $items): void
    {
        Session::put(self::KEY, array_values($items));
    }

    /**
     * @param list<array{variant_id: int, quantity: int}> $composition
     */
    public static function add(int $productId, int $quantity, array $composition = []): string
    {
        $items = self::raw();
        $key = bin2hex(random_bytes(6));

        $composition = array_values(array_filter($composition, static fn (array $row): bool =>
            (int) ($row['quantity'] ?? 0) > 0
        ));

        $items[] = [
            'key'         => $key,
            'product_id'  => $productId,
            'quantity'    => max(1, $quantity),
            'composition' => $composition,
        ];

        self::persist($items);

        return $key;
    }

    public static function updateQuantity(string $key, int $quantity): void
    {
        $items = self::raw();

        foreach ($items as &$item) {
            if ((string) ($item['key'] ?? '') === $key) {
                $item['quantity'] = max(1, $quantity);
                break;
            }
        }

        unset($item);
        self::persist($items);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findLine(string $key): ?array
    {
        foreach (self::raw() as $item) {
            if ((string) ($item['key'] ?? '') === $key) {
                return $item;
            }
        }

        return null;
    }

    public static function remove(string $key): void
    {
        $items = array_filter(self::raw(), static fn (array $item): bool => ($item['key'] ?? '') !== $key);
        self::persist($items);
    }

    public static function clear(): void
    {
        Session::forget(self::KEY);
    }

    public static function count(): int
    {
        $total = 0;
        foreach (self::raw() as $item) {
            $total += (int) ($item['quantity'] ?? 0);
        }

        return $total;
    }

    /**
     * Baris keranjang yang sudah dilengkapi data produk dari database.
     *
     * @return list<array<string, mixed>>
     */
    public static function detailed(): array
    {
        $lines = [];
        $variants = [];
        foreach (Variant::all() as $variant) {
            $variants[(int) $variant['id']] = $variant;
        }

        foreach (self::raw() as $item) {
            $product = Product::find((int) $item['product_id']);

            if ($product === null) {
                continue;
            }

            $composition = [];
            foreach ($item['composition'] as $row) {
                $variant = $variants[(int) $row['variant_id']] ?? null;

                if ($variant === null) {
                    continue;
                }

                $composition[] = [
                    'variant_id' => (int) $variant['id'],
                    'name'       => (string) $variant['name'],
                    'quantity'   => (int) $row['quantity'],
                ];
            }

            $price = $product['price'] === null ? null : (float) $product['price'];
            $quantity = (int) $item['quantity'];

            $lines[] = [
                'key'         => (string) $item['key'],
                'product'     => $product,
                'quantity'    => $quantity,
                'composition' => $composition,
                'unit_price'  => $price,
                'line_total'  => $price === null ? null : (int) round($price * $quantity),
            ];
        }

        return $lines;
    }

    public static function subtotal(): int
    {
        $total = 0;

        foreach (self::detailed() as $line) {
            $total += (int) ($line['line_total'] ?? 0);
        }

        return $total;
    }

    /**
     * Apakah keranjang berisi paket hampers (butuh pre-order).
     */
    public static function hasHampers(): bool
    {
        foreach (self::detailed() as $line) {
            if (($line['product']['type'] ?? '') === 'hampers') {
                return true;
            }
        }

        return false;
    }

    public static function hasUnpricedItem(): bool
    {
        foreach (self::detailed() as $line) {
            if ($line['unit_price'] === null) {
                return true;
            }
        }

        return false;
    }
}
