<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\Fees;

class PaymentDetailsResource extends JsonResource
{
    public function toArray($request): array
    {
        // Get user wallet balance (points_balance)
        $user = $this->user;
        $walletBalance = $user ? $user->points_balance : 0;
        
        // Check if this is for reservation fee or plan payment
        $isReservationFee = $this->status === \App\Models\UserRequest::STATUS_PENDING_PAYMENT;
        
        if ($isReservationFee) {
            // Reservation fee payment
            if (!$this->relationLoaded('plan')) {
                $this->load('plan');
            }
            $countryId = $this->plan?->country_id;
            $serviceFeeMinor = Fees::reservationFeeMinor($countryId);
            $serviceFee = $serviceFeeMinor / 100;
            $vatPercent = (float) config('app.vat_percent', 0.0);
            $vatAmount = ($serviceFee * $vatPercent) / 100;
            $totalAmount = $serviceFee + $vatAmount;
            $totalMinor = (int) round($totalAmount * 100);
            
            return [
                'user_request_id' => $this->id,
                'payment_type' => 'reservation_fee',
                'service_fee' => [
                    'minor' => $serviceFeeMinor,
                    'amount' => $serviceFee,
                    'currency' => $this->currency,
                    'description' => 'رسوم الخدمة - استقبلي عروض أسعار من 1000 مدربة واختاري الأنسب لكي بسهولة',
                ],
                'vat' => [
                    'percent' => $vatPercent,
                    'amount' => $vatAmount,
                    'currency' => $this->currency,
                ],
                'total' => [
                    'minor' => $totalMinor,
                    'amount' => $totalAmount,
                    'currency' => $this->currency,
                ],
                'wallet' => [
                    'balance' => $walletBalance,
                    'balance_minor' => $walletBalance * 100, // Assuming points are stored as major units
                    'can_pay' => $walletBalance >= $totalAmount,
                    'remaining_after_payment' => max(0, $walletBalance - $totalAmount),
                ],
                'payment_methods' => [
                    'wallet' => true,
                    'apple_pay' => true,
                    'card' => true,
                    'supported_cards' => ['visa', 'mastercard', 'mada'],
                ],
                'security_notice' => 'لحماية حقوقك وضمان جودة الخدمة، تأكدي من اتمام الدفع داخل المنصة فقط. لسنا مسؤولين عن أي مبالغ تدفع المدربات خارج المنصة.',
            ];
        } else {
            // Plan payment (after offer selection)
            if (!$this->relationLoaded('offers')) {
                $this->load('offers');
            }
            
            $offer = $this->offers->where('status', \App\Models\TrainerOffer::STATUS_ACCEPTED)->first();
            
            if (!$offer) {
                abort(422, 'No accepted offer found');
            }
            
            $planAmountMinor = $offer->price_minor;
            $planAmount = $planAmountMinor / 100;
            $vatPercent = (float) config('app.vat_percent', 0.0);
            $vatAmount = ($planAmount * $vatPercent) / 100;
            $totalAmount = $planAmount + $vatAmount;
            $totalMinor = (int) round($totalAmount * 100);
            
            return [
                'user_request_id' => $this->id,
                'payment_type' => 'plan_full',
                'plan_amount' => [
                    'minor' => $planAmountMinor,
                    'amount' => $planAmount,
                    'currency' => $this->currency,
                    'description' => 'دفع كامل قيمة الخطة',
                ],
                'vat' => [
                    'percent' => $vatPercent,
                    'amount' => $vatAmount,
                    'currency' => $this->currency,
                ],
                'total' => [
                    'minor' => $totalMinor,
                    'amount' => $totalAmount,
                    'currency' => $this->currency,
                ],
                'wallet' => [
                    'balance' => $walletBalance,
                    'balance_minor' => $walletBalance * 100, // Assuming points are stored as major units
                    'can_pay' => $walletBalance >= $totalAmount,
                    'remaining_after_payment' => max(0, $walletBalance - $totalAmount),
                ],
                'payment_methods' => [
                    'wallet' => true,
                    'apple_pay' => true,
                    'card' => true,
                    'supported_cards' => ['visa', 'mastercard', 'mada'],
                ],
                'security_notice' => 'لحماية حقوقك وضمان جودة الخدمة، تأكدي من اتمام الدفع داخل المنصة فقط. لسنا مسؤولين عن أي مبالغ تدفع المدربات خارج المنصة.',
            ];
        }
    }
}

