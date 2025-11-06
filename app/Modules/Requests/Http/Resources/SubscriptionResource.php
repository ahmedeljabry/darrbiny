<?php

declare(strict_types=1);

namespace App\Modules\Requests\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray($request): array
    {
        // Get accepted offer to find trainer
        $acceptedOffer = $this->offers->where('status', \App\Models\TrainerOffer::STATUS_ACCEPTED)->first();
        $trainer = $acceptedOffer?->trainer;
        $trainerProfile = $trainer?->trainerProfile;
        
        // Calculate course end date (start_date + duration_days)
        $durationDays = (int) ($this->plan->duration_days ?? 0);
        $endDate = $this->start_date && $durationDays > 0
            ? $this->start_date->copy()->addDays($durationDays)
            : null;
        
        // Course ID starting from 500 (use numeric part of UUID)
        $uuidHex = str_replace('-', '', $this->id);
        $courseId = 500 + (int) hexdec(substr($uuidHex, 0, 4));
        
        // Get location
        $location = '';
        if ($this->plan->city) {
            $location = $this->plan->city->name;
            if ($this->plan->country) {
                $location .= ' ، ' . $this->plan->country->name;
            }
        }
        
        // Training car info - get from trainer profile or booking
        $carModel = null;
        if ($trainerProfile && $trainerProfile->car_available) {
            // You might want to add a car_model field to trainer_profile
            $carModel = 'سيارة المدرب'; // Default, can be enhanced with actual car model
        }
        $trainingCar = $this->wants_trainer_car 
            ? ($carModel ?? 'كامري 2024') // Default car model, can be from trainer profile
            : ($this->has_user_car ? 'سيارة المتدرب' : 'غير محدد');
        
        // Transport request
        $transportRequest = $this->needs_pickup ? 'اخذ وارجاع' : 'لا يوجد';
        
        // Get price from accepted offer or plan minimum
        $priceMinor = $acceptedOffer?->price_minor ?? ($this->plan->price_min ?? 0) * 100;
        $price = $priceMinor / 100;
        
        // Map status to tab categories
        $statusCategory = $this->mapStatusToCategory($this->status);
        
        return [
            'id' => $this->id,
            'course_id' => $courseId,
            'status' => $this->status,
            'status_category' => $statusCategory, // 'active', 'pending', 'completed'
            
            // Course header
            'title' => 'كورس تدريب',
            'duration' => [
                'days' => $this->plan->duration_days ?? 0,
                'hours' => $this->plan->hours_count ?? 0,
                'display' => ($this->plan->duration_days ?? 0) . ' ايام ( ' . ($this->plan->hours_count ?? 0) . ' ساعات)',
            ],
            
            // Trainer information
            'trainer' => $trainer ? [
                'id' => $trainer->id,
                'name' => 'كوتش / ' . $trainer->name,
                'rating' => [
                    'average' => (float) ($trainerProfile->rating_avg ?? 0),
                    'count' => (int) ($trainerProfile->rating_count ?? 0),
                    'display' => number_format((float) ($trainerProfile->rating_avg ?? 0), 1),
                ],
                'profile_picture' => $trainer->profile_picture_url ?? null, // Assuming this field exists or add it
                'can_contact' => true,
            ] : null,
            
            // Course details
            'course_details' => [
                'course_id' => '#' . $courseId,
                'start_date' => $this->start_date?->format('d M Y'),
                'start_date_ar' => $this->formatArabicDate($this->start_date),
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
            
            // Cancellation info
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
            
            // Actions
            'actions' => [
                'can_cancel' => in_array($this->status, [
                    \App\Models\UserRequest::STATUS_PENDING_PAYMENT,
                    \App\Models\UserRequest::STATUS_AWAITING_OFFERS,
                    \App\Models\UserRequest::STATUS_OFFER_SELECTED,
                    \App\Models\UserRequest::STATUS_PAID,
                    \App\Models\UserRequest::STATUS_IN_TRAINING,
                ]) && (!$this->relationLoaded('cancellationRequest') || !$this->cancellationRequest || $this->cancellationRequest->status !== \App\Models\CancellationRequest::STATUS_PENDING),
                'can_view_schedule' => in_array($this->status, [
                    \App\Models\UserRequest::STATUS_IN_TRAINING,
                    \App\Models\UserRequest::STATUS_COMPLETED,
                ]),
                'can_contact_trainer' => $trainer !== null,
            ],
            
            // Schedule progress
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
                    ];
                })->values();
            }),
            
            // Additional info
            'plan' => [
                'id' => $this->plan->id,
                'title' => $this->plan->title,
                'description' => $this->plan->description,
            ],
            
            'created_at' => $this->created_at?->toIso8601String(),
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
}

