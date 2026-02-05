<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'price_minor' => ['required_without_all:message', 'integer', 'min:0'],
            'message' => ['required_without_all:price_minor', 'nullable', 'string', 'max:2000'],
        ];
    }
}
