<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;
        
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,' . $userId],
            'phone_with_cc' => ['nullable', 'string', 'max:32', 'unique:users,phone_with_cc,' . $userId],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
            'password_confirmation' => ['required_with:password', 'string'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB max
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // alias for profile picture
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'الاسم يجب أن يكون أقل من 255 حرف',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.unique' => 'البريد الإلكتروني مستخدم بالفعل',
            'phone_with_cc.unique' => 'رقم الهاتف مستخدم بالفعل',
            'password.confirmed' => 'كلمة المرور الجديدة وتأكيدها غير متطابقين',
            'password.min' => 'كلمة المرور يجب أن تكون على الأقل 8 أحرف',
            'password_confirmation.required_with' => 'تأكيد كلمة المرور مطلوب عند تغيير كلمة المرور',
            'profile_picture.image' => 'يجب أن يكون الملف صورة',
            'profile_picture.mimes' => 'نوع الصورة يجب أن يكون: jpeg, png, jpg, أو webp',
            'profile_picture.max' => 'حجم الصورة يجب أن يكون أقل من 5 ميجابايت',
            'image.image' => 'يجب أن يكون الملف صورة',
            'image.mimes' => 'نوع الصورة يجب أن يكون: jpeg, png, jpg, أو webp',
            'image.max' => 'حجم الصورة يجب أن يكون أقل من 5 ميجابايت',
        ];
    }
}

