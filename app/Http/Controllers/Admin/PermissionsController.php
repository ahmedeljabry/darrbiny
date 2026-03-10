<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Support\AccessLabels;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Spatie\Permission\Models\Permission;

class PermissionsController extends BaseController
{
    public function index()
    {
        $perms = Permission::orderBy('name')->get();
        return view('admin.permissions.index', compact('perms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:permissions,name'],
        ]);

        Permission::firstOrCreate(['name' => trim($data['name'])]);

        return back()->with('status','تم إنشاء الصلاحية بنجاح');
    }

    public function destroy(string $id)
    {
        $permission = Permission::findById($id);
        if (AccessLabels::isCorePermission($permission->name)) {
            return back()->withErrors([
                'error' => 'لا يمكن حذف صلاحية أساسية مستخدمة داخل النظام.',
            ]);
        }

        $permission->delete();
        return back()->with('status','تم حذف الصلاحية بنجاح');
    }
}
