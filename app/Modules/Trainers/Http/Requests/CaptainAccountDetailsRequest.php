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
            'car_model_year' => ['nullable', 'string', 'max:20'],
            'has_driving_license' => ['required', 'boolean'],
            'country_id' => ['nullable', 'uuid', 'exists:countries,id'],
            'city_id' => ['nullable', 'uuid', 'exists:cities,id'],
            'car_available' => ['nullable', 'boolean'],
            'pickup_available' => ['nullable', 'boolean'],
        ];
    }
}
