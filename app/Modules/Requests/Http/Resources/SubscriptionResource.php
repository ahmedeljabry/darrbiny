<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        $acceptedOffer = $this->offers->where('status', \App\Models\TrainerOffer::STATUS_ACCEPTED)->first();
        $trainer = $acceptedOffer?->trainer;
        $trainerProfile = $trainer?->trainerProfile;
        $trainerBio = $this->resolveTrainerBio($trainerProfile);
        $offerMessage = $acceptedOffer?->message;

        $durationDays = (int) ($this->plan->duration_days ?? 0);
        $endDate = $this->start_date && $durationDays > 0
            ? $this->start_date->copy()->addDays($durationDays)
            : null;

        $uuidHex = str_replace('-', '', $this->id);
        $courseId = 500 + (int) hexdec(substr($uuidHex, 0, 4));

        $location = '';
        if ($this->plan->city) {
            $location = $this->plan->city->name;
            if ($this->plan->country) {
                $location .= ' ، ' . $this->plan->country->name;
            }
        }

        $carModel = null;
        if ($trainerProfile && $trainerProfile->car_available) {
            $carModelParts = array_filter([
                $trainerProfile->car_type,
                $trainerProfile->car_model_year,
            ], static fn ($value) => filled($value));
            $carModel = trim(implode(' ', $carModelParts));
            if ($carModel === '') {
                $carModel = 'سيارة المدرب';
            }
        }
        $trainingCar = $this->wants_trainer_car
            ? ($carModel ?? 'سيارة المدرب')
            : ($this->has_user_car ? 'سيارة المتدرب' : 'غير محدد');

        $transportRequest = $this->needs_pickup ? 'اخذ وارجاع' : 'لا يوجد';
        $priceMinor = $acceptedOffer?->price_minor ?? ($this->plan->price_min ?? 0) * 100;
        $price = $priceMinor / 100;

        $statusCategory = $this->mapStatusToCategory($this->status);

        return [

            'id' => $this->id,
            'course_id' => $courseId,
            'status' => $this->status,
            'status_category' => $statusCategory,
            'trainer_rate' => $trainerProfile ? (float) ($trainerProfile->rating_avg ?? 0) : null,
            'trainer_name' => $trainer ? $trainer->name : null,
            'trainer_offer_message' => $offerMessage,
            'description' => $this->description,

            'title' => 'كورس تدريب',
            'duration' => [
                'days' => $this->plan->duration_days ?? 0,
                'hours' => $this->plan->hours_count ?? 0,
                'display' => ($this->plan->duration_days ?? 0) . ' ايام ( ' . ($this->plan->hours_count ?? 0) . ' ساعات)',
            ],

            'trainer' => $trainer ? [
                'id' => $trainer->id,
                'name' => 'كوتش / ' . $trainer->name,
                'bio' => $trainerBio,
                'rating' => [
                    'average' => (float) ($trainerProfile->rating_avg ?? 0),
                    'count' => (int) ($trainerProfile->rating_count ?? 0),
                    'display' => number_format((float) ($trainerProfile->rating_avg ?? 0), 1),
                ],
                'profile_picture' => $trainer->profile_picture_url ?? null,
                'can_contact' => true,
            ] : null,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
                'profile_picture' => $this->user->profile_picture_url ?? null,
            ],
            'course_details' => [
                'course_id' => '#' . $courseId,
                'start_date' => $this->start_date?->format('d M Y'),
                'start_date_ar' => $this->formatArabicDate($this->start_date),
                'has_user_car' => $this->has_user_car,
                'wants_trainer_car' => $this->wants_trainer_car,
                'needs_pickup' => $this->needs_pickup,
                'start_time' => $this->start_time,
                'end_date' => $endDate?->format('d M Y'),
                'end_date_ar' => $this->formatArabicDate($endDate),
                'location' => $location,
                'training_car' => $trainingCar,
                'transport_request' => $transportRequest,
                'price' => [
                    'amount' => $price,
                    'minor' => $priceMinor,
                    'currency' => $this->currency,
                    'display' => number_format($price, 0) . ' ' . $this->currency,
                ],
            ],

            'cancellation' => $this->whenLoaded('cancellationRequest', function () {
                if (!$this->cancellationRequest) {
                    return null;
                }
                return [
                    'id' => $this->cancellationRequest->id,
                    'status' => $this->cancellationRequest->status,
                    'reason' => $this->cancellationRequest->reason,
                    'admin_notes' => $this->cancellationRequest->admin_notes,
                    'processed_at' => $this->cancellationRequest->processed_at?->toIso8601String(),
                ];
            }),

            'actions' => [
                'can_cancel' => false,
                'can_view_schedule' => in_array($this->status, [
                    \App\Models\UserRequest::STATUS_IN_TRAINING,
                    \App\Models\UserRequest::STATUS_COMPLETED,
                ]),
                'can_contact_trainer' => $trainer !== null,
            ],

            'schedule' => $this->when($this->relationLoaded('plan.scheduleItems') || $this->plan->relationLoaded('scheduleItems'), function () {
                if (!$this->relationLoaded('scheduleProgress')) {
                    $this->load('scheduleProgress');
                }

                $scheduleItems = $this->plan->scheduleItems ?? collect();
                $progress = $this->scheduleProgress->keyBy('plan_schedule_item_id');

                return $scheduleItems->map(function ($item) use ($progress) {
                    $userProgress = $progress->get($item->id);
                    return [
                        'id' => $item->id,
                        'day_number' => $item->day_number,
                        'title' => $item->title,
                        'is_checked' => $userProgress ? $userProgress->is_checked : false,
                        'checked_at' => $userProgress?->checked_at?->toIso8601String(),
                        'progress_id' => $userProgress?->id,
                        'status' => $userProgress?->status ?? \App\Models\UserScheduleProgress::STATUS_PENDING,
                        'sent_at' => $userProgress?->sent_at?->toIso8601String(),
                        'rejection_reason' => $userProgress?->rejection_reason,
                        'rating' => $userProgress?->rating,
                        'rating_titles' => $userProgress?->rating_titles,
                        'rating_comment' => $userProgress?->rating_comment,
                    ];
                })->values();
            }),

            'plan' => [
                'id' => $this->plan->id,
                'title' => $this->plan->title,
                'description' => $this->plan->description,
                'price_min' => $this->plan->price_min,
                'price_max' => $this->plan->price_max,
            ],

            'created_at' => $this->created_at,
        ];
    }

    /**
     * Map booking status to category (active, pending, completed)
     */
    private function mapStatusToCategory(string $status): string
    {
        return match($status) {
            \App\Models\UserRequest::STATUS_IN_TRAINING => 'active',
            \App\Models\UserRequest::STATUS_COMPLETED => 'completed',
            default => 'pending', // pending_payment, awaiting_offers, offer_selected, paid, cancelled
        };
    }

    /**
     * Format date in Arabic format (e.g., "24 يناير")
     */
    private function formatArabicDate($date): ?string
    {
        if (!$date) {
            return null;
        }

        $months = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];

        return $date->format('d') . ' ' . ($months[(int) $date->format('m')] ?? '');
    }

    private function resolveTrainerBio($trainerProfile): ?string
    {
        if (!$trainerProfile) {
            return null;
        }

        $bio = $trainerProfile->bio;
        if (
            $trainerProfile->pending_approval
            && is_array($trainerProfile->pending_changes)
            && array_key_exists('bio', $trainerProfile->pending_changes)
        ) {
            $bio = $trainerProfile->pending_changes['bio'];
        }

        return $bio;
    }
}
