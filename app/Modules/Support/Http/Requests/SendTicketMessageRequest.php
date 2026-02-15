<?php

declare(strict_types=1);

namespace App\Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendTicketMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'in:open,pending,closed'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'الرسالة مطلوبة',
            'message.max' => 'الرسالة يجب ألا تتجاوز 5000 حرف',
            'status.in' => 'الحالة غير صحيحة',
        ];
    }
}

