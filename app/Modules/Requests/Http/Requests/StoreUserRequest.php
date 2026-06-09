<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_id' => ['required', 'uuid'],
            'trainer_id' => ['nullable', 'uuid', 'exists:users,id'],
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'area_level_1' => ['required', 'string', 'max:120'],
            'area_level_2' => ['nullable', 'string', 'max:120'],
            'area_level_3' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'string', 'max:64'],
            'description' => ['nullable', 'string', 'max:1000'],
            'has_user_car' => ['required', 'boolean'],
            'wants_trainer_car' => ['required', 'boolean'],
            'needs_pickup' => ['required', 'boolean'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
