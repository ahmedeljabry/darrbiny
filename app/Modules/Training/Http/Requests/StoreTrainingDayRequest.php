<?php

declare(strict_types=1);

namespace App\Modules\Training\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainingDayRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'user_request_id' => ['required','uuid'],
            'date' => ['required','date'],
            'hours_done' => ['required','integer','min:1','max:12'],
            'notes' => ['nullable','string','max:2000'],
        ];
    }
}

