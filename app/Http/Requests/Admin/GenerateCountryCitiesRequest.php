<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateCountryCitiesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasRole('ADMIN');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'iso2' => ['nullable', 'string', 'size:2'],
            'currency' => ['nullable', 'string', 'size:3'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'iso2' => strtoupper(trim((string) $this->input('iso2'))),
            'currency' => strtoupper(trim((string) $this->input('currency'))),
        ]);
    }
}

