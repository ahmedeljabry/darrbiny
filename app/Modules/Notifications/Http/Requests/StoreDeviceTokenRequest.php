<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeviceTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', Rule::in(['android', 'ios', 'web'])],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'رمز الجهاز مطلوب',
            'token.max' => 'رمز الجهاز طويل جداً',
            'platform.in' => 'المنصة يجب أن تكون android أو ios أو web',
            'device_name.max' => 'اسم الجهاز يجب ألا يتجاوز 255 حرف',
        ];
    }
}
