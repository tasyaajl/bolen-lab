<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Admin
{
    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT id, name, email FROM admins WHERE id = :id', ['id' => $id]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::first('SELECT * FROM admins WHERE email = :email', ['email' => $email]);
    }

    public static function updatePassword(int $id, string $password): void
    {
        Database::update('admins', [
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ], ['id' => $id]);
    }

    public static function updateProfile(int $id, string $name, string $email, ?string $password = null): void
    {
        $data = ['name' => $name, 'email' => $email];

        if ($password !== null && $password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        Database::update('admins', $data, ['id' => $id]);
    }
}
