<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'plan_id' => ['required','uuid'],
            'start_date' => ['required','date','after_or_equal:today'],
            'has_user_car' => ['required','boolean'],
            'wants_trainer_car' => ['required','boolean'],
            'needs_pickup' => ['required','boolean'],
        ];
    }
}

