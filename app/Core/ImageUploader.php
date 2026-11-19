<?php

declare(strict_types=1);

namespace App\Core;

use DomainException;

/**
 * Validasi dan simpan upload gambar produk/logo, serta berkas bukti bayar.
 */
final class ImageUploader
{
    /**
     * @param array<string, mixed>|null $file entry dari $_FILES
     * @param array<string, string> $allowedMime mime => ekstensi
     */
    public static function store(
        ?array $file,
        string $directory,
        int $maxBytes,
        array $allowedMime,
        ?string $allowedLabel = null,
        int $maxDimension = 0
    ): ?string {
        if ($file === null || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new DomainException('Upload gagal (kode ' . $file['error'] . '). Coba lagi.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new DomainException('Berkas upload tidak valid.');
        }

        if ((int) $file['size'] > $maxBytes) {
            throw new DomainException('Ukuran berkas maksimal ' . round($maxBytes / 1048576, 1) . ' MB.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);

        if ($mime === false || !isset($allowedMime[$mime])) {
            throw new DomainException(
                'Format berkas tidak didukung. Gunakan ' . ($allowedLabel ?? 'JPG, PNG, atau WEBP') . '.'
            );
        }

        $extension = $allowedMime[$mime];
        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new DomainException('Folder upload tidak dapat dibuat.');
        }

        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $filename)) {
            throw new DomainException('Gagal menyimpan berkas.');
        }

        if (str_starts_with($mime, 'image/')) {
            self::optimizeImage($directory . '/' . $filename, $mime, $maxDimension);
        }

        return $filename;
    }

    /**
     * Perkecil gambar ke batas dimensi dan simpan ulang dengan kompresi.
     * Bila GD tidak tersedia atau proses gagal, file asli tetap dipakai.
     */
    private static function optimizeImage(string $path, string $mime, int $maxDimension): void
    {
        if ($maxDimension <= 0 || !function_exists('imagecreatetruecolor')) {
            return;
        }

        $info = @getimagesize($path);

        if ($info === false) {
            return;
        }

        [$width, $height] = $info;

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };

        if ($source === false || $source === null) {
            return;
        }

        $ratio = $maxDimension / max($width, $height);
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $target = imagecreatetruecolor($newWidth, $newHeight);

        if ($mime !== 'image/jpeg') {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
            imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        match ($mime) {
            'image/jpeg' => @imagejpeg($target, $path, 82),
            'image/png'  => @imagepng($target, $path, 7),
            'image/webp' => @imagewebp($target, $path, 82),
            default      => null,
        };

        imagedestroy($source);
        imagedestroy($target);
    }

    public static function delete(string $directory, ?string $filename): void
    {
        if ($filename === null || $filename === '') {
            return;
        }

        $path = $directory . '/' . basename($filename);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function path(string $directory, ?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $path = $directory . '/' . basename($filename);

        return is_file($path) ? $path : null;
    }
}
