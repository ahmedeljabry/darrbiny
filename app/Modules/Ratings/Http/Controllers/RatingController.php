<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Http\Controllers;

use App\Models\Rating;
use App\Modules\Ratings\Http\Resources\RatingResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RatingController extends BaseController
{
    public function index(Request $request)
    {
        $q = Rating::with([
            'user.profilePicture',
            'trainer.profilePicture',
            'trainer.trainerProfile',
            'trainer.trainerProfile.country',
            'trainer.trainerProfile.city',
        ]);

        if ($tid = $request->query('trainer_id')) {
            $q->where('trainer_id', $tid);
        }

        $ratings = $q->latest()->paginate(20);

        return response()->json([
            'data' => RatingResource::collection($ratings->items()),
            'pagination' => [
                'current_page' => $ratings->currentPage(),
                'last_page' => $ratings->lastPage(),
                'per_page' => $ratings->perPage(),
                'total' => $ratings->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => ['required','uuid'],
            'user_request_id' => ['required','uuid'],
            'stars' => ['required','integer','min:1','max:5'],
            'comment' => ['nullable','string','max:1000'],
        ]);
        $data['user_id'] = $request->user()->id;
        $rating = Rating::create($data);

        // Load relationships for response
        $rating->load([
            'user.profilePicture',
            'trainer.profilePicture',
            'trainer.trainerProfile',
            'trainer.trainerProfile.country',
            'trainer.trainerProfile.city',
        ]);

        return response()->json(['data' => new RatingResource($rating)], 201);
    }
}

