<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use App\Models\Upload;
use App\Support\PaymentGatewayFees;
use App\Support\ReportCurrencyConverter;
use Illuminate\Http\UploadedFile;

final class SettingsService
{
    public function allKeyed(): array
    {
        return Setting::pluck('value', 'key')->toArray();
    }

    public function update(
        array $data,
        ?UploadedFile $logo = null,
        ?UploadedFile $video = null,
        ?UploadedFile $favicon = null,
        ?UploadedFile $captainVideo = null,
    ): void {
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
        $this->save('integrations.hypersend.whatsapp.token', $data['hypersend_whatsapp_token'] ?? null);
        $this->save('integrations.hypersend.whatsapp.instance_id', $data['hypersend_whatsapp_instance_id'] ?? null);
        $this->save('fees.app_fee_percent', $data['app_fee_percent'] ?? null);
        $this->save('fees.reservation_fee_minor', $data['reservation_fee_minor'] ?? null);
        if (array_key_exists('payment_gateway_fees', $data)) {
            $this->save(PaymentGatewayFees::SETTINGS_KEY, PaymentGatewayFees::encode($data['payment_gateway_fees'] ?? []));
        }
        if (array_key_exists('report_exchange_rates', $data)) {
            $this->save(
                ReportCurrencyConverter::SETTINGS_KEY,
                json_encode(
                    $this->normalizeReportExchangeRates($data['report_exchange_rates'] ?? []),
                    JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION
                )
            );
        }

        if (! empty($data['country_fees']) && is_array($data['country_fees'])) {
            foreach ($data['country_fees'] as $countryId => $feeMinor) {
                if ($feeMinor !== null && $feeMinor !== '') {
                    \App\Models\Country::where('id', $countryId)->update([
                        'reservation_fee_minor' => (int) $feeMinor,
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

        $this->save('home.banner.student_text', $data['banner_text_student'] ?? null);
        $this->save('home.banner.trainer_text', $data['banner_text_trainer'] ?? null);

        $this->saveFaqSetting('pages.usage', $data, 'page_usage_faqs', 'page_usage_policy');
        $this->saveFaqSetting('pages.privacy', $data, 'page_privacy_faqs', 'page_privacy_policy');
        $this->saveFaqSetting('pages.terms', $data, 'page_terms_faqs', 'page_terms');
        $this->saveFaqSetting('pages.terms_trainer', $data, 'page_terms_trainer_faqs', 'page_terms_trainer');
        $this->saveFaqSetting('pages.about', $data, 'page_about_faqs', 'page_about');
        $this->saveFaqSetting('pages.sales', $data, 'page_sales_faqs', 'page_sales');
        $this->saveFaqSetting('pages.sales_trainer', $data, 'page_sales_trainer_faqs', 'page_sales_trainer');
        $this->saveFaqSetting('pages.app_usage_trainer', $data, 'page_app_usage_trainer_faqs', 'page_app_usage_trainer');
        $this->saveFaqSetting('pages.app_usage_student', $data, 'page_app_usage_student_faqs', 'page_app_usage_student');
        $this->saveFaqSetting('pages.exchange', $data, 'page_exchange_faqs', 'page_exchange_policy');
        $this->saveFaqSetting('pages.faq', $data, 'faqs');
        $this->save('pages.contact', $data['page_contact'] ?? null);

        if (! empty($data['how_it_works']) && is_array($data['how_it_works'])) {
            $sections = collect($data['how_it_works'])
                ->map(function ($row) {
                    $title = trim((string) ($row['title'] ?? ''));
                    $steps = collect($row['steps'] ?? [])
                        ->map(fn ($s) => trim((string) $s))
                        ->filter()
                        ->values()
                        ->all();
                    if ($title === '' || empty($steps)) {
                        return null;
                    }

                    return ['title' => $title, 'steps' => $steps];
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
        if ($value === null) {
            return;
        }
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    private function saveFaqSetting(string $key, array $data, string $rowsKey, ?string $fallbackTextKey = null): void
    {
        if (array_key_exists($rowsKey, $data)) {
            $rows = is_array($data[$rowsKey] ?? null) ? $data[$rowsKey] : [];
            $faqs = $this->normalizeFaqRows($rows);
            $this->save($key, json_encode($faqs, JSON_UNESCAPED_UNICODE));

            return;
        }

        if ($fallbackTextKey && array_key_exists($fallbackTextKey, $data)) {
            $text = trim((string) ($data[$fallbackTextKey] ?? ''));
            if ($text !== '') {
                $this->save($key, json_encode([['question' => '', 'answer' => $text]], JSON_UNESCAPED_UNICODE));
            }
        }
    }

    private function normalizeFaqRows(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    $answer = trim((string) $row);

                    return $answer === '' ? null : ['question' => '', 'answer' => $answer];
                }
                $q = trim((string) ($row['question'] ?? ''));
                $a = trim((string) ($row['answer'] ?? ''));

                return ($q === '' && $a === '') ? null : ['question' => $q, 'answer' => $a];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeList(?array $items): array
    {
        return collect($items ?? [])
            ->map(static fn ($item) => trim((string) $item))
            ->filter(static fn ($item) => $item !== '')
            ->values()
            ->all();
    }

    private function normalizeReportExchangeRates(?array $rows): array
    {
        return collect($rows ?? [])
            ->mapWithKeys(function ($row): array {
                if (! is_array($row)) {
                    return [];
                }

                $currency = strtoupper(trim((string) ($row['currency'] ?? '')));
                $rate = is_numeric($row['rate'] ?? null) ? round((float) $row['rate'], 6) : null;

                if (
                    ! preg_match('/^[A-Z]{3}$/', $currency)
                    || $currency === ReportCurrencyConverter::REPORT_CURRENCY
                    || $rate === null
                    || $rate <= 0
                ) {
                    return [];
                }

                return [$currency => $rate];
            })
            ->all();
    }
}
