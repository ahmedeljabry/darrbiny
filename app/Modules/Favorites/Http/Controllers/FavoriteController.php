<?php

declare(strict_types=1);

namespace App\Modules\Favorites\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class FavoriteController extends BaseController
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'trainer_id' => ['required','uuid','exists:users,id'],
            'user_id' => ['required','uuid','exists:users,id'],
        ]);
        Favorite::firstOrCreate([
            'user_id' => $data['user_id'],
            'trainer_id' => $data['trainer_id'],
        ]);
        return response()->json(['data' => ['ok' => true]]);
    }

    public function destroy(string $trainerId, Request $request)
    {
        Favorite::where('user_id', $request->user_id)->where('trainer_id', $trainerId)->delete();
        return response()->json(['data' => ['ok' => true]]);
    }
}

