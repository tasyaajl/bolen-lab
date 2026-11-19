<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    /** @var array<string, string|null>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $rows = Database::all('SELECT setting_key, setting_value FROM business_settings');
        $settings = [];

        foreach ($rows as $row) {
            $settings[(string) $row['setting_key']] = $row['setting_value'];
        }

        self::$cache = $settings;

        return $settings;
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $settings = self::all();
        $value = $settings[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function set(string $key, ?string $value): void
    {
        Database::run(
            'INSERT INTO business_settings (setting_key, setting_value, updated_at)
             VALUES (:key, :value, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()',
            ['key' => $key, 'value' => $value]
        );

        self::$cache = null;
    }

    /**
     * @param array<string, string|null> $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }
}
