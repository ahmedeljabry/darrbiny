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

    public function update(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $video = null,
        ?UploadedFile $favicon = null,
        ?UploadedFile $captainVideo = null,
    ): void
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
        $this->save('fees.app_fee_percent', $data['app_fee_percent'] ?? null);
        $this->save('fees.reservation_fee_minor', $data['reservation_fee_minor'] ?? null);
        
        if (!empty($data['country_fees']) && is_array($data['country_fees'])) {
            foreach ($data['country_fees'] as $countryId => $feeMinor) {
                if ($feeMinor !== null && $feeMinor !== '') {
                    \App\Models\Country::where('id', $countryId)->update([
                        'reservation_fee_minor' => (int) $feeMinor
                    ]);
                }
            }
        }
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

        if ($captainVideo) {
            $disk = config('filesystems.default', 'public');
            $path = $captainVideo->store('videos', $disk);
            Upload::create([
                'disk' => $disk,
                'path' => $path,
                'mime' => $captainVideo->getMimeType(),
                'size' => $captainVideo->getSize(),
            ]);
            $this->save('video.captain.path', $path);
        }

        $this->save('pages.usage', $data['page_usage_policy'] ?? null);
        $this->save('pages.privacy', $data['page_privacy_policy'] ?? null);
        $this->save('pages.terms', $data['page_terms'] ?? null);
        $this->save('pages.about', $data['page_about'] ?? null);
        $this->save('pages.sales', $data['page_sales'] ?? null);
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

        if (array_key_exists('trainer_roles', $data)) {
            $this->save('roles.trainer', json_encode($this->normalizeList($data['trainer_roles'] ?? []), JSON_UNESCAPED_UNICODE));
        }
        if (array_key_exists('trainer_restrictions', $data)) {
            $this->save('restrictions.trainer', json_encode($this->normalizeList($data['trainer_restrictions'] ?? []), JSON_UNESCAPED_UNICODE));
        }
        if (array_key_exists('user_roles', $data)) {
            $this->save('roles.user', json_encode($this->normalizeList($data['user_roles'] ?? []), JSON_UNESCAPED_UNICODE));
        }
        if (array_key_exists('user_restrictions', $data)) {
            $this->save('restrictions.user', json_encode($this->normalizeList($data['user_restrictions'] ?? []), JSON_UNESCAPED_UNICODE));
        }
    }

    private function save(string $key, mixed $value): void
    {
        if ($value === null) return;
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function normalizeList(?array $items): array
    {
        return collect($items ?? [])
            ->map(static fn ($item) => trim((string) $item))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();
    }
}
