<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\AdminStoreUserRequest;
use App\Http\Requests\Admin\AdminUpdateUserRequest;
use App\Models\City;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class UsersController extends BaseController
{
    public function index(Request $request)
    {
        $q = User::query();

        // Filters: role, status, search
        $role = $request->query('role');
        if ($role === 'trainer') {
            $q->role('TRAINER');
        } elseif ($role === 'admin') {
            $q->role('ADMIN');
        } elseif ($role === 'user') {
            $q->whereDoesntHave('roles', function ($r) {
                $r->whereIn('name', ['ADMIN', 'TRAINER']);
            });
        }

        $status = $request->query('status');
        if ($status === 'banned') {
            $q->withTrashed();
            $q->where(function ($w) {
                $w->whereNotNull('deleted_at')
                  ->orWhere('banned_until', '>', now());
            });
        } elseif ($status === 'active') {
            $q->whereNull('deleted_at')
              ->where(function ($w) {
                  $w->whereNull('banned_until')->orWhere('banned_until', '<=', now());
              });
        }

        $s = (string) $request->query('search', '');
        if ($s !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")
                ->orWhere('phone_with_cc', 'like', "%$s%")
                ->orWhere('email', 'like', "%$s%"));
        }

        $users = $q->with('roles')->latest()->paginate(20)->withQueryString();

        // Cards
        $totalUsers = User::count();
        $trainersCount = User::role('TRAINER')->count();
        $bannedCount = User::withTrashed()
            ->where(function ($w) {
                $w->whereNotNull('deleted_at')
                  ->orWhere('banned_until', '>', now());
            })->count();
        $activeCount = $totalUsers - $bannedCount;

        return view('admin.users.index', compact(
            'users', 'totalUsers', 'trainersCount', 'bannedCount', 'activeCount', 'role', 'status', 's'
        ));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->pluck('name');
        $countries = Country::orderBy('name')->get(['id','name']);
        $cities = City::orderBy('name')->get(['id','name']);
        return view('admin.users.create', compact('roles','countries','cities'));
    }

    public function store(AdminStoreUserRequest $request)
    {
        $data = $request->validated();
        $user = new User();
        $user->fill([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_with_cc' => $data['phone_with_cc'],
            'country_id' => $data['country_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'whatsapp_enabled' => (bool)($data['whatsapp_enabled'] ?? false),
        ]);
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        if (!empty($data['banned_until'])) {
            $user->update([
                'banned_until' => $data['banned_until'],
                'banned_reason' => $data['banned_reason'] ?? null,
            ]);
        }

        return redirect()->route('admin.users.index')->with('status','تم إنشاء المستخدم');
    }

    public function show(string $id)
    {
        $user = User::withTrashed()->with('roles')->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    public function edit(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $roles = Role::orderBy('name')->pluck('name');
        $countries = Country::orderBy('name')->get(['id','name']);
        $cities = City::orderBy('name')->get(['id','name']);
        return view('admin.users.edit', compact('user','roles','countries','cities'));
    }

    public function update(string $id, AdminUpdateUserRequest $request)
    {
        $user = User::withTrashed()->findOrFail($id);
        $data = $request->validated();
        $user->fill([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone_with_cc' => $data['phone_with_cc'],
            'country_id' => $data['country_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'whatsapp_enabled' => (bool)($data['whatsapp_enabled'] ?? false),
        ]);
        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        $user->syncRoles($data['roles'] ?? []);

        $user->update([
            'banned_until' => $data['banned_until'] ?? null,
            'banned_reason' => $data['banned_reason'] ?? null,
        ]);

        return redirect()->route('admin.users.index')->with('status','تم تحديث المستخدم');
    }

    public function freeze(string $id)
    {
        $user = User::findOrFail($id);
        if (Auth::id() === $user->id) {
            return back()->withErrors(['self_delete' => 'لا يمكنك حذف حسابك من لوحة التحكم.']);
        }
        $user->update(['deleted_at' => now()]);
        return back()->with('status', 'User frozen');
    }

    public function ban(string $id, Request $request)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'until' => ['required', 'date', 'after:now'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $user->update([
            'banned_until' => $data['until'],
            'banned_reason' => $data['reason'] ?? null,
        ]);

        return back()->with('status', 'User banned until '.$user->banned_until->format('Y-m-d H:i'));
    }

    public function unban(string $id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->update([
            'banned_until' => null,
            'banned_reason' => null,
        ]);
        if ($user->trashed()) {
            $user->restore();
        }
        return back()->with('status', 'User unbanned');
    }
}
