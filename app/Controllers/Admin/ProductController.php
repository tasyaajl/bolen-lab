<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageUploader;
use App\Models\Product;
use App\Models\Variant;
use DomainException;
use RuntimeException;

final class ProductController extends Controller
{
    public function index(): string
    {
        Auth::requireLogin();

        return $this->view('admin/products/index', [
            'title'    => 'Produk Bolen Satuan — Admin BaleBolen',
            'products' => array_values(array_filter(
                Product::all(),
                static fn (array $product): bool => $product['type'] === 'single'
            )),
        ], 'layouts/admin');
    }

    public function create(): string
    {
        Auth::requireLogin();

        return $this->view('admin/products/form', [
            'title'    => 'Tambah Produk — Admin BaleBolen',
            'product'  => null,
            'variants' => Variant::all(),
            'errors'   => [],
            'old'      => [],
        ], 'layouts/admin');
    }

    public function store(): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $data = $this->validated();
        $data['type'] = 'single';
        $data['category_id'] = $this->unitCategoryId();
        $data['slug'] = Product::uniqueSlug((string) $data['name']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        try {
            $data['image'] = ImageUploader::store(
                $_FILES['image'] ?? null,
                (string) App::configValue('uploads.product_path'),
                (int) App::configValue('uploads.max_bytes'),
                (array) App::configValue('uploads.allowed_mime'),
                'JPG, PNG, atau WEBP',
                (int) App::configValue('uploads.image_max_dimension', 1600)
            );

            Product::create($data);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/admin/produk/baru');
        }

        $this->flash('success', 'Produk berhasil ditambahkan.');

        return $this->redirect('/admin/produk');
    }

    public function edit(string $id): string
    {
        Auth::requireLogin();

        $product = Product::find((int) $id);

        if ($product === null) {
            $this->flash('error', 'Produk tidak ditemukan.');
            return $this->redirect('/admin/produk');
        }

        return $this->view('admin/products/form', [
            'title'    => 'Edit ' . $product['name'] . ' — Admin BaleBolen',
            'product'  => $product,
            'variants' => Variant::all(),
            'errors'   => [],
            'old'      => [],
        ], 'layouts/admin');
    }

    public function update(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $product = Product::find((int) $id);

        if ($product === null) {
            $this->flash('error', 'Produk tidak ditemukan.');
            return $this->redirect('/admin/produk');
        }

        $data = $this->validated();
        $data['category_id'] = $this->unitCategoryId();
        $data['slug'] = Product::uniqueSlug((string) $data['name'], (int) $product['id']);

        try {
            $newImage = ImageUploader::store(
                $_FILES['image'] ?? null,
                (string) App::configValue('uploads.product_path'),
                (int) App::configValue('uploads.max_bytes'),
                (array) App::configValue('uploads.allowed_mime'),
                'JPG, PNG, atau WEBP',
                (int) App::configValue('uploads.image_max_dimension', 1600)
            );

            if ($newImage !== null) {
                $data['image'] = $newImage;

                if (!empty($product['image'])) {
                    ImageUploader::delete((string) App::configValue('uploads.product_path'), (string) $product['image']);
                }
            }

            Product::update((int) $product['id'], $data);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/admin/produk/' . $product['id'] . '/edit');
        }

        $this->flash('success', 'Produk berhasil diperbarui.');

        return $this->redirect('/admin/produk');
    }

    public function destroy(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $product = Product::find((int) $id);

        if ($product === null) {
            return $this->redirect('/admin/produk');
        }

        try {
            Product::delete((int) $product['id']);
            ImageUploader::delete((string) App::configValue('uploads.product_path'), $product['image']);
            $this->flash('success', 'Produk dihapus.');
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());
        }

        return $this->redirect('/admin/produk');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(): array
    {
        $name = (string) ($this->input('name') ?? '');
        $description = (string) ($this->input('description') ?? '');
        $price = (string) ($this->input('price') ?? '');
        $variantId = (int) ($this->input('variant_id') ?? 0);
        $sortOrder = (int) ($this->input('sort_order') ?? 0);
        $isActive = $this->rawInput('is_active') !== null;

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama produk wajib diisi.';
        }

        if (Variant::find($variantId) === null) {
            $errors['variant_id'] = 'Pilih varian rasa yang valid.';
        }

        if ($price === '' || !is_numeric($price) || (float) $price <= 0) {
            $errors['price'] = 'Harga wajib diisi berupa angka lebih dari 0 (contoh: 5000).';
        }

        if ($errors !== []) {
            $this->withErrors($errors, [
                'name'        => $name,
                'description' => $description,
                'price'       => $price,
                'variant_id'  => (string) $variantId,
                'sort_order'  => (string) $sortOrder,
                'is_active'   => $isActive ? '1' : '',
            ]);
            $this->flash('error', 'Periksa kembali data produk.');
            $this->back('/admin/produk');
        }

        return [
            'variant_id'  => $variantId,
            'name'        => $name,
            'description' => $description !== '' ? $description : null,
            'price'       => (float) $price,
            'is_active'   => $isActive ? 1 : 0,
            'sort_order'  => $sortOrder,
        ];
    }

    private function unitCategoryId(): int
    {
        $id = Product::categoryId('bolen-satuan');

        if ($id === null) {
            throw new RuntimeException('Kategori bolen-satuan tidak ditemukan. Jalankan ulang seeder.');
        }

        return $id;
    }
}
