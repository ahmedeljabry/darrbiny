<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable','string','max:255'],
            'email' => ['nullable','email','max:255','unique:users,email'],
            'phone_with_cc' => ['required','string','max:32','unique:users,phone_with_cc'],
            'password' => ['nullable','string','min:6'],
            'country_id' => ['nullable','uuid'],
            'city_id' => ['nullable','uuid'],
            'whatsapp_enabled' => ['sometimes','boolean'],
            'roles' => ['sometimes','array'],
            'roles.*' => ['string','max:64'],
            'banned_until' => ['nullable','date','after:now'],
            'banned_reason' => ['nullable','string','max:255'],
        ];
    }
}

