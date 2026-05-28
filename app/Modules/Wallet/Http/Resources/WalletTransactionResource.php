<?php

declare(strict_types=1);

namespace App\Modules\Wallet\Http\Resources;

use App\Models\Payment;
use App\Models\UserRequest;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletTransactionResource extends JsonResource
{
    /** @var array<string, Payment|null> */
    private static array $paymentCache = [];

    /** @var array<string, UserRequest|null> */
    private static array $userRequestCache = [];

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amountMajor(),
            'amount_minor' => $this->amountMinor(),
            'type' => $this->type,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'notes' => $this->displayNotes(),
            'processed_at' => $this->processed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function displayNotes(): ?string
    {
        $notes = $this->notes;
        if ($notes === null) {
            return null;
        }

        $notes = (string) $notes;
        if (trim($notes) === '') {
            return $notes;
        }

        if (preg_match('/^Payment:\s*([0-9a-fA-F-]{36})/u', $notes, $matches) === 1) {
            $payment = $this->paymentForId($matches[1]);
            $orderNumber = $payment?->userRequest?->display_order_number;

            if ($orderNumber !== null) {
                return "دفع طلب رقم #{$orderNumber}";
            }
        }

        if (preg_match('/^إلغاء دورة\s+#([0-9a-fA-F-]{36})(.*)$/u', $notes, $matches) === 1) {
            $userRequest = $this->userRequestForId($matches[1]);
            $orderNumber = $userRequest?->display_order_number;

            if ($orderNumber !== null) {
                return "إلغاء دورة #{$orderNumber}{$matches[2]}";
            }
        }

        return $notes;
    }

    private function paymentForId(string $id): ?Payment
    {
        if (! array_key_exists($id, self::$paymentCache)) {
            self::$paymentCache[$id] = Payment::with('userRequest')->find($id);
        }

        return self::$paymentCache[$id];
    }

    private function userRequestForId(string $id): ?UserRequest
    {
        if (! array_key_exists($id, self::$userRequestCache)) {
            self::$userRequestCache[$id] = UserRequest::query()->find($id);
        }

        return self::$userRequestCache[$id];
    }
}
