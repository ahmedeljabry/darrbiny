<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PlanStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['required','string','max:120'],
            'description' => ['nullable','string'],
            'price_min' => ['required','numeric','min:0'],
            'badge_discount' => ['nullable','string','max:50'],
            'deposit_amount' => ['nullable','numeric','min:0'],
            'duration_days' => ['required','string','max:50'],
            'hours_count' => ['nullable','integer','min:0'],
            'session_count' => ['nullable','integer','min:0'],
            'level' => ['nullable','string','max:50'],
            'country_id' => ['required','uuid'],
            'city_id' => ['required','uuid'],
            'is_active' => ['sometimes','boolean'],
            'features' => ['nullable','array'],
            'features.*' => ['nullable','string','max:255'],
        ];
    }
}
