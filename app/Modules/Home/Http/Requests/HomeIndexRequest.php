<?php

declare(strict_types=1);

namespace App\Modules\Offers\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomeIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'country_id' => ['sometimes','integer','exists:countries,id'],
            'city_id'    => ['sometimes','integer','exists:cities,id'],
            'includes'   => ['sometimes','string'],
            'limit_plans'=> ['sometimes','integer','min:1','max:20'],
            'limit_trainers'=> ['sometimes','integer','min:1','max:20'],
        ];
    }

    public function includes(): array {
        $default = ['hero','featured','best_value','quick','trainers','promises'];
        return array_intersect(
            $default,
            array_filter(explode(',', (string)$this->input('includes', implode(',',$default))))
        );
    }
}

