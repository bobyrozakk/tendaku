<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Perkecil & kompres foto yang diunggah vendor sebelum disimpan.
 *
 * Foto kamera ponsel (3–8 MB, 4000px+) sebelumnya disimpan apa adanya, lalu dikirim penuh ke
 * browser customer untuk thumbnail katalog. Di sini sisi terpanjang dibatasi dan JPEG/WebP
 * dikompres ulang. Jika GD tidak tersedia / gagal, file asli disimpan seperti semula.
 */
class ImageOptimizer
{
    public const MAX_DIMENSION = 1600;

    private const QUALITY = 82;

    /**
     * Simpan file ke disk dan kembalikan path relatifnya (sama seperti UploadedFile::store()).
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'public'): string
    {
        $optimized = $this->optimize($file);

        if ($optimized === null) {
            return (string) $file->store($directory, $disk);
        }

        [$binary, $extension] = $optimized;
        $path = trim($directory, '/').'/'.Str::random(40).'.'.$extension;

        if (! Storage::disk($disk)->put($path, $binary)) {
            return (string) $file->store($directory, $disk);
        }

        return $path;
    }

    /**
     * @return array{0: string, 1: string}|null [biner gambar, ekstensi] atau null jika tidak perlu/tidak bisa dioptimasi
     */
    private function optimize(UploadedFile $file): ?array
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $mime = (string) $file->getMimeType();
        $realPath = $file->getRealPath();

        if ($realPath === false) {
            return null;
        }

        $loader = match ($mime) {
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png' => 'imagecreatefrompng',
            'image/webp' => 'imagecreatefromwebp',
            default => null,
        };

        if ($loader === null || ! function_exists($loader)) {
            return null;
        }

        try {
            $info = @getimagesize($realPath);
            if ($info === false) {
                return null;
            }

            [$width, $height] = $info;

            $source = @$loader($realPath);
            if ($source === false) {
                return null;
            }

            if ($mime === 'image/jpeg') {
                $source = $this->applyExifOrientation($source, $realPath);
                $width = imagesx($source);
                $height = imagesy($source);
            }

            $scale = min(1, self::MAX_DIMENSION / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            if ($scale < 1) {
                $resized = imagecreatetruecolor($targetWidth, $targetHeight);
                if ($mime !== 'image/jpeg') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                imagecopyresampled($resized, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
                $source = $resized;
            }

            ob_start();
            $ok = match ($mime) {
                'image/png' => imagepng($source, null, 7),
                'image/webp' => function_exists('imagewebp') && imagewebp($source, null, self::QUALITY),
                default => imagejpeg($source, null, self::QUALITY),
            };
            $binary = (string) ob_get_clean();

            // Jangan menyimpan hasil yang lebih besar dari file asli.
            if (! $ok || $binary === '' || ($scale >= 1 && strlen($binary) >= (int) $file->getSize())) {
                return null;
            }

            $extension = match ($mime) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };

            return [$binary, $extension];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * Putar gambar sesuai EXIF Orientation, karena re-encode membuang metadata EXIF.
     */
    private function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);

        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated !== false ? $rotated : $image;
    }
}
