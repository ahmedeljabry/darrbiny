<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_request_id' => ['required','uuid'],
            'price_minor' => ['required','integer','min:0'],
            'message' => ['nullable','string','max:2000'],
        ];
    }
}

