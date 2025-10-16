<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Services\Admin\SettingsService;
use Illuminate\Routing\Controller as BaseController;

class SettingsController extends BaseController
{
    public function index(SettingsService $service)
    {
        $settings = $service->allKeyed();
        return view('admin.settings.index', compact('settings'));
    }

    public function update(SettingsUpdateRequest $request, SettingsService $service)
    {
        $service->update(
            $request->validated(),
            $request->file('logo'),
            $request->file('video_app_file'),
            $request->file('favicon')
        );
        return back()->with('status','تم حفظ الإعدادات');
    }
}
