<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Rating;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RatingsAdminController extends BaseController
{
    public function index()
    {
        $ratings = Rating::latest()->paginate(20);
        return view('admin.ratings.index', compact('ratings'));
    }

    public function update(Rating $rating, Request $request)
    {
        $data = $request->validate([
            'stars' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $rating->update($data);

        return back()->with('status', 'تم تحديث التقييم.');
    }

    public function destroy(Rating $rating)
    {
        $rating->delete();
        return back()->with('status', 'تم حذف التقييم.');
    }
}
