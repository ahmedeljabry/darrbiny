<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;

class StorageUrl
{
    /**
     * Generate an accessible URL for a stored file.
     *
     * If the disk is private but supports temporary URLs (e.g. local "serve" or S3),
     * a long-lived signed URL is generated to avoid 403 responses.
     */
    public static function make(?string $path, ?string $disk = null, ?DateTimeInterface $expiresAt = null): ?string
    {
        if (!$path) {
            return null;
        }

        $disk ??= config('filesystems.default', 'public');
        $storage = Storage::disk($disk);
        $config = config("filesystems.disks.$disk", []);

        $isPublic = ($config['visibility'] ?? null) === 'public';

        // Use a signed URL when the disk is private but supports temporary URLs.
        if (!$isPublic && method_exists($storage, 'providesTemporaryUrls') && $storage->providesTemporaryUrls()) {
            try {
                return $storage->temporaryUrl($path, $expiresAt ?? now()->addYear());
            } catch (\Throwable) {
                // Fall back to the plain URL if generating a signed URL fails.
            }
        }

        $url = $storage->url($path);

        if ($url && !str_starts_with($url, 'http://') && !str_starts_with($url, 'https://') && !str_starts_with($url, '//')) {
            $baseUrl = rtrim((string) config('app.url'), '/');
            if ($baseUrl !== '') {
                $url = $baseUrl . '/' . ltrim($url, '/');
            }
        }

        return $url;
    }
}
