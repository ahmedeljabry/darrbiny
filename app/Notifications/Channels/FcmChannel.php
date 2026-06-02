<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\UserDeviceToken;
use App\Support\NotificationPayloadSanitizer;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Throwable;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $tokens = $notifiable->routeNotificationFor('fcm', $notification);

        if ($tokens instanceof Collection) {
            $tokens = $tokens->all();
        }

        $tokens = collect($tokens ?? [])
            ->filter(static fn (mixed $token): bool => is_string($token) && $token !== '')
            ->unique()
            ->values()
            ->all();

        if ($tokens === []) {
            return;
        }

        try {
            $report = app(Messaging::class)->sendMulticast(
                $this->resolveMessage($notifiable, $notification),
                $tokens
            );

            $staleTokens = array_values(array_unique(array_merge(
                $report->unknownTokens(),
                $report->invalidTokens(),
            )));

            if ($staleTokens !== []) {
                UserDeviceToken::query()
                    ->whereIn('token', $staleTokens)
                    ->delete();
            }

            if ($report->hasFailures()) {
                Log::warning('FCM notification delivery completed with failures.', [
                    'notification' => $notification::class,
                    'notifiable_type' => $notifiable::class,
                    'notifiable_id' => $notifiable->id ?? null,
                    'attempted_tokens' => count($tokens),
                    'stale_tokens' => $staleTokens,
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('FCM notification delivery failed.', [
                'notification' => $notification::class,
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id ?? null,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveMessage(object $notifiable, Notification $notification): CloudMessage|array
    {
        if (method_exists($notification, 'toFcm')) {
            $message = $notification->toFcm($notifiable);

            if ($message instanceof CloudMessage || is_array($message)) {
                return $message;
            }
        }

        $payload = NotificationPayloadSanitizer::withoutUuids(
            $this->resolvePayload($notifiable, $notification)
        );
        $title = isset($payload['title']) && is_scalar($payload['title']) ? (string) $payload['title'] : config('app.name');
        $body = isset($payload['message']) && is_scalar($payload['message']) ? (string) $payload['message'] : null;

        $data = $this->stringifyPayload([
            'notification_type' => $payload['type'] ?? $notification::class,
            ...$payload,
        ]);

        $message = CloudMessage::new()
            ->withData($data)
            ->withHighestPossiblePriority()
            ->withDefaultSounds();

        if ($title !== null || $body !== null) {
            $message = $message->withNotification(FirebaseNotification::create($title, $body));
        }

        return $message;
    }

    private function resolvePayload(object $notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            $payload = $notification->toDatabase($notifiable);

            if (is_array($payload)) {
                return $payload;
            }
        }

        if (method_exists($notification, 'toArray')) {
            $payload = $notification->toArray($notifiable);

            if (is_array($payload)) {
                return $payload;
            }
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, string>
     */
    private function stringifyPayload(array $payload): array
    {
        $data = [];

        foreach ($payload as $key => $value) {
            if ($value === null || $key === '') {
                continue;
            }

            if (is_bool($value)) {
                $data[$key] = $value ? '1' : '0';

                continue;
            }

            if (is_scalar($value)) {
                $data[$key] = (string) $value;

                continue;
            }

            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            if ($encoded !== false) {
                $data[$key] = $encoded;
            }
        }

        return $data;
    }
}
