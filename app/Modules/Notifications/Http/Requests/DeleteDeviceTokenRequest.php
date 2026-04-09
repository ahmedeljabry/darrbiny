<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز الجهاز مطلوب',
            'token.max' => 'رمز الجهاز طويل جداً',
        ];
    }
}
