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
            'hours_count' => ['sometimes','integer','min:1'],
            'training_type' => ['sometimes','string','max:50'],
            'country_id' => ['nullable','uuid'],
            'city_id' => ['nullable','uuid'],
            'base_price_minor' => ['sometimes','integer','min:0'],
            'is_active' => ['sometimes','boolean'],
        ];
    }
}

