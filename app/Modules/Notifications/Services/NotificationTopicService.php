<?php

declare(strict_types=1);

namespace App\Modules\Notifications\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

class NotificationTopicService
{
    public function __construct(
        private readonly Messaging $messaging,
    ) {}

    /**
     * @return list<string>
     */
    public function topicsForUser(User $user): array
    {
        $topics = [$this->userTopic($user)];

        if ($user->hasRole('TRAINER')) {
            $topics[] = (string) config('services.firebase.topics.trainers', 'trainers');
        }

        if ($user->hasRole('USER')) {
            $topics[] = (string) config('services.firebase.topics.trainees', 'trainees');
        }

        return array_values(array_unique(array_filter($topics, static fn (?string $topic): bool => is_string($topic) && $topic !== '')));
    }

    public function userTopic(User $user): string
    {
        $prefix = (string) config('services.firebase.topics.user_prefix', 'user_');

        return $prefix.$user->id;
    }

    public function subscribeUserToken(User $user, string $token): void
    {
        $topics = $this->topicsForUser($user);

        if ($topics === []) {
            return;
        }

        try {
            $this->messaging->subscribeToTopics($topics, $token);
        } catch (Throwable $exception) {
            Log::warning('FCM topic subscription failed.', [
                'user_id' => $user->id,
                'topics' => $topics,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    public function unsubscribeUserToken(User $user, string $token): void
    {
        $topics = $this->topicsForUser($user);

        if ($topics === []) {
            return;
        }

        try {
            $this->messaging->unsubscribeFromTopics($topics, $token);
        } catch (Throwable $exception) {
            Log::warning('FCM topic unsubscription failed.', [
                'user_id' => $user->id,
                'topics' => $topics,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
