<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\UserRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class SubscriptionsController extends BaseController
{
    public function index(Request $request)
    {
        $q = UserRequest::with('plan','user')->latest();
        if ($status = $request->query('status')) $q->where('status', $status);
        $subs = $q->paginate(20);
        return view('admin.subscriptions.index', compact('subs'));
    }
}

