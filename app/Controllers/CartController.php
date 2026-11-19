<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Controller;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Variant;

final class CartController extends Controller
{
    public function index(): string
    {
        return $this->view('cart', [
            'title'        => 'Keranjang — BaleBolen',
            'lines'        => Cart::detailed(),
            'subtotal'     => Cart::subtotal(),
            'hasHampers'   => Cart::hasHampers(),
            'preorderDays' => setting('hampers_preorder_days'),
        ]);
    }

    public function add(): string
    {
        $this->checkCsrf();

        $productId = (int) ($this->rawInput('product_id') ?? 0);
        $product = Product::find($productId);

        if ($product === null || (int) $product['is_active'] !== 1) {
            $this->flash('error', 'Produk tidak ditemukan atau sudah tidak tersedia.');
            return $this->redirect('/menu');
        }

        $maxQty = max(1, (int) App::configValue('limits.max_item_qty', 50));
        $quantity = max(1, (int) ($this->rawInput('quantity') ?? 1));

        if ($quantity > $maxQty) {
            $this->flash('error', 'Jumlah per produk maksimal ' . $maxQty . ' buah per baris.');
            return $this->redirect('/menu/' . $product['slug']);
        }

        if ((string) $product['type'] === 'hampers') {
            return $this->addHampers($product);
        }

        $stock = Product::availableStock($product);
        if ($stock <= 0) {
            $this->flash('error', 'Stok ' . $product['name'] . ' sedang habis.');
            return $this->redirect('/menu/' . $product['slug']);
        }

        if ($quantity > $stock) {
            $this->flash('error', 'Jumlah melebihi stok tersedia (' . $stock . ' buah).');
            return $this->redirect('/menu/' . $product['slug']);
        }

        Cart::add($productId, $quantity);
        $this->flash('success', $product['name'] . ' ditambahkan ke keranjang.');

        return $this->redirect('/keranjang');
    }

    private function addHampers(array $product): string
    {
        $redirect = '/menu/' . $product['slug'];

        if ($product['price'] === null) {
            $this->flash('error', 'Harga ' . $product['name'] . ' belum diatur admin, paket belum dapat dipesan.');
            return $this->redirect($redirect);
        }

        if (setting('hampers_preorder_days') === null) {
            $this->flash('error', 'Ketentuan pre-order hampers belum diatur admin, paket belum dapat dipesan.');
            return $this->redirect($redirect);
        }

        $submitted = $this->rawInput('variants');
        $composition = [];
        $total = 0;

        if (is_array($submitted)) {
            foreach ($submitted as $variantId => $qty) {
                $variantId = (int) $variantId;
                $qty = (int) $qty;

                if ($qty <= 0) {
                    continue;
                }

                $variant = Variant::find($variantId);

                if ($variant === null || (int) $variant['is_active'] !== 1) {
                    $this->flash('error', 'Varian rasa tidak tersedia.');
                    return $this->redirect($redirect);
                }

                if ($qty > (int) $variant['stock']) {
                    $this->flash('error', 'Stok ' . $variant['name'] . ' hanya ' . (int) $variant['stock'] . ' buah.');
                    return $this->redirect($redirect);
                }

                $composition[] = ['variant_id' => $variantId, 'quantity' => $qty];
                $total += $qty;
            }
        }

        if ($total !== (int) $product['package_size']) {
            $this->flash('error', 'Komposisi ' . $product['name'] . ' harus tepat ' . (int) $product['package_size'] . ' buah (saat ini ' . $total . ').');
            return $this->redirect($redirect);
        }

        Cart::add((int) $product['id'], 1, $composition);
        $this->flash('success', $product['name'] . ' ditambahkan ke keranjang.');

        return $this->redirect('/keranjang');
    }

    public function update(): string
    {
        $this->checkCsrf();

        $key = (string) ($this->rawInput('key') ?? '');
        $line = Cart::findLine($key);

        if ($line === null) {
            $this->flash('error', 'Item keranjang tidak ditemukan.');
            return $this->redirect('/keranjang');
        }

        $product = Product::find((int) $line['product_id']);

        if ($product === null || (int) $product['is_active'] !== 1) {
            $this->flash('error', 'Produk sudah tidak tersedia. Hapus item dari keranjang.');
            return $this->redirect('/keranjang');
        }

        if ((string) $product['type'] === 'hampers') {
            $this->flash('info', 'Jumlah paket hampers diubah lewat halaman detail paket (komposisi rasa harus tetap tepat).');
            return $this->redirect('/menu/' . $product['slug']);
        }

        $maxQty = max(1, (int) App::configValue('limits.max_item_qty', 50));
        $quantity = (int) ($this->rawInput('quantity') ?? 0);
        $stock = Product::availableStock($product);

        if ($quantity < 1) {
            $this->flash('error', 'Jumlah minimal 1 buah.');
            return $this->redirect('/keranjang');
        }

        if ($quantity > $maxQty) {
            $this->flash('error', 'Jumlah per produk maksimal ' . $maxQty . ' buah.');
            return $this->redirect('/keranjang');
        }

        if ($stock <= 0) {
            $this->flash('error', 'Stok ' . $product['name'] . ' sedang habis.');
            return $this->redirect('/keranjang');
        }

        if ($quantity > $stock) {
            $this->flash('error', 'Jumlah melebihi stok tersedia (' . $stock . ' buah).');
            return $this->redirect('/keranjang');
        }

        Cart::updateQuantity($key, $quantity);
        $this->flash('success', 'Jumlah ' . $product['name'] . ' diperbarui.');

        return $this->redirect('/keranjang');
    }

    public function remove(): string
    {
        $this->checkCsrf();

        $key = (string) ($this->rawInput('key') ?? '');
        Cart::remove($key);
        $this->flash('success', 'Item dihapus dari keranjang.');

        return $this->redirect('/keranjang');
    }
}
