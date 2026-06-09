<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckTrainerAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'area_level_1' => ['required', 'string', 'max:120'],
            'area_level_2' => ['nullable', 'string', 'max:120'],
            'area_level_3' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
        ];
    }
}
