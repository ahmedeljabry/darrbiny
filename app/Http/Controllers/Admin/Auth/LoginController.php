<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class LoginController extends BaseController
{
    public function show(): ViewContract|RedirectResponse
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user && $user->hasRole('ADMIN')) {
                return redirect()->route('admin.dashboard');
            }
            Auth::logout();
        }

        return view('admin.auth.login');
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $remember = (bool)($data['remember'] ?? false);
        $login = $data['login'];

        $credentials = [
            filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'name' => $login,
            'password' => $data['password'],
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();
            if (!$user || !$user->hasRole('ADMIN')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'login' => 'Only admins can login.',
                ])->withInput($request->only('login'));
            }

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'login' => 'Invalid credentials.',
        ])->withInput($request->only('login', 'remember'));
    }

    public function logout(\Illuminate\Http\Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
