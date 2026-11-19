<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Product;
use App\Models\Variant;

final class CatalogController extends Controller
{
    public function index(): string
    {
        $filter = $this->input('kategori');

        if ($filter !== null && !in_array($filter, ['bolen-satuan', 'paket-hampers'], true)) {
            $filter = null;
        }

        return $this->view('catalog', [
            'title'        => 'Menu BaleBolen',
            'units'        => Product::active('single'),
            'hampers'      => Product::active('hampers'),
            'filter'       => $filter,
            'preorderDays' => setting('hampers_preorder_days'),
        ]);
    }

    public function show(string $slug): string
    {
        $product = Product::findBySlug($slug);

        if ($product === null || (int) $product['is_active'] !== 1) {
            http_response_code(404);

            return $this->view('errors/404', ['title' => 'Produk Tidak Ditemukan']);
        }

        $variants = Variant::active();

        return $this->view('product', [
            'title'        => $product['name'] . ' — BaleBolen',
            'product'      => $product,
            'variants'     => $variants,
            'related'      => array_values(array_filter(
                Product::active($product['type']),
                static fn (array $item): bool => (int) $item['id'] !== (int) $product['id']
            )),
            'preorderDays' => setting('hampers_preorder_days'),
            'stock'        => Product::availableStock($product),
        ]);
    }
}
