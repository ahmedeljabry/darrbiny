<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

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
        $data = $request->validate(['name' => ['required','string','max:64']]);
        Permission::firstOrCreate(['name' => $data['name']]);
        return back()->with('status','Permission created');
    }

    public function destroy(string $id)
    {
        Permission::findById($id)->delete();
        return back()->with('status','Permission deleted');
    }
}

