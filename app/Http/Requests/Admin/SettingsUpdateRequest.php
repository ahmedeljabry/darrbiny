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
            'tap_public_key' => ['nullable','string','max:200'],
            'tap_secret_key' => ['nullable','string','max:200'],
            'tap_webhook_secret' => ['nullable','string','max:200'],
        ];
    }
}

