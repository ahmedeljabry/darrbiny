<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\Rating;
use Illuminate\Routing\Controller as BaseController;

class RatingsAdminController extends BaseController
{
    public function index()
    {
        $ratings = Rating::latest()->paginate(20);
        return view('admin.ratings.index', compact('ratings'));
    }
}

