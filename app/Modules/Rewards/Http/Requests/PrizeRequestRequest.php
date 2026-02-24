<?php

declare(strict_types=1);

namespace App\Modules\Rewards\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrizeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'reward_id' => ['required', 'uuid', 'exists:rewards,id'],
            'points_spent' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'reward_id.required' => 'معرف الجائزة مطلوب',
            'reward_id.exists' => 'الجائزة غير موجودة',
            'points_spent.integer' => 'عدد النقاط يجب أن يكون رقماً',
            'points_spent.min' => 'عدد النقاط يجب أن يكون على الأقل 1',
        ];
    }
}

