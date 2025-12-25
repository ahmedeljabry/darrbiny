<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\SettingsUpdateRequest;
use App\Models\Country;
use App\Services\Admin\SettingsService;
use Illuminate\Routing\Controller as BaseController;

class SettingsController extends BaseController
{
    public function index(SettingsService $service)
    {
        $settings = $service->allKeyed();
        $countries = Country::orderBy('name')->get();
        $trainerRoles = $this->decodeListSetting($settings['roles.trainer'] ?? null);
        $trainerRestrictions = $this->decodeListSetting($settings['restrictions.trainer'] ?? null);
        $userRoles = $this->decodeListSetting($settings['roles.user'] ?? null);
        $userRestrictions = $this->decodeListSetting($settings['restrictions.user'] ?? null);
        return view('admin.settings.index', compact('settings', 'countries', 'trainerRoles', 'trainerRestrictions', 'userRoles', 'userRestrictions'));
    }

    public function update(SettingsUpdateRequest $request, SettingsService $service)
    {
        $service->update(
            $request->validated(),
            $request->file('logo'),
            $request->file('video_app_file'),
            $request->file('favicon'),
            $request->file('video_captain_file'),
        );
        return back()->with('status','تم حفظ الإعدادات');
    }

    private function decodeListSetting(?string $value): array
    {
        $items = json_decode($value ?? '[]', true);
        $items = is_array($items) ? $items : [];
        return empty($items) ? [''] : $items;
    }
}
