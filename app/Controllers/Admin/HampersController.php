<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageUploader;
use App\Models\Product;
use App\Models\Setting;
use DomainException;

final class HampersController extends Controller
{
    public function index(): string
    {
        Auth::requireLogin();

        $hampers = array_values(array_filter(
            Product::all(),
            static fn (array $product): bool => $product['type'] === 'hampers'
        ));

        return $this->view('admin/hampers/index', [
            'title'        => 'Paket Hampers — Admin BaleBolen',
            'hampers'      => $hampers,
            'preorderDays' => Setting::get('hampers_preorder_days'),
        ], 'layouts/admin');
    }

    public function edit(string $id): string
    {
        Auth::requireLogin();

        $package = Product::find((int) $id);

        if ($package === null || $package['type'] !== 'hampers') {
            $this->flash('error', 'Paket hampers tidak ditemukan.');
            return $this->redirect('/admin/hampers');
        }

        return $this->view('admin/hampers/form', [
            'title'    => 'Edit ' . $package['name'] . ' — Admin BaleBolen',
            'package'  => $package,
            'errors'   => [],
            'old'      => [],
            'preorderDays' => Setting::get('hampers_preorder_days'),
        ], 'layouts/admin');
    }

    public function update(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $package = Product::find((int) $id);

        if ($package === null || $package['type'] !== 'hampers') {
            $this->flash('error', 'Paket hampers tidak ditemukan.');
            return $this->redirect('/admin/hampers');
        }

        $name = (string) ($this->input('name') ?? '');
        $description = (string) ($this->input('description') ?? '');
        $packagingInfo = (string) ($this->input('packaging_info') ?? '');
        $priceRaw = (string) ($this->input('price') ?? '');
        $packageSize = (int) ($this->input('package_size') ?? 0);
        $sortOrder = (int) ($this->input('sort_order') ?? 0);
        $isActive = $this->rawInput('is_active') !== null;

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama paket wajib diisi.';
        }

        if ($packageSize < 1 || $packageSize > 100) {
            $errors['package_size'] = 'Jumlah isi paket harus antara 1 - 100 buah.';
        }

        if ($priceRaw !== '' && (!is_numeric($priceRaw) || (float) $priceRaw <= 0)) {
            $errors['price'] = 'Harga harus berupa angka lebih dari 0, atau dikosongkan bila belum ditetapkan.';
        }

        if ($errors !== []) {
            $this->withErrors($errors, [
                'name'           => $name,
                'description'    => $description,
                'packaging_info' => $packagingInfo,
                'price'          => $priceRaw,
                'package_size'   => (string) $packageSize,
                'sort_order'     => (string) $sortOrder,
                'is_active'      => $isActive ? '1' : '',
            ]);
            $this->flash('error', 'Periksa kembali data paket hampers.');
            return $this->redirect('/admin/hampers/' . $package['id'] . '/edit');
        }

        $data = [
            'name'           => $name,
            'slug'           => Product::uniqueSlug($name, (int) $package['id']),
            'description'    => $description !== '' ? $description : null,
            'packaging_info' => $packagingInfo !== '' ? $packagingInfo : null,
            'package_size'   => $packageSize,
            'price'          => $priceRaw === '' ? null : (float) $priceRaw,
            'is_active'      => $isActive ? 1 : 0,
            'sort_order'     => $sortOrder,
        ];

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

                if (!empty($package['image'])) {
                    ImageUploader::delete((string) App::configValue('uploads.product_path'), (string) $package['image']);
                }
            }

            Product::update((int) $package['id'], $data);
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/admin/hampers/' . $package['id'] . '/edit');
        }

        $this->flash('success', 'Paket ' . $name . ' berhasil diperbarui.');

        return $this->redirect('/admin/hampers');
    }
}
