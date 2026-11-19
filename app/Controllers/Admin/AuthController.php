<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

final class AuthController extends Controller
{
    public function showLogin(): string
    {
        if (Auth::check()) {
            return $this->redirect('/admin');
        }

        return $this->view('admin/login', [
            'title'  => 'Login Admin — BaleBolen',
            'errors' => Session::pull('_errors', []),
            'old'    => Session::pull('_old', []),
            'locked' => false,
            'lockSeconds' => 0,
        ], 'layouts/auth');
    }

    public function login(): string
    {
        $this->checkCsrf();

        $email = strtolower((string) ($this->input('email') ?? ''));
        $password = (string) ($this->rawInput('password') ?? '');

        $errors = [];
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Masukkan email admin yang valid.';
        }

        if ($password === '') {
            $errors['password'] = 'Password wajib diisi.';
        }

        if ($errors !== []) {
            $this->withErrors($errors, ['email' => $email]);
            return $this->redirect('/admin/login');
        }

        if (Auth::isLocked($email)) {
            $this->flash('error', 'Terlalu banyak percobaan login. Coba lagi dalam ' . (int) ceil(Auth::remainingLockSeconds($email) / 60) . ' menit.');
            return $this->redirect('/admin/login');
        }

        if (!Auth::attempt($email, $password)) {
            $this->withErrors(['email' => 'Email atau password salah.'], ['email' => $email]);
            $this->flash('error', Auth::isLocked($email)
                ? 'Terlalu banyak percobaan login. Coba lagi dalam ' . (int) ceil(Auth::remainingLockSeconds($email) / 60) . ' menit.'
                : 'Login gagal. Periksa kredensial Anda.');
            return $this->redirect('/admin/login');
        }

        $intended = (string) Session::pull('_intended', '/admin');
        if (!str_starts_with($intended, '/admin')) {
            $intended = '/admin';
        }

        $this->flash('success', 'Selamat datang kembali, ' . (Auth::user()['name'] ?? 'Admin') . '.');

        return $this->redirect($intended);
    }

    public function logout(): string
    {
        $this->checkCsrf();
        Auth::logout();
        $this->flash('success', 'Anda telah keluar dari dashboard.');

        return $this->redirect('/admin/login');
    }
}
