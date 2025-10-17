<?php

declare(strict_types=1);

namespace App\Modules\Home\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\{Cache, Storage, DB, Auth};
use App\Models\{Setting, TrainerProfile, Plan, Favorite, City, HowItWorksSection};

class HomeService
{
    protected array $defaultIncludes = ['video', 'plans', 'trainers', 'how_it_works', 'search'];
    private const LIMIT_TRAINERS = 5;
    private const CACHE_PREFIX   = 'home_data:v5';
    private const CACHE_MINUTES  = 5;

    protected ?string $cityId = null;

    public function getHomeData(?string $cityId = null, string $q = ''): array
    {
        $user         = Auth::user();
        $authUserId   = (string) Auth::id();
        $this->cityId = $cityId ?: ($user->city_id ?? null);

        $cacheKey = $this->cacheKey(
            $this->defaultIncludes,
            self::LIMIT_TRAINERS,
            (string) $this->cityId,
            $q,
            $authUserId
        );

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($q, $authUserId) {
            $sections = [
                'video'        => fn() => $this->getVideo(),
                'plans'        => fn() => $this->getPlans(),
                'trainers'     => fn() => ['top_rated_trainers' => $this->getTrainers($authUserId)],
                'how_it_works' => fn() => $this->getHowItWorks(),
                'search'       => fn() => ['trainers' => $this->getTrainers($authUserId, $q, 20)],
            ];

            $data = [];
            foreach ($this->defaultIncludes as $key) {
                if (isset($sections[$key])) {
                    $data[$key] = $sections[$key]();
                }
            }
            return $data;
        });
    }

    protected function cacheKey(array $includes, int $limitTrainers, ?string $cityId, string $q, ?string $uid): string
    {
        $includesKey = implode(',', Arr::sort($includes));
        return sprintf(
            '%s:inc=%s:lt=%d:city=%s:q=%s:u=%s',
            self::CACHE_PREFIX,
            $includesKey,
            $limitTrainers,
            $cityId ?? 'null',
            md5($q),
            $uid ?? 'guest'
        );
    }

    protected function getVideo(): array
    {
        $path = (string) Setting::where('key', 'video.app.path')->value('value');
        if (!$path) {
            return ['url' => null];
        }

        $disk = config('filesystems.default', 'public');
        return ['url' => Storage::disk($disk)->url($path)];
    }

    protected function getPlans(): array
    {
        return Plan::query()
            ->active()
            ->home()
            ->byLocation($this->cityId)
            ->with('features:id,label')
            ->latest()
            ->select('id', 'title', 'price_min', 'badge_discount', 'duration_days', 'hours_count', 'session_count')
            ->get()
            ->map(fn(Plan $p) => [
                'id'             => $p->id,
                'title'          => $p->title,
                'price_min'      => (float) $p->price_min,
                'badge_discount' => $p->badge_discount,
                'duration_days'  => $p->duration_days,
                'hours_count'    => $p->hours_count,
                'session_count'  => $p->session_count,
                'features'       => $p->features->pluck('label')->values()->all(),
            ])
            ->all();
    }

    protected function getTrainers(?string $authUserId, string $search = '', int $limit = self::LIMIT_TRAINERS): array
    {
        $search = trim($search);

        $profiles = TrainerProfile::query()
            ->when($this->cityId, fn($q) => $q->where('city_id', $this->cityId))
            ->whereHas('user', function ($uq) use ($search) {
                $uq->whereNull('deleted_at')->role('TRAINER');
                if ($search !== '') {
                    $uq->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            })
            ->with('user:id,name,deleted_at')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->limit($limit)
            ->get();

        return $this->decorateTrainers($profiles, $authUserId);
    }

    protected function decorateTrainers($profiles, ?string $authUserId): array
    {
        if ($profiles->isEmpty()) {
            return [];
        }

        $trainerIds  = $profiles->pluck('user_id')->all();
        $favoriteIds = $authUserId
            ? Favorite::where('user_id', $authUserId)->pluck('trainer_id')
            : collect();

        $trainingCounts = DB::table('training_days')
            ->select('trainer_id', DB::raw('count(*) as cnt'))
            ->where('status', \App\Models\TrainingDay::STATUS_APPROVED)
            ->whereIn('trainer_id', $trainerIds)
            ->groupBy('trainer_id')
            ->pluck('cnt', 'trainer_id');

        $cityIds   = $profiles->pluck('city_id')->filter()->unique()->values();
        $cityNames = $cityIds->isEmpty()
            ? collect()
            : City::whereIn('id', $cityIds)->pluck('name', 'id');

        return $profiles->map(function (TrainerProfile $tp) use ($favoriteIds, $trainingCounts, $cityNames) {
            $u = $tp->user;
            return [
                'id'             => $u->id,
                'name'           => $u->name,
                'rating_avg'     => $tp->rating_avg,
                'rating_count'   => $tp->rating_count,
                'city_id'        => $tp->city_id,
                'city_name'      => $cityNames[$tp->city_id] ?? null,
                'country_id'     => $tp->country_id,
                'is_favorite'    => $favoriteIds->contains($u->id),
                'training_count' => (int) ($trainingCounts[$u->id] ?? 0),
            ];
        })->all();
    }

    protected function getHowItWorks(): array
    {
        return HowItWorksSection::with('steps')->get()->map(fn($s) => ['title' => $s->title, 'steps' => $s->steps->pluck('title')->values()->all(),])->all();
    }
}
