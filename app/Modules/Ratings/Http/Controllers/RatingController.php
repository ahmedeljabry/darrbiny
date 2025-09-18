<?php

declare(strict_types=1);

namespace App\Modules\Ratings\Http\Controllers;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RatingController extends BaseController
{
    public function index(Request $request)
    {
        $q = Rating::query();
        if ($tid = $request->query('trainer_id')) $q->where('trainer_id', $tid);
        return response()->json(['data' => $q->latest()->paginate(20)]);
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
        return response()->json(['data' => $rating], 201);
    }
}

