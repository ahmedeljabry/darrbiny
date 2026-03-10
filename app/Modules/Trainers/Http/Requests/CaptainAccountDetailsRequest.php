<?php

declare(strict_types=1);

namespace App\Modules\Trainers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CaptainAccountDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('TRAINER');
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_driving_license' => $this->boolean('has_driving_license'),
            'car_available' => $this->has('car_available') ? $this->boolean('car_available') : null,
            'pickup_available' => $this->has('pickup_available') ? $this->boolean('pickup_available') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'bio' => ['nullable', 'string', 'max:2000'],
            'car_type' => ['nullable', 'string', 'max:120'],
            'car_model' => ['nullable', 'string', 'max:120'],
            'car_model_year' => ['nullable', 'string', 'max:20'],
            'car_year' => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'car_plate_number' => ['nullable', 'string', 'max:20'],
            'has_driving_license' => ['required', 'boolean'],
            'license_number' => ['nullable', 'string', 'max:50'],
            'license_expiry_date' => ['nullable', 'date', 'after_or_equal:today'],
            'country_id' => ['required', 'uuid', 'exists:countries,id'],
            'area_level_1' => ['required', 'string', 'max:120'],
            'area_level_2' => ['nullable', 'string', 'max:120'],
            'area_level_3' => ['nullable', 'string', 'max:120'],
            'locality' => ['nullable', 'string', 'max:120'],
            'car_available' => ['nullable', 'boolean'],
            'pickup_available' => ['nullable', 'boolean'],
        ];
    }
}
