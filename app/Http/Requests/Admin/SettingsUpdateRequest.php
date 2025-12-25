<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'brand_name' => ['nullable','string','max:120'],
            'logo' => ['nullable','file','image','max:2048'],
            'favicon' => ['nullable','file','mimetypes:image/x-icon,image/png','max:1024'],
            'tap_public_key' => ['nullable','string','max:200'],
            'tap_secret_key' => ['nullable','string','max:200'],
            'tap_webhook_secret' => ['nullable','string','max:200'],
            'video_app_file' => ['nullable','file','mimetypes:video/mp4,video/webm,video/quicktime','max:256000'],
            'video_captain_file' => ['nullable','file','mimetypes:video/mp4,video/webm,video/quicktime','max:256000'],
            'page_usage_policy' => ['nullable','string'],
            'page_privacy_policy' => ['nullable','string'],
            'page_terms' => ['nullable','string'],
            'page_about' => ['nullable','string'],
            'page_sales' => ['nullable','string'],
            'faqs' => ['nullable','array'],
            'faqs.*.question' => ['nullable','string','max:500'],
            'faqs.*.answer' => ['nullable','string'],
            'page_contact' => ['nullable','string'],
            'app_fee_percent' => ['nullable','numeric','min:0','max:100'],
            'reservation_fee_minor' => ['nullable','integer','min:0'],
            'country_fees' => ['nullable','array'],
            'country_fees.*' => ['nullable','integer','min:0'],
            'trainer_roles' => ['nullable','array'],
            'trainer_roles.*' => ['nullable','string','max:255'],
            'trainer_restrictions' => ['nullable','array'],
            'trainer_restrictions.*' => ['nullable','string','max:255'],
            'user_roles' => ['nullable','array'],
            'user_roles.*' => ['nullable','string','max:255'],
            'user_restrictions' => ['nullable','array'],
            'user_restrictions.*' => ['nullable','string','max:255'],
        ];
    }
}
