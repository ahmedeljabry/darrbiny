<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Observers;

use App\Models\Rating;
use App\Models\TrainerProfile;

class RatingObserver
{
    public function created(Rating $rating): void
    {
        $profile = TrainerProfile::where('user_id', $rating->trainer_id)->first();
        if (!$profile) return;
        $count = (int) $profile->rating_count + 1;
        $avg = ($profile->rating_avg * $profile->rating_count + $rating->stars) / $count;
        $profile->rating_count = $count;
        $profile->rating_avg = round($avg, 2);
        $profile->save();
    }
}

