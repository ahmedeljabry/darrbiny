<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Services\Admin\RolesService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class RolesController extends BaseController
{
    public function index(RolesService $service)
    {
        ['roles' => $roles, 'perms' => $perms] = $service->list();
        return view('admin.roles.index', compact('roles','perms'));
    }

    public function store(Request $request, RolesService $service)
    {
        $data = $request->validate(['name' => ['required','string','max:64']]);
        $service->create($data['name']);
        return back()->with('status','Role created');
    }

    public function update(Request $request, string $id, RolesService $service)
    {
        $data = $request->validate(['permissions' => ['array']]);
        $service->updatePermissions($id, (array)($data['permissions'] ?? []));
        return back()->with('status','Role updated');
    }

    public function destroy(string $id, RolesService $service)
    {
        $service->delete($id);
        return back()->with('status','Role deleted');
    }
}
