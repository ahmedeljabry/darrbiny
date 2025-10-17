<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlanUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['sometimes','string','max:120'],
            'description' => ['nullable','string'],
            'price_min' => ['sometimes','numeric','min:0'],
            'badge_discount' => ['nullable','string','max:50'],
            'deposit_amount' => ['nullable','numeric','min:0'],
            'duration_days' => ['sometimes','string','max:50'],
            'hours_count' => ['sometimes','integer','min:0'],
            'session_count' => ['sometimes','integer','min:0'],
            'level' => ['nullable','string','max:50'],
            'country_id' => ['sometimes','uuid'],
            'city_id' => ['sometimes','uuid'],
            'is_active' => ['sometimes','boolean'],
            'show_on_home' => ['sometimes','boolean'],
            'features' => ['nullable','array'],
            'features.*' => ['nullable','string','max:255'],
        ];
    }
}
