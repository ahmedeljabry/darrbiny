<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\User;
use App\Modules\Notifications\Services\NotificationTopicService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Throwable;

class FcmChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $topic = $this->resolveTopic($notifiable);

        if ($topic === null) {
            return;
        }

        try {
            app(Messaging::class)->send(
                $this->withTopic(
                    $this->resolveMessage($notifiable, $notification),
                    $topic
                )
            );
        } catch (Throwable $exception) {
            Log::warning('FCM notification delivery failed.', [
                'notification' => $notification::class,
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id ?? null,
                'topic' => $topic,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveTopic(object $notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationForFcmTopic')) {
            $topic = $notifiable->routeNotificationForFcmTopic();

            return is_string($topic) && $topic !== '' ? $topic : null;
        }

        if ($notifiable instanceof User) {
            return app(NotificationTopicService::class)->userTopic($notifiable);
        }

        return null;
    }

    private function withTopic(CloudMessage|array $message, string $topic): CloudMessage|array
    {
        if ($message instanceof CloudMessage) {
            return $message->toTopic($topic);
        }

        unset($message['token'], $message['tokens'], $message['condition']);
        $message['topic'] = $topic;

        return $message;
    }

    private function resolveMessage(object $notifiable, Notification $notification): CloudMessage|array
    {
        if (method_exists($notification, 'toFcm')) {
            $message = $notification->toFcm($notifiable);

            if ($message instanceof CloudMessage || is_array($message)) {
                return $message;
            }
        }

        $payload = $this->resolvePayload($notifiable, $notification);
        $title = isset($payload['title']) && is_scalar($payload['title']) ? (string) $payload['title'] : config('app.name');
        $body = isset($payload['message']) && is_scalar($payload['message']) ? (string) $payload['message'] : null;

        $data = $this->stringifyPayload([
            'notification_id' => (string) $notification->id,
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
