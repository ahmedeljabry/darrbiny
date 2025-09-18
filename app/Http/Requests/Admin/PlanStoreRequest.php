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
            'hours_count' => ['required','integer','min:1'],
            'training_type' => ['required','string','max:50'],
            'country_id' => ['nullable','uuid'],
            'city_id' => ['nullable','uuid'],
            'base_price_minor' => ['required','integer','min:0'],
        ];
    }
}

