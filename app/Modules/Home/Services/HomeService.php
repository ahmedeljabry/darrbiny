<?php

declare(strict_types=1);

namespace App\Modules\Home\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\{Cache, DB, Auth};
use App\Models\{Setting, TrainerProfile, Plan, Favorite, HowItWorksSection, User};
use App\Support\StorageUrl;

class HomeService
{
    protected array $defaultIncludes = ['video', 'banner', 'plans', 'trainers', 'how_it_works', 'search'];
    private const LIMIT_TRAINERS = 5;
    private const CACHE_PREFIX   = 'home_data:v8';
    private const CACHE_MINUTES  = 5;

    protected ?string $countryId = null;

    public function getHomeData(?string $countryId = null, string $q = '', string $trainerMonth = ''): array
    {
        $user = Auth::guard('sanctum')->user();
        $authUserId = $user?->id;
        $this->countryId = $countryId ?: ($user->country_id ?? null);
        $favoritesStamp = $this->favoritesStamp($authUserId);
        $trainerMonth = $this->normalizeTrainerMonth($trainerMonth);

        $cacheKey = $this->cacheKey(
            $this->defaultIncludes,
            self::LIMIT_TRAINERS,
            (string) $this->countryId,
            $q,
            $authUserId,
            $favoritesStamp,
            $trainerMonth
        );

        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_MINUTES), function () use ($q, $authUserId, $trainerMonth) {
            $sections = [
                'video'        => fn() => $this->getVideo(),
                'banner'       => fn() => $this->getBanner(),
                'plans'        => fn() => $this->getPlans(),
                'trainers'     => fn() => [
                    'top_rated_trainers' => $this->getTrainers($authUserId),
                    'new_trainers_month' => $trainerMonth,
                    'new_trainers' => $this->getNewTrainers($authUserId, $trainerMonth),
                ],
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

    protected function cacheKey(
        array $includes,
        int $limitTrainers,
        ?string $countryId,
        string $q,
        ?string $uid,
        string $favoritesStamp,
        string $trainerMonth
    ): string
    {
        $includesKey = implode(',', Arr::sort($includes));
        return sprintf(
            '%s:inc=%s:lt=%d:location=%s:q=%s:favs=%s:u=%s:trainer_month=%s',
            self::CACHE_PREFIX,
            $includesKey,
            $limitTrainers,
            $countryId ?? 'null',
            md5($q),
            $favoritesStamp,
            $uid ?? 'guest',
            $trainerMonth
        );
    }

    private function normalizeTrainerMonth(string $trainerMonth): string
    {
        $trainerMonth = trim($trainerMonth);

        if (preg_match('/^\d{4}-\d{2}$/', $trainerMonth) === 1) {
            return $trainerMonth;
        }

        return now()->format('Y-m');
    }

    protected function favoritesStamp(?string $authUserId): string
    {
        if (!$authUserId) {
            return 'guest';
        }

        $meta = Favorite::query()
            ->where('user_id', $authUserId)
            ->selectRaw('count(*) as cnt, max(updated_at) as latest')
            ->first();

        $count = (int) ($meta->cnt ?? 0);
        $latest = $meta->latest ?? 'none';

        return $count . ':' . $latest;
    }

    protected function getVideo(): array
    {
        $userPath = Setting::where('key', 'video.app.path')->value('value');
        $captainPath = Setting::where('key', 'video.captain.path')->value('value');

        $userUrl = StorageUrl::make($userPath);
        $captainUrl = StorageUrl::make($captainPath);

        return [
            'url' => $userUrl,
            'user_url' => $userUrl,
            'captain_url' => $captainUrl,
        ];
    }

    protected function getBanner(): array
    {
        return [
            'student_text' => (string) (Setting::where('key', 'home.banner.student_text')->value('value') ?? ''),
            'trainer_text' => (string) (Setting::where('key', 'home.banner.trainer_text')->value('value') ?? ''),
        ];
    }

    protected function getPlans(): array
    {
        return Plan::query()
            ->active()
            ->home()
            ->byCountry($this->countryId)
            ->with('features:id,label')
            ->ordered()
            ->select('id', 'title', 'price_min', 'price_max', 'badge_discount', 'duration_days', 'hours_count', 'session_count')
            ->get()
            ->map(fn(Plan $p) => [
                'id'             => $p->id,
                'title'          => $p->title,
                'price_min'      => (float) $p->price_min,
                'price_max'      => $p->price_max !== null ? (float) $p->price_max : null,
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
            ->where('pending_approval', false)
            ->when($this->countryId, fn($q) => $q->where('country_id', $this->countryId))
            ->whereHas('user', function ($uq) use ($search) {
                $uq->whereNull('deleted_at')
                    ->where(function ($w) {
                        $w->whereNull('banned_until')
                            ->orWhere('banned_until', '<=', now());
                    })
                    ->trainerAccount();
                if ($search !== '') {
                    $uq->where(function ($w) use ($search) {
                        $w->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                }
            })
            ->with('user:id,name,deleted_at,profile_picture_id', 'user.profilePicture')
            ->orderByDesc('rating_avg')
            ->orderByDesc('rating_count')
            ->limit($limit)
            ->get();

        return $this->decorateTrainers($profiles, $authUserId);
    }

    protected function getNewTrainers(?string $authUserId, string $trainerMonth): array
    {
        [$year, $month] = array_map('intval', explode('-', $trainerMonth));

        $profiles = TrainerProfile::query()
            ->where('pending_approval', false)
            ->when($this->countryId, fn($q) => $q->where('country_id', $this->countryId))
            ->whereHas('user', function ($uq) use ($year, $month) {
                $uq->whereNull('deleted_at')
                    ->where(function ($w) {
                        $w->whereNull('banned_until')
                            ->orWhere('banned_until', '<=', now());
                    })
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->trainerAccount();
            })
            ->with('user:id,name,deleted_at,profile_picture_id,created_at', 'user.profilePicture')
            ->orderByDesc(
                User::query()
                    ->select('created_at')
                    ->whereColumn('users.id', 'trainer_profiles.user_id')
                    ->limit(1)
            )
            ->limit(self::LIMIT_TRAINERS)
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

        return $profiles->map(function (TrainerProfile $tp) use ($favoriteIds, $trainingCounts) {
            $u = $tp->user;
            return [
                'id'              => $u->id,
                'name'            => $u->name,
                'profile_picture' => $u->profile_picture_url,
                'rating_avg'      => $tp->rating_avg,
                'rating_count'    => $tp->rating_count,
                'country_id'      => $tp->country_id,
                'area_level_1'    => $tp->area_level_1,
                'area_level_2'    => $tp->area_level_2,
                'area_level_3'    => $tp->area_level_3,
                'locality'        => $tp->locality,
                'is_favorite'     => $favoriteIds->contains($u->id),
                'training_count'  => (int) ($trainingCounts[$u->id] ?? 0),
            ];
        })->all();
    }

    protected function getHowItWorks(): array
    {
        return HowItWorksSection::with('steps')
            ->orderBy('position')
            ->get()
            ->map(fn ($s) => [
                'title' => $s->title,
                'steps' => $s->steps->pluck('title')->values()->all(),
            ])
            ->all();
    }
}
