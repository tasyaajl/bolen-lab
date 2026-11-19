<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Variant;

final class InventoryController extends Controller
{
    public function index(): string
    {
        Auth::requireLogin();

        return $this->view('admin/inventory/index', [
            'title'     => 'Stok & Varian — Admin BaleBolen',
            'variants'  => Variant::all(),
            'movements' => Variant::movements(80),
        ], 'layouts/admin');
    }

    public function updateStock(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $variant = Variant::find((int) $id);

        if ($variant === null) {
            $this->flash('error', 'Varian tidak ditemukan.');
            return $this->redirect('/admin/stok');
        }

        $mode = (string) ($this->input('mode') ?? 'adjust');
        $amount = $this->input('amount');
        $note = $this->input('note');

        if ($amount === null || !is_numeric($amount)) {
            $this->flash('error', 'Jumlah stok harus berupa angka.');
            return $this->redirect('/admin/stok');
        }

        $amount = (int) $amount;
        $admin = Auth::user();

        if ($mode === 'set') {
            if ($amount < 0) {
                $this->flash('error', 'Stok tidak boleh negatif.');
                return $this->redirect('/admin/stok');
            }

            Variant::setStock((int) $variant['id'], $amount, $note, $admin['id'] ?? null);
        } else {
            if ($amount === 0) {
                $this->flash('error', 'Jumlah penyesuaian tidak boleh 0.');
                return $this->redirect('/admin/stok');
            }

            $newStock = (int) $variant['stock'] + $amount;

            if ($newStock < 0) {
                $this->flash('error', 'Penyesuaian akan membuat stok negatif.');
                return $this->redirect('/admin/stok');
            }

            Variant::adjustStock((int) $variant['id'], $amount, $amount > 0 ? 'restock' : 'adjustment', $note, null, $admin['id'] ?? null);
        }

        $this->flash('success', 'Stok ' . $variant['name'] . ' diperbarui.');

        return $this->redirect('/admin/stok');
    }

    public function updateVariant(string $id): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $variant = Variant::find((int) $id);

        if ($variant === null) {
            $this->flash('error', 'Varian tidak ditemukan.');
            return $this->redirect('/admin/stok');
        }

        $name = (string) ($this->input('name') ?? '');
        $isActive = $this->rawInput('is_active') !== null;

        if ($name === '') {
            $this->flash('error', 'Nama varian wajib diisi.');
            return $this->redirect('/admin/stok');
        }

        Variant::update(
            (int) $variant['id'],
            $name,
            $variant['slug'],
            $isActive,
            (int) $variant['sort_order']
        );

        $this->flash('success', 'Data varian diperbarui.');

        return $this->redirect('/admin/stok');
    }
}
