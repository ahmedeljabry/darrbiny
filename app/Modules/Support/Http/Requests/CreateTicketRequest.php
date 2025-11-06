<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Allow both authenticated and unauthenticated users
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_with_cc' => ['required', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب',
            'name.max' => 'الاسم يجب ألا يتجاوز 255 حرف',
            'phone_with_cc.required' => 'رقم الهاتف مطلوب',
            'phone_with_cc.max' => 'رقم الهاتف يجب ألا يتجاوز 32 حرف',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'email.max' => 'البريد الإلكتروني يجب ألا يتجاوز 255 حرف',
            'subject.required' => 'الموضوع مطلوب',
            'subject.max' => 'الموضوع يجب ألا يتجاوز 255 حرف',
            'details.required' => 'التفاصيل مطلوبة',
            'details.max' => 'التفاصيل يجب ألا تتجاوز 5000 حرف',
        ];
    }
}

