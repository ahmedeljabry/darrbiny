<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class NotificationsAdminController extends BaseController
{
    public function index()
    {
        $users = User::select('id','name','phone_with_cc')->latest()->limit(50)->get();
        return view('admin.notifications.index', compact('users'));
    }
}

