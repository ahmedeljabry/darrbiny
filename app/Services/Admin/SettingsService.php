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

    public function update(array $data, ?UploadedFile $logo = null, ?UploadedFile $video = null, ?UploadedFile $favicon = null): void
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

        if ($favicon) {
            $disk = config('filesystems.default', 'public');
            $path = $favicon->store('brand', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $favicon->getMimeType(),
                'size' => $favicon->getSize(),
            ]);
            $this->save('brand.favicon_path', $path);
        }

        $this->save('brand.name', $data['brand_name'] ?? null);
        $this->save('payment.tap.public_key', $data['tap_public_key'] ?? null);
        $this->save('payment.tap.secret_key', $data['tap_secret_key'] ?? null);
        $this->save('payment.tap.webhook_secret', $data['tap_webhook_secret'] ?? null);
        if ($video) {
            $disk = config('filesystems.default', 'public');
            $path = $video->store('videos', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $video->getMimeType(),
                'size' => $video->getSize(),
            ]);
            $this->save('video.app.path', $path);
        }

        // Pages content
        $this->save('pages.usage', $data['page_usage_policy'] ?? null);
        $this->save('pages.privacy', $data['page_privacy_policy'] ?? null);
        if (!empty($data['faqs']) && is_array($data['faqs'])) {
            $faqs = collect($data['faqs'])
                ->map(function ($row) {
                    $q = trim((string)($row['question'] ?? ''));
                    $a = trim((string)($row['answer'] ?? ''));
                    return ($q === '' && $a === '') ? null : ['question' => $q, 'answer' => $a];
                })
                ->filter()
                ->values()
                ->all();
            $this->save('pages.faq', json_encode($faqs, JSON_UNESCAPED_UNICODE));
        }
        $this->save('pages.contact', $data['page_contact'] ?? null);

        // Home: How it works (sections with steps)
        if (!empty($data['how_it_works']) && is_array($data['how_it_works'])) {
            $sections = collect($data['how_it_works'])
                ->map(function ($row) {
                    $title = trim((string)($row['title'] ?? ''));
                    $steps = collect($row['steps'] ?? [])
                        ->map(fn($s) => trim((string)$s))
                        ->filter()
                        ->values()
                        ->all();
                    if ($title === '' || empty($steps)) return null;
                    return [ 'title' => $title, 'steps' => $steps ];
                })
                ->filter()
                ->values()
                ->all();
            $this->save('home.how_it_works', json_encode($sections, JSON_UNESCAPED_UNICODE));
        }
    }

    private function save(string $key, mixed $value): void
    {
        if ($value === null) return;
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
