<?php

declare(strict_types=1);

namespace App\Modules\Trainers\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CaptainAccountResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'bio' => $this->bio,
            'car_type' => $this->car_type,
            'car_model' => $this->car_model,
            'car_model_year' => $this->car_model_year,
            'car_year' => $this->car_year,
            'car_plate_number' => $this->car_plate_number,
            'has_driving_license' => (bool) $this->has_driving_license,
            'license_number' => $this->license_number,
            'license_expiry_date' => $this->license_expiry_date?->format('Y-m-d'),
            'car_available' => (bool) $this->car_available,
            'pickup_available' => (bool) $this->pickup_available,
            'country_id' => $this->country_id,
            'country' => $this->whenLoaded('country', fn () => [
                'id' => $this->country?->id,
                'name' => $this->country?->name,
            ]),
            'city_id' => $this->city_id,
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city?->id,
                'name' => $this->city?->name,
            ]),
            'is_complete' => $this->isComplete(),
            'bio_hint' => 'اكتبي هنا نبذة تعريفية عن نفسك توضّح للمتدربات من أنتي، بالإضافة إلى وصف مختصر لطريقتك في التدريب. يمكن تعديل النص لاحقاً.',
            'guidelines' => [
                'title' => 'تنبيه هام',
                'items' => [
                    'يمنع منعاً باتاً كتابة رقم الهاتف أو أي وسيلة تواصل شخصية بأي شكل داخل النبذة.',
                    'سيتم مراجعة النبذة بعد كل تعديل، وأي مخالفة للتعليمات ستؤدي إلى حظر الحساب بشكل نهائي.',
                    'نحرص على حماية حقوقك وضمان جودة الخدمة وبيئة آمنة ومنظمة داخل التطبيق، وسيُتاح التواصل الكامل في الخطوات النهائية من الحجز.',
                    'يرجى الالتزام بالتعليمات لتفادي حظر الحساب.',
                ],
            ],
        ];
    }

    protected function isComplete(): bool
    {
        return !empty($this->bio)
            && !empty($this->car_type)
            && !empty($this->car_model_year)
            && (bool) $this->has_driving_license
            && !empty($this->country_id)
            && !empty($this->city_id);
    }
}
