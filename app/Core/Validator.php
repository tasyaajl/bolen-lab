<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validator ringan dengan aturan dipisah tanda "|".
 * Contoh: 'required|max:100', 'nullable|in:pickup,delivery'.
 */
final class Validator
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @return array<string, string> pesan error per field
     */
    public static function validate(array $data, array $rules, array $labels = []): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleString) {
            $value = $data[$field] ?? null;
            $value = is_string($value) ? trim($value) : $value;
            $rulesList = explode('|', $ruleString);
            $label = $labels[$field] ?? $field;
            $required = in_array('required', $rulesList, true);

            if (!$required && ($value === null || $value === '')) {
                continue;
            }

            if ($required && ($value === null || $value === '')) {
                $errors[$field] = $label . ' wajib diisi.';
                continue;
            }

            foreach ($rulesList as $rule) {
                if ($rule === '' || $rule === 'required' || $rule === 'nullable') {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $stringValue = is_scalar($value) ? (string) $value : '';

                $message = match ($name) {
                    'max'      => mb_strlen($stringValue) > (int) $parameter
                        ? $label . ' maksimal ' . $parameter . ' karakter.'
                        : null,
                    'min'      => mb_strlen($stringValue) < (int) $parameter
                        ? $label . ' minimal ' . $parameter . ' karakter.'
                        : null,
                    'numeric'  => !is_numeric($stringValue) ? $label . ' harus berupa angka.' : null,
                    'int'      => filter_var($stringValue, FILTER_VALIDATE_INT) === false ? $label . ' harus berupa bilangan bulat.' : null,
                    'email'    => filter_var($stringValue, FILTER_VALIDATE_EMAIL) === false ? $label . ' tidak valid.' : null,
                    'in'       => !in_array($stringValue, explode(',', (string) $parameter), true)
                        ? $label . ' tidak valid.'
                        : null,
                    'date'     => strtotime($stringValue) === false ? $label . ' harus berupa tanggal yang valid.' : null,
                    'phone'    => preg_match('/^(\+62|62|0)[0-9]{8,14}$/', preg_replace('/[\s-]+/', '', $stringValue) ?? '') !== 1
                        ? $label . ' tidak valid (contoh: 081234567890).'
                        : null,
                    'gte'      => is_numeric($stringValue) && (float) $stringValue < (float) $parameter
                        ? $label . ' minimal ' . $parameter . '.'
                        : null,
                    default    => null,
                };

                if ($message !== null) {
                    $errors[$field] = $message;
                    break;
                }
            }
        }

        return $errors;
    }
}
