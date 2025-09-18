<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use App\Models\Upload;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;

final class SettingsService
{
    public function allKeyed(): array
    {
        return Setting::pluck('value','key')->toArray();
    }

    public function update(array $data, ?UploadedFile $logo = null): void
    {
        if ($logo) {
            $disk = config('filesystems.default', 'public');
            $path = $logo->store('brand', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $logo->getMimeType(),
                'size' => $logo->getSize(),
            ]);
            $this->save('brand.logo_path', $path);
        }

        $this->save('brand.name', $data['brand_name'] ?? null);
        $this->save('payment.tap.public_key', $data['tap_public_key'] ?? null);
        $this->save('payment.tap.secret_key', $data['tap_secret_key'] ?? null);
        $this->save('payment.tap.webhook_secret', $data['tap_webhook_secret'] ?? null);
    }

    private function save(string $key, mixed $value): void
    {
        if ($value === null) return;
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

