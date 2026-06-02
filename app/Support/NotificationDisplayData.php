<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\UserRequest;
use Illuminate\Notifications\DatabaseNotification;

final class NotificationDisplayData
{
    /** @var array<string, UserRequest|null> */
    private static array $userRequestCache = [];

    public static function for(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        if (! is_array($data)) {
            return [];
        }

        $userRequest = self::userRequestFromData($data);
        if (! $userRequest) {
            return $data;
        }

        $displayOrderNumber = $userRequest->notificationOrderNumber();

        $data['order_number'] ??= $userRequest->order_number;
        $data['formatted_order_number'] ??= $userRequest->formatted_order_number;
        $data['display_order_number'] ??= $displayOrderNumber;

        if (isset($data['message']) && is_string($data['message'])) {
            $data['message'] = self::replaceRequestReference(
                $data['message'],
                (string) $userRequest->id,
                $displayOrderNumber
            );
        }

        return NotificationPayloadSanitizer::withoutUuids($data);
    }

    private static function userRequestFromData(array $data): ?UserRequest
    {
        $userRequestId = $data['user_request_id'] ?? null;

        if (! is_string($userRequestId) || $userRequestId === '') {
            return null;
        }

        if (! array_key_exists($userRequestId, self::$userRequestCache)) {
            self::$userRequestCache[$userRequestId] = UserRequest::query()->find($userRequestId);
        }

        return self::$userRequestCache[$userRequestId];
    }

    private static function replaceRequestReference(string $message, string $requestId, string $displayOrderNumber): string
    {
        return str_replace(
            ["#{$requestId}", "رقم {$requestId}", "رقم #{$requestId}"],
            ["#{$displayOrderNumber}", "رقم #{$displayOrderNumber}", "رقم #{$displayOrderNumber}"],
            $message
        );
    }
}
