<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_with_cc' => ['required', 'string', 'min:6', 'max:20', 'unique:users'],
            'password' => ['required', 'string', 'confirmed', Password::min(6)],
            'type' => ['required', 'string', 'in:user,captain'],
            'referral_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}

