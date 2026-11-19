<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\App;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\ImageUploader;
use App\Models\Admin;
use App\Models\Setting;
use DomainException;

final class SettingsController extends Controller
{
    private const TEXT_FIELDS = [
        'business_name', 'business_tagline', 'about_intro', 'about_story',
        'whatsapp', 'address', 'maps_url', 'opening_hours',
        'instagram_url', 'tiktok_url', 'pickup_instructions', 'order_note',
        'bank_name', 'bank_number', 'bank_holder', 'qris_note',
        'ewallet_name', 'ewallet_number',
        'shipping_area', 'shipping_note', 'delivery_note', 'cancellation_policy',
    ];

    public function index(): string
    {
        Auth::requireLogin();

        return $this->view('admin/settings', [
            'title'   => 'Pengaturan — Admin BaleBolen',
            'admin'   => Auth::user(),
            'errors'  => [],
            'old'     => [],
        ], 'layouts/admin');
    }

    public function save(): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $businessName = (string) ($this->input('business_name') ?? '');
        $whatsapp = (string) ($this->input('whatsapp') ?? '');
        $preorder = (string) ($this->input('hampers_preorder_days') ?? '');
        $paymentDueHours = (string) ($this->input('payment_due_hours') ?? '');

        $errors = [];

        if ($businessName === '') {
            $errors['business_name'] = 'Nama bisnis wajib diisi.';
        }

        if ($whatsapp !== '' && preg_match('/^(\+62|62|0)[0-9]{8,14}$/', preg_replace('/[\s-]+/', '', $whatsapp) ?? '') !== 1) {
            $errors['whatsapp'] = 'Nomor WhatsApp tidak valid (contoh: 081234567890).';
        }

        if ($preorder !== '' && (!ctype_digit($preorder) || (int) $preorder > 90)) {
            $errors['hampers_preorder_days'] = 'Minimal pre-order harus angka 0 - 90 hari.';
        }

        if ($paymentDueHours !== '' && (!ctype_digit($paymentDueHours) || (int) $paymentDueHours > 336)) {
            $errors['payment_due_hours'] = 'Batas waktu pembayaran harus angka 0 - 336 jam (0 = tanpa batas).';
        }

        if ($errors !== []) {
            $this->flash('error', 'Periksa kembali pengaturan yang diisi.');
            // Simpan input sementara untuk ditampilkan ulang.
            $old = [];
            foreach (self::TEXT_FIELDS as $field) {
                $old[$field] = (string) ($this->input($field) ?? '');
            }
            $old['hampers_preorder_days'] = $preorder;
            $old['payment_due_hours'] = $paymentDueHours;
            $this->withErrors($errors, $old);

            return $this->redirect('/admin/pengaturan');
        }

        foreach (self::TEXT_FIELDS as $field) {
            Setting::set($field, $this->input($field));
        }

        Setting::set('hampers_preorder_days', $preorder === '' ? null : (string) (int) $preorder);
        Setting::set('payment_due_hours', $paymentDueHours === '' ? null : (string) (int) $paymentDueHours);
        Setting::set('whatsapp', $whatsapp === '' ? null : normalize_phone($whatsapp));

        try {
            $logo = ImageUploader::store(
                $_FILES['logo'] ?? null,
                (string) App::configValue('uploads.brand_path'),
                (int) App::configValue('uploads.max_bytes'),
                (array) App::configValue('uploads.allowed_mime'),
                'JPG, PNG, atau WEBP',
                (int) App::configValue('uploads.image_max_dimension', 1600)
            );

            if ($logo !== null) {
                ImageUploader::delete((string) App::configValue('uploads.brand_path'), Setting::get('logo'));
                Setting::set('logo', $logo);
            }

            $qrisImage = ImageUploader::store(
                $_FILES['qris_image'] ?? null,
                (string) App::configValue('uploads.brand_path'),
                (int) App::configValue('uploads.max_bytes'),
                (array) App::configValue('uploads.allowed_mime'),
                'JPG, PNG, atau WEBP',
                (int) App::configValue('uploads.image_max_dimension', 1600)
            );

            if ($qrisImage !== null) {
                ImageUploader::delete((string) App::configValue('uploads.brand_path'), Setting::get('qris_image'));
                Setting::set('qris_image', $qrisImage);
            }
        } catch (DomainException $e) {
            $this->flash('error', $e->getMessage());
            return $this->redirect('/admin/pengaturan');
        }

        $this->flash('success', 'Pengaturan berhasil disimpan.');

        return $this->redirect('/admin/pengaturan');
    }

    public function updateProfile(): string
    {
        Auth::requireLogin();
        $this->checkCsrf();

        $user = Auth::user();
        $name = (string) ($this->input('name') ?? '');
        $email = strtolower((string) ($this->input('email') ?? ''));
        $currentPassword = (string) ($this->rawInput('current_password') ?? '');
        $newPassword = (string) ($this->rawInput('new_password') ?? '');

        $errors = [];

        if ($name === '') {
            $errors['name'] = 'Nama admin wajib diisi.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email tidak valid.';
        }

        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors['new_password'] = 'Password baru minimal 8 karakter.';
        }

        if ($newPassword !== '') {
            $existing = Admin::findByEmail($user['email'] ?? '');

            if ($existing === null || !password_verify($currentPassword, $existing['password_hash'])) {
                $errors['current_password'] = 'Password saat ini salah.';
            }
        }

        if ($errors !== []) {
            $this->withErrors($errors);
            $this->flash('error', 'Periksa kembali data profil admin.');
            return $this->redirect('/admin/pengaturan#profil');
        }

        Admin::updateProfile((int) $user['id'], $name, $email, $newPassword !== '' ? $newPassword : null);

        $this->flash('success', 'Profil admin diperbarui.');

        return $this->redirect('/admin/pengaturan#profil');
    }
}
