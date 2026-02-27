<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->input('mobile')
            ?? $this->input('mobile_number')
            ?? $this->input('phone_with_cc');

        $newPassword = $this->input('new_password')
            ?? $this->input('newpassword')
            ?? $this->input('password');

        $newPasswordConfirmation = $this->input('new_password_confirmation')
            ?? $this->input('newpassword_confirmation')
            ?? $this->input('confirm_password')
            ?? $this->input('confirmPassword')
            ?? $this->input('password_confirmation');

        $payload = [];
        if ($mobile !== null) {
            $payload['mobile'] = $mobile;
        }
        if ($newPassword !== null) {
            $payload['new_password'] = $newPassword;
        }
        if ($newPasswordConfirmation !== null) {
            $payload['new_password_confirmation'] = $newPasswordConfirmation;
        }

        if (!empty($payload)) {
            $this->merge($payload);
        }
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'min:6', 'max:20', 'exists:users,phone_with_cc'],
            'new_password' => ['required', 'string', 'confirmed', Password::min(8)],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.required' => 'رقم الجوال مطلوب',
            'mobile.exists' => 'رقم الجوال غير مسجل',
            'new_password.required' => 'كلمة المرور الجديدة مطلوبة',
            'new_password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين',
            'new_password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف',
            'new_password_confirmation.required' => 'تأكيد كلمة المرور مطلوب',
        ];
    }
}

