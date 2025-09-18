<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class WalletsController extends BaseController
{
    public function index()
    {
        $users = User::orderBy('name')->paginate(20);
        return view('admin.wallets.index', compact('users'));
    }
}

