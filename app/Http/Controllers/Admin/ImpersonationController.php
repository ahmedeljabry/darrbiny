<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends BaseController
{
    public function start(User $user, Request $request)
    {
        // Keep the current admin id so we can restore later.
        $request->session()->put('impersonator_id', Auth::id());

        Auth::login($user);

        return redirect('/')->with('status', 'تم الدخول بحساب المستخدم. لإنهاء الوضع اضغط إيقاف التنكر.');
    }

    public function stop(Request $request)
    {
        $adminId = $request->session()->pull('impersonator_id');

        if ($adminId) {
            Auth::loginUsingId($adminId);
            return redirect()->route('admin.dashboard')->with('status', 'تم إيقاف التنكر والعودة لحساب الأدمن.');
        }

        Auth::logout();
        return redirect('/login');
    }
}
