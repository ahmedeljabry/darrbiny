<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\Permission\Models\Role;

class UserRolesController extends BaseController
{
    public function edit(string $id)
    {
        $user = User::findOrFail($id);
        $roles = Role::orderBy('name')->get();
        return view('admin.users.roles', compact('user','roles'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $request->validate(['roles' => ['array']]);
        $user->syncRoles($request->input('roles', []));
        return redirect()->route('admin.users.index')->with('status','User roles updated');
    }
}

