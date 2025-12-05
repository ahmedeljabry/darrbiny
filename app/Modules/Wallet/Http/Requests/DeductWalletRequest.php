<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeductWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reference' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'المبلغ مطلوب',
            'amount.integer' => 'المبلغ يجب أن يكون رقماً صحيحاً',
            'amount.min' => 'المبلغ يجب أن يكون أكبر من صفر',
            'notes.max' => 'الملاحظات يجب أن تكون أقل من 500 حرف',
            'reference.max' => 'المرجع يجب أن يكون أقل من 255 حرف',
        ];
    }
}

