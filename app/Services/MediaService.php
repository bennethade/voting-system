<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores uploads on the public disk under random names. Images are decoded
 * and re-encoded with GD, which strips metadata and any payload smuggled
 * inside the file, and downsized to a sensible maximum width.
 *
 * MIME type and size are validated by the Form Request before we get here.
 */
class MediaService
{
    private const MAX_WIDTH = 1600;

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function storeImage(UploadedFile $file, string $directory, ?string $replacing = null): string
    {
        $mime = $file->getMimeType();

        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw new RuntimeException('Unsupported image type.');
        }

        $path = trim($directory, '/').'/'.Str::uuid()->toString().'.'.($mime === 'image/png' ? 'png' : 'jpg');
        $encoded = $this->reencode($file->getRealPath(), $mime);

        Storage::disk('public')->put($path, $encoded ?? $file->get());
        $this->delete($replacing);

        return $path;
    }

    /** Non-image files (e.g. gallery MP4s): stored as-is under a random name. */
    public function storeFile(UploadedFile $file, string $directory, ?string $replacing = null): string
    {
        $extension = $file->guessExtension() ?: 'bin';
        $path = $file->storeAs(trim($directory, '/'), Str::uuid()->toString().'.'.$extension, 'public');
        $this->delete($replacing);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'http')) {
            Storage::disk('public')->delete($path);
        }
    }

    private function reencode(string $source, string $mime): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($source));

        if ($image === false) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        try {
            if (imagesx($image) > self::MAX_WIDTH) {
                $scaled = imagescale($image, self::MAX_WIDTH);
                if ($scaled !== false) {
                    imagedestroy($image);
                    $image = $scaled;
                }
            }

            ob_start();
            if ($mime === 'image/png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                imagepng($image, null, 8);
            } else {
                imagejpeg($image, null, 85);
            }

            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            Log::warning('Image re-encode failed', ['error' => $e->getMessage()]);

            return null;
        } finally {
            imagedestroy($image);
        }
    }
}
