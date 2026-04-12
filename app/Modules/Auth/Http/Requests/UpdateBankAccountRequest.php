<?php

declare(strict_types=1);

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'bank_account' => ['required', 'string', 'max:100'],
            'bank_account_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:34'], // IBAN max length is 34 characters
            'bank_name' => ['required', 'string', 'max:255'],
            'bank_country_id' => ['required', 'uuid', 'exists:countries,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'bank_account.required' => 'رقم الحساب البنكي مطلوب',
            'bank_account.max' => 'رقم الحساب البنكي يجب ألا يتجاوز 100 حرف',
            'bank_account_name.required' => 'اسم صاحب الحساب مطلوب',
            'bank_account_name.max' => 'اسم صاحب الحساب يجب ألا يتجاوز 255 حرف',
            'iban.required' => 'رقم الآيبان مطلوب',
            'iban.max' => 'رقم الآيبان يجب ألا يتجاوز 34 حرف',
            'bank_name.required' => 'اسم البنك مطلوب',
            'bank_name.max' => 'اسم البنك يجب ألا يتجاوز 255 حرف',
            'bank_country_id.required' => 'دولة البنك مطلوبة',
            'bank_country_id.uuid' => 'دولة البنك غير صحيحة',
            'bank_country_id.exists' => 'دولة البنك غير موجودة',
        ];
    }
}

